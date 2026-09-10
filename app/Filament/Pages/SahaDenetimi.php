<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\SahaDenetimi as SahaDenetimiModel;
use App\Models\SahaDenetimiOzelMadde;
use App\Support\SahaDenetimiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Saha Denetimi ("Şantiye Denetim ve Değerlendirme") — isgpratik SAHA
 * DENETİMİ/1-15.jpg + gerçek örnek PDF. 9 kategori / 41 maddelik kontrol
 * listesi tek sayfada doldurulur (isgpratik'teki 9 adımlı sihirbaz yerine);
 * PDF çıktısı isgpratik'in gerçek raporuyla birebir aynı düzende.
 */
class SahaDenetimi extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.saha-denetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'saha-denetimi';

    protected static ?string $title = 'Saha Denetimi';

    protected static ?string $navigationLabel = 'Saha Denetimi';

    public ?int $firmaId = null;

    public ?string $isTanimi = null;

    public ?string $santiyeAdi = null;

    public ?string $santiyeSorumlusu = null;

    public ?string $isReferansNo = null;

    public ?string $denetimTarihi = null;

    public ?string $denetimSaati = null;

    public ?string $denetciAdi = null;

    /** null = tüm sektörler için ortak liste — config isg.risk_ai.sektorler */
    public ?string $sektorAnahtari = null;

    /** @var array<string, array{sonuc: ?string, aciklama: ?string, foto_yolu: ?string}> kod => cevap */
    public array $cevaplar = [];

    /** @var array<string, UploadedFile> kod => yüklenen fotoğraf */
    public array $fotoYuklemeleri = [];

    /** @var array<int, array{ad_soyad: string, gorev: ?string, kkd: string}> */
    public array $ekipUyeleri = [];

    public ?string $yeniEkipAdSoyad = null;

    public ?string $yeniEkipGorev = null;

    /** @var array<int, string> */
    public array $yeniEkipKkd = [];

    public ?string $genelNotlar = null;

    public bool $taslakYuklendi = false;

    public ?string $yeniOzelSektorAnahtari = null;

    public ?string $yeniOzelKategoriAdi = null;

    public ?string $yeniOzelIfade = null;

    public bool $yeniOzelKritik = false;

    public bool $yeniOzelUygulanamazIzni = true;

    public function mount(): void
    {
        $this->denetimTarihi = now()->toDateString();
        $this->denetimSaati = now()->format('H:i');

        $this->cevaplariSenkronizeEt();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** Sabit 41 madde + kullanıcının o sektör için eklediği özel maddeler. */
    #[Computed]
    public function kategoriler(): array
    {
        $kategoriler = config('isg.saha_denetimi.kategoriler');

        $gecerliOzelMaddeler = $this->ozelMaddeler
            ->filter(fn (SahaDenetimiOzelMadde $m) => $m->sektor_anahtari === null || $m->sektor_anahtari === $this->sektorAnahtari)
            ->groupBy('kategori_ad');

        foreach ($gecerliOzelMaddeler as $kategoriAdi => $maddeler) {
            $ozelMaddeListesi = $maddeler->map(fn (SahaDenetimiOzelMadde $m) => [
                'kod' => 'OZL'.$m->id,
                'ifade' => $m->ifade,
                'kritik' => $m->kritik,
                'uygulanamaz_izni' => $m->uygulanamaz_izni,
            ])->values()->all();

            $eslesenAnahtar = collect($kategoriler)->search(fn ($k) => $k['ad'] === $kategoriAdi);

            if ($eslesenAnahtar !== false) {
                $kategoriler[$eslesenAnahtar]['maddeler'] = [...$kategoriler[$eslesenAnahtar]['maddeler'], ...$ozelMaddeListesi];
            } else {
                $kategoriler['ozel_'.Str::slug($kategoriAdi, '_')] = ['ad' => $kategoriAdi, 'maddeler' => $ozelMaddeListesi];
            }
        }

        return $kategoriler;
    }

    #[Computed]
    public function kkdSecenekleri(): array
    {
        return config('isg.saha_denetimi.kkd_secenekleri');
    }

    #[Computed]
    public function sektorler(): array
    {
        return collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad'])->all();
    }

    /** @return Collection<int, SahaDenetimiOzelMadde> */
    #[Computed]
    public function ozelMaddeler(): Collection
    {
        return SahaDenetimiOzelMadde::where('user_id', Filament::auth()->id())
            ->orderBy('kategori_ad')
            ->orderBy('sira')
            ->get();
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    /** @return array{uygun: int, uygun_degil: int, yuzde: float, kritik_var: bool} */
    #[Computed]
    public function canliSonuc(): array
    {
        $maddeler = $this->tumMaddeler();
        $uygun = 0;
        $uygunDegil = 0;
        $kritikVar = false;

        foreach ($maddeler as $kod => $madde) {
            $cevap = $this->cevaplar[$this->anahtar($kod)] ?? null;

            if (($cevap['sonuc'] ?? null) === 'uygun') {
                $uygun++;
            } elseif (($cevap['sonuc'] ?? null) === 'uygun_degil') {
                $uygunDegil++;
                if ($madde['kritik']) {
                    $kritikVar = true;
                }
            }
        }

        $cevaplanan = $uygun + $uygunDegil;

        return [
            'uygun' => $uygun,
            'uygun_degil' => $uygunDegil,
            'yuzde' => $cevaplanan > 0 ? round($uygun / $cevaplanan * 100, 2) : 0.0,
            'kritik_var' => $kritikVar,
        ];
    }

    /** @return Collection<int, SahaDenetimiModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->sahaDenetimleri()->where('durum', 'tamamlandi')->latest()->get() ?? collect();
    }

    /** Firma başına en fazla 1 açık taslak — "kaldığı yerden devam et". */
    #[Computed]
    public function aktifTaslak(): ?SahaDenetimiModel
    {
        return $this->firma?->sahaDenetimleri()->where('durum', 'taslak')->first();
    }

    /** kod => madde config (tüm kategoriler + özel maddeler düzleştirilmiş). */
    private function tumMaddeler(): array
    {
        $sonuc = [];

        foreach ($this->kategoriler as $kategoriAnahtari => $kategori) {
            foreach ($kategori['maddeler'] as $madde) {
                $sonuc[$madde['kod']] = [...$madde, 'kategori_anahtari' => $kategoriAnahtari, 'kategori_ad' => $kategori['ad']];
            }
        }

        return $sonuc;
    }

    /** Henüz $cevaplar'da olmayan (yeni eklenen özel) maddeler için boş cevap girişi açar. */
    private function cevaplariSenkronizeEt(): void
    {
        foreach ($this->tumMaddeler() as $kod => $madde) {
            $anahtar = $this->anahtar($kod);

            if (! isset($this->cevaplar[$anahtar])) {
                $this->cevaplar[$anahtar] = ['sonuc' => null, 'aciklama' => null, 'foto_yolu' => null];
            }
        }
    }

    private function ifadeYaz(string $ifade): string
    {
        return str_replace('{FIRMA}', $this->firma?->unvan ?? '{FİRMA}', $ifade);
    }

    /**
     * "1.1" gibi kodlar Livewire'ın dot-notation property yolunda (wire:model,
     * ->set()) her noktayı ayrı bir dizi seviyesi sanar; bu yüzden $cevaplar/
     * $fotoYuklemeleri dizilerinde gerçek anahtar olarak nokta içermeyen bu
     * güvenli hale kullanılır (görüntüleme/PDF'te hâlâ gerçek "kod" kullanılır).
     */
    public function anahtar(string $kod): string
    {
        return str_replace('.', '_', $kod);
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar, $this->aktifTaslak);
        $this->denetciAdi = $this->firma?->igu?->ad_soyad;

        if ($taslak = $this->aktifTaslak) {
            $this->taslaktanYukle($taslak);
        }
    }

    private function taslaktanYukle(SahaDenetimiModel $taslak): void
    {
        $this->isTanimi = $taslak->is_tanimi;
        $this->santiyeAdi = $taslak->santiye_adi;
        $this->santiyeSorumlusu = $taslak->santiye_sorumlusu;
        $this->isReferansNo = $taslak->is_referans_no;
        $this->denetimTarihi = $taslak->denetim_tarihi?->toDateString() ?? $this->denetimTarihi;
        $this->denetimSaati = $taslak->denetim_saati ?? $this->denetimSaati;
        $this->denetciAdi = $taslak->denetci_adi ?: $this->denetciAdi;
        $this->sektorAnahtari = $taslak->sektor_anahtari;
        $this->ekipUyeleri = $taslak->ekip_uyeleri ?? [];
        $this->genelNotlar = $taslak->genel_notlar;

        unset($this->kategoriler);
        $this->cevaplariSenkronizeEt();

        foreach ($taslak->cevaplar ?? [] as $c) {
            $anahtar = $this->anahtar($c['kod']);
            if (isset($this->cevaplar[$anahtar])) {
                $this->cevaplar[$anahtar] = ['sonuc' => $c['sonuc'], 'aciklama' => $c['aciklama'], 'foto_yolu' => $c['foto_yolu']];
            }
        }

        $this->taslakYuklendi = true;
    }

    public function taslakTemizle(): void
    {
        $this->aktifTaslak?->delete();
        unset($this->aktifTaslak);

        $this->reset([
            'isTanimi', 'santiyeAdi', 'santiyeSorumlusu', 'isReferansNo', 'sektorAnahtari',
            'ekipUyeleri', 'genelNotlar', 'cevaplar', 'taslakYuklendi',
        ]);
        $this->denetciAdi = $this->firma?->igu?->ad_soyad;
        unset($this->kategoriler);
        $this->cevaplariSenkronizeEt();

        Notification::make()->title('Taslak temizlendi')->success()->send();
    }

    public function updatedSektorAnahtari(): void
    {
        unset($this->kategoriler);
        $this->cevaplariSenkronizeEt();
    }

    public function ozelMaddeEkle(): void
    {
        if (blank($this->yeniOzelKategoriAdi) || blank($this->yeniOzelIfade)) {
            Notification::make()->title('Kategori adı ve madde ifadesi zorunlu')->danger()->send();

            return;
        }

        $madde = SahaDenetimiOzelMadde::create([
            'sektor_anahtari' => $this->yeniOzelSektorAnahtari,
            'kategori_ad' => $this->yeniOzelKategoriAdi,
            'ifade' => $this->yeniOzelIfade,
            'kritik' => $this->yeniOzelKritik,
            'uygulanamaz_izni' => $this->yeniOzelUygulanamazIzni,
        ]);

        unset($this->ozelMaddeler, $this->kategoriler);
        $this->cevaplariSenkronizeEt();

        $this->reset('yeniOzelKategoriAdi', 'yeniOzelIfade', 'yeniOzelKritik');
        $this->yeniOzelUygulanamazIzni = true;

        Notification::make()->title('Kontrol maddesi eklendi')->body($madde->kategori_ad.' — '.$madde->ifade)->success()->send();
    }

    public function ozelMaddeSil(int $id): void
    {
        $madde = SahaDenetimiOzelMadde::where('user_id', Filament::auth()->id())->find($id);

        if (! $madde) {
            return;
        }

        unset($this->cevaplar[$this->anahtar('OZL'.$madde->id)]);
        $madde->delete();
        unset($this->ozelMaddeler, $this->kategoriler);
    }

    public function cevapVer(string $kod, string $sonuc): void
    {
        $anahtar = $this->anahtar($kod);

        if (isset($this->cevaplar[$anahtar])) {
            $this->cevaplar[$anahtar]['sonuc'] = $sonuc;
        }
    }

    /** Bir maddeye eklenen (yeni yüklenen veya taslaktan gelen) fotoğrafı kaldırır. */
    public function fotoKaldir(string $kod): void
    {
        $anahtar = $this->anahtar($kod);

        unset($this->fotoYuklemeleri[$anahtar]);

        if (isset($this->cevaplar[$anahtar])) {
            $this->cevaplar[$anahtar]['foto_yolu'] = null;
        }
    }

    public function ekipHizliEkle(int $calisanId): void
    {
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if ($c) {
            $this->ekipUyeleri[] = ['ad_soyad' => $c->ad_soyad, 'gorev' => $c->gorev, 'kkd' => ''];
        }
    }

    public function ekipEkle(): void
    {
        if (blank($this->yeniEkipAdSoyad)) {
            return;
        }

        $this->ekipUyeleri[] = [
            'ad_soyad' => $this->yeniEkipAdSoyad,
            'gorev' => $this->yeniEkipGorev,
            'kkd' => implode(', ', $this->yeniEkipKkd),
        ];

        $this->reset('yeniEkipAdSoyad', 'yeniEkipGorev', 'yeniEkipKkd');
    }

    public function ekipSil(int $index): void
    {
        unset($this->ekipUyeleri[$index]);
        $this->ekipUyeleri = array_values($this->ekipUyeleri);
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?SahaDenetimiModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $maddeler = $this->tumMaddeler();

        foreach ($maddeler as $kod => $madde) {
            $cevap = $this->cevaplar[$this->anahtar($kod)] ?? [];

            if (($cevap['sonuc'] ?? null) === 'uygun_degil' && blank($cevap['aciklama'] ?? null)) {
                Notification::make()->title('Uygunsuzluk açıklaması zorunlu')
                    ->body($kod.' — '.$this->ifadeYaz($madde['ifade']))
                    ->danger()->send();

                return null;
            }
        }

        $cevaplarSnapshot = [];
        $uygun = 0;
        $uygunDegil = 0;
        $kritikUygunsuzlukVar = false;

        foreach ($maddeler as $kod => $madde) {
            $anahtar = $this->anahtar($kod);
            $cevap = $this->cevaplar[$anahtar] ?? ['sonuc' => null, 'aciklama' => null, 'foto_yolu' => null];

            $fotoYolu = $cevap['foto_yolu'] ?? null;
            if ($this->fotoYuklemeleri[$anahtar] ?? null) {
                $fotoYolu = $this->fotoYuklemeleri[$anahtar]->store('saha-denetimi-foto', 'public');
            }

            if ($cevap['sonuc'] === 'uygun') {
                $uygun++;
            } elseif ($cevap['sonuc'] === 'uygun_degil') {
                $uygunDegil++;
                if ($madde['kritik']) {
                    $kritikUygunsuzlukVar = true;
                }
            }

            $cevaplarSnapshot[] = [
                'kategori_ad' => $madde['kategori_ad'],
                'kod' => $kod,
                'ifade' => $this->ifadeYaz($madde['ifade']),
                'kritik' => $madde['kritik'],
                'sonuc' => $cevap['sonuc'],
                'aciklama' => $cevap['aciklama'],
                'foto_yolu' => $fotoYolu,
            ];
        }

        $cevaplanan = $uygun + $uygunDegil;

        $d = new SahaDenetimiModel([
            'firma_id' => $this->firma->id,
            'sektor_anahtari' => $this->sektorAnahtari,
            'durum' => 'tamamlandi',
            'revizyon' => $this->firma->sahaDenetimleri()->where('durum', 'tamamlandi')->count() + 1,
            'is_tanimi' => $this->isTanimi,
            'santiye_adi' => $this->santiyeAdi,
            'santiye_sorumlusu' => $this->santiyeSorumlusu,
            'is_referans_no' => $this->isReferansNo,
            'denetim_tarihi' => $this->denetimTarihi,
            'denetim_saati' => $this->denetimSaati,
            'denetci_adi' => $this->denetciAdi,
            'denetci_kase' => $this->firma->igu?->kase_gorseli,
            'cevaplar' => $cevaplarSnapshot,
            'ekip_uyeleri' => $this->ekipUyeleri,
            'genel_notlar' => $this->genelNotlar,
            'uygunluk_yuzdesi' => $cevaplanan > 0 ? round($uygun / $cevaplanan * 100, 2) : null,
            'kritik_uygunsuzluk_var' => $kritikUygunsuzlukVar,
        ]);
        $d->save();

        // Denetim tamamlandı — bu firma için bekleyen taslak varsa artık gereksiz.
        $this->firma->sahaDenetimleri()->where('durum', 'taslak')->delete();

        unset($this->gecmisKayitlar, $this->aktifTaslak);
        $this->taslakYuklendi = false;

        return $d;
    }

    /**
     * Yarıda bırakılan denetimi kaydeder — "Denetimi Tamamla"nın aksine
     * hiçbir alan zorunlu değildir (uygunsuzluk açıklaması dahil). Firma
     * başına tek taslak tutulur; ikinci "Taslak Kaydet" öncekini günceller.
     */
    public function taslakKaydet(): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $cevaplarSnapshot = [];
        foreach ($this->tumMaddeler() as $kod => $madde) {
            $anahtar = $this->anahtar($kod);
            $cevap = $this->cevaplar[$anahtar] ?? ['sonuc' => null, 'aciklama' => null, 'foto_yolu' => null];

            $fotoYolu = $cevap['foto_yolu'] ?? null;
            if ($this->fotoYuklemeleri[$anahtar] ?? null) {
                $fotoYolu = $this->fotoYuklemeleri[$anahtar]->store('saha-denetimi-foto', 'public');
            }

            $cevaplarSnapshot[] = [
                'kategori_ad' => $madde['kategori_ad'],
                'kod' => $kod,
                'ifade' => $this->ifadeYaz($madde['ifade']),
                'kritik' => $madde['kritik'],
                'sonuc' => $cevap['sonuc'],
                'aciklama' => $cevap['aciklama'],
                'foto_yolu' => $fotoYolu,
            ];
        }

        $taslak = $this->aktifTaslak ?? new SahaDenetimiModel(['firma_id' => $this->firma->id, 'durum' => 'taslak', 'revizyon' => 0]);

        $taslak->forceFill([
            'sektor_anahtari' => $this->sektorAnahtari,
            'is_tanimi' => $this->isTanimi,
            'santiye_adi' => $this->santiyeAdi,
            'santiye_sorumlusu' => $this->santiyeSorumlusu,
            'is_referans_no' => $this->isReferansNo,
            'denetim_tarihi' => $this->denetimTarihi,
            'denetim_saati' => $this->denetimSaati,
            'denetci_adi' => $this->denetciAdi,
            'cevaplar' => $cevaplarSnapshot,
            'ekip_uyeleri' => $this->ekipUyeleri,
            'genel_notlar' => $this->genelNotlar,
        ])->save();

        unset($this->aktifTaslak);
        $this->taslakYuklendi = true;

        Notification::make()->title('Taslak kaydedildi')->body('Kaldığınız yerden devam edebilirsiniz.')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('taslak')
                ->label('Taslak Olarak Kaydet')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->taslakKaydet()),

            Action::make('pdf')
                ->label('Denetimi Tamamla (Kaydet ve İndir PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $d = $this->kaydet();

                    if (! $d) {
                        return null;
                    }

                    Notification::make()->title('Saha denetimi kaydedildi')->body($d->belgeAdi())->success()->send();

                    return SahaDenetimiUretici::pdf($d);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $d = $this->firma?->sahaDenetimleri()->find($id);

        return $d ? SahaDenetimiUretici::pdf($d) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->sahaDenetimleri()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
