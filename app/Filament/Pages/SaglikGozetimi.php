<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\SaglikGozetimi as SaglikGozetimiModel;
use App\Filament\Support\ImzaSecenegi;
use App\Support\SaglikGozetimiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Sağlık Gözetimi Takibi — firma başına. Çalışan × sağlık tetkiki (işe giriş /
 * periyodik muayene, odyometri, SFT, portör vb.). Her satır için tetkik tarihi +
 * sonuç girilir; sonraki tetkik tarihi periyoda / tehlike sınıfına göre otomatik.
 */
class SaglikGozetimi extends Page
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.saglik-gozetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Merkezler';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'saglik-gozetimi';

    protected static ?string $title = 'Sağlık Gözetimi Takibi';

    protected static ?string $navigationLabel = 'Sağlık & Kaza';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Url(as: 'sekme')]
    public string $sekme = 'tetkik';

    public const SEKMELER = [
        'tetkik' => 'Tetkik & Muayene',
        'revir' => 'Revir & Protokol Defteri',
        'ecza' => 'Ecza Dolabı & SKT',
        'asi' => 'Aşı Takibi',
    ];

    // --- K1 Revir formu ---
    public ?int $revirCalisanId = null;
    public string $revirAdSoyad = '';
    public string $revirNeden = '';
    public string $revirTani = '';
    public string $revirIslem = '';
    public string $revirSonuc = 'ayakta';
    public ?int $revirIstirahatGun = null;

    // --- K1 Ecza Dolabı formu ---
    public string $eczaAd = '';
    public string $eczaKategori = 'pansuman';
    public int $eczaMiktar = 10;
    public int $eczaKritik = 3;
    public string $eczaBirim = 'Adet';
    public ?string $eczaSkt = null;
    public string $eczaKonum = 'Revir / Ana Dolap';

    // --- K1 Aşı Takibi formu ---
    public ?int $asiCalisanId = null;
    public string $asiAdSoyad = '';
    public string $asiTur = 'Tetanoz';
    public string $asiDoz = '1. Doz';
    public ?string $asiTarih = null;
    public ?string $asiSonrakiTarih = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    public ?int $yeniCalisanId = null;

    public string $yeniTur = 'periyodik';

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function gozetim(): ?SaglikGozetimiModel
    {
        return $this->firma ? SaglikGozetimiModel::firmaIcin($this->firma) : null;
    }

    /** @return \Illuminate\Support\Collection<int, Calisan> */
    #[Computed]
    public function calisanlar()
    {
        return $this->firma?->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function turler(): array
    {
        return config('isg.saglik_tetkik.turleri', []);
    }

    #[Computed]
    public function sonuclar(): array
    {
        return config('isg.saglik_tetkik.sonuclar', []);
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        unset($this->firma, $this->gozetim, $this->calisanlar, $this->revirKayitlari, $this->revirToplamSayisi, $this->eczaKalemleri, $this->asiKayitlari);

        $gozetim = $this->gozetim();
        $this->satirlar = $gozetim?->satirlar ?? [];
        $this->genelNot = $gozetim?->genel_not;
    }

    #[Computed]
    public function tibbiVeriGorebilirMi(): bool
    {
        $u = Filament::auth()->user();

        return ! $u instanceof \App\Models\User || $u->tibbiVeriGorebilirMi();
    }

    #[Computed]
    public function revirToplamSayisi(): int
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('revir_kayitlari')) {
            return 0;
        }

        return \App\Models\RevirKaydi::where('firma_id', $this->firma->id)->count();
    }

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    #[Computed]
    public function revirKayitlari()
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('revir_kayitlari')) {
            return collect();
        }

        return \App\Models\RevirKaydi::query()
            ->where('firma_id', $this->firma->id)
            ->latest('tarih')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function revirEkle(): void
    {
        if (! $this->firma) {
            return;
        }

        $calisan = $this->revirCalisanId ? $this->calisanlar->firstWhere('id', $this->revirCalisanId) : null;
        $ad = trim($calisan?->ad_soyad ?: $this->revirAdSoyad);
        if ($ad === '' || trim($this->revirNeden) === '') {
            Notification::make()->title('Çalışan adı ve başvuru nedeni zorunludur')->warning()->send();

            return;
        }

        $kullanici = Filament::auth()->user();
        $klinikKullaniciAdi = ($kullanici instanceof \App\Models\User && in_array($kullanici->unvan, ['isyeri_hekimi', 'dsp'], true))
            ? $kullanici->name
            : null;

        \App\Models\RevirKaydi::create([
            'firma_id' => $this->firma->id,
            'calisan_id' => $calisan?->id,
            'calisan_ad_soyad' => $ad,
            'tarih' => now(),
            'basvuru_nedeni' => trim($this->revirNeden),
            'bulgu_tani' => trim($this->revirTani) ?: null,
            'yapilan_islem' => trim($this->revirIslem) ?: null,
            'sonuc' => array_key_exists($this->revirSonuc, \App\Models\RevirKaydi::SONUCLAR) ? $this->revirSonuc : 'ayakta',
            'istirahat_gun' => $this->revirSonuc === 'istirahat' ? ($this->revirIstirahatGun ?: 1) : null,
            'hekim_dsp' => $this->firma->isyeriHekimi?->ad_soyad ?: $this->firma->dsp?->ad_soyad ?: $klinikKullaniciAdi,
        ]);

        $this->reset('revirCalisanId', 'revirAdSoyad', 'revirNeden', 'revirTani', 'revirIslem', 'revirIstirahatGun');
        $this->revirSonuc = 'ayakta';
        unset($this->revirKayitlari);
        Notification::make()->title('Revir protokol kaydı eklendi')->success()->send();
    }

    public function revirSil(int $id): void
    {
        if (! $this->firma) {
            return;
        }
        \App\Models\RevirKaydi::where('firma_id', $this->firma->id)->whereKey($id)->delete();
        unset($this->revirKayitlari);
    }

    public function protokolDefteriPdf()
    {
        if (! $this->firma) {
            return null;
        }
        $kayitlar = \App\Models\RevirKaydi::where('firma_id', $this->firma->id)->orderBy('tarih')->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.revir-protokol-defteri', [
            'firma' => $this->firma,
            'kayitlar' => $kayitlar,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(fn () => print($pdf->output()), 'protokol-defteri-'.\Illuminate\Support\Str::slug($this->firma->unvan ?: 'firma').'.pdf');
    }

    #[Computed]
    public function eczaKalemleri()
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('ecza_dolabi_kalemleri')) {
            return collect();
        }

        return \App\Models\EczaDolabiKalemi::query()
            ->where('firma_id', $this->firma->id)
            ->orderByRaw('skt IS NULL, skt ASC')
            ->orderBy('malzeme_adi')
            ->get();
    }

    public function eczaEkle(): void
    {
        if (! $this->firma || trim($this->eczaAd) === '') {
            Notification::make()->title('Malzeme / ilaç adı zorunludur')->warning()->send();

            return;
        }

        \App\Models\EczaDolabiKalemi::create([
            'firma_id' => $this->firma->id,
            'malzeme_adi' => trim($this->eczaAd),
            'kategori' => array_key_exists($this->eczaKategori, \App\Models\EczaDolabiKalemi::KATEGORILER) ? $this->eczaKategori : 'pansuman',
            'miktar' => max(0, $this->eczaMiktar),
            'kritik_miktar' => max(0, $this->eczaKritik),
            'birim' => trim($this->eczaBirim) ?: 'Adet',
            'skt' => $this->eczaSkt ?: null,
            'konum' => trim($this->eczaKonum) ?: null,
        ]);

        $this->reset('eczaAd', 'eczaSkt');
        $this->eczaMiktar = 10;
        unset($this->eczaKalemleri);
        Notification::make()->title('Ecza dolabı kalemi eklendi')->success()->send();
    }

    public function eczaMiktarDegistir(int $id, int $fark): void
    {
        if (! $this->firma) {
            return;
        }
        $k = \App\Models\EczaDolabiKalemi::where('firma_id', $this->firma->id)->find($id);
        if ($k) {
            $k->update(['miktar' => max(0, (int) $k->miktar + $fark)]);
            unset($this->eczaKalemleri);
        }
    }

    public function eczaSil(int $id): void
    {
        if (! $this->firma) {
            return;
        }
        \App\Models\EczaDolabiKalemi::where('firma_id', $this->firma->id)->whereKey($id)->delete();
        unset($this->eczaKalemleri);
    }

    #[Computed]
    public function asiKayitlari()
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('asi_kayitlari')) {
            return collect();
        }

        return \App\Models\AsiKaydi::query()
            ->where('firma_id', $this->firma->id)
            ->latest('yapilma_tarihi')
            ->latest('id')
            ->get();
    }

    public function asiEkle(): void
    {
        if (! $this->firma) {
            return;
        }
        $calisan = $this->asiCalisanId ? $this->calisanlar->firstWhere('id', $this->asiCalisanId) : null;
        $ad = trim($calisan?->ad_soyad ?: $this->asiAdSoyad);
        if ($ad === '') {
            Notification::make()->title('Çalışan adı zorunludur')->warning()->send();

            return;
        }

        $kullanici = Filament::auth()->user();
        $klinikKullaniciAdi = ($kullanici instanceof \App\Models\User && in_array($kullanici->unvan, ['isyeri_hekimi', 'dsp'], true))
            ? $kullanici->name
            : null;

        \App\Models\AsiKaydi::create([
            'firma_id' => $this->firma->id,
            'calisan_id' => $calisan?->id,
            'calisan_ad_soyad' => $ad,
            'asi_turu' => $this->asiTur ?: 'Tetanoz',
            'doz' => $this->asiDoz ?: '1. Doz',
            'yapilma_tarihi' => $this->asiTarih ?: now()->toDateString(),
            'sonraki_doz_tarihi' => $this->asiSonrakiTarih ?: null,
            'uygulayan' => $this->firma->isyeriHekimi?->ad_soyad ?: $this->firma->dsp?->ad_soyad ?: $klinikKullaniciAdi,
        ]);

        $this->reset('asiCalisanId', 'asiAdSoyad', 'asiSonrakiTarih');
        unset($this->asiKayitlari);
        Notification::make()->title('Aşı kaydı eklendi')->success()->send();
    }

    public function asiSil(int $id): void
    {
        if (! $this->firma) {
            return;
        }
        \App\Models\AsiKaydi::where('firma_id', $this->firma->id)->whereKey($id)->delete();
        unset($this->asiKayitlari);
    }

    private function satirYap(Calisan $c, string $tur): array
    {
        return [
            'calisan_id' => $c->id,
            'calisan_adi' => $c->ad_soyad,
            'gorev' => $c->gorev,
            'tetkik_turu' => $tur,
            'tarih' => null,
            'sonraki_tarih' => null,
            'sonuc' => 'bekliyor',
            'rapor_no' => null,
            'not' => null,
            'kursun_deger' => null,
        ];
    }

    public function satirEkle(): void
    {
        $c = $this->calisanlar->firstWhere('id', $this->yeniCalisanId);

        if (! $c) {
            Notification::make()->title('Çalışan seçin')->danger()->send();

            return;
        }

        $zaten = collect($this->satirlar)->contains(
            fn (array $s) => (int) $s['calisan_id'] === $c->id && $s['tetkik_turu'] === $this->yeniTur,
        );

        if ($zaten) {
            Notification::make()->title('Bu çalışan + tetkik zaten listede')->warning()->send();

            return;
        }

        $this->satirlar[] = $this->satirYap($c, $this->yeniTur);
    }

    /** Seçili tetkik türünü firmanın TÜM aktif çalışanlarına ekle (zaten olanı atla). */
    public function tumCalisanlaraEkle(): void
    {
        $mevcut = collect($this->satirlar);
        $eklenen = 0;

        foreach ($this->calisanlar as $c) {
            $zaten = $mevcut->contains(fn (array $s) => (int) $s['calisan_id'] === $c->id && $s['tetkik_turu'] === $this->yeniTur);

            if (! $zaten) {
                $this->satirlar[] = $this->satirYap($c, $this->yeniTur);
                $eklenen++;
            }
        }

        Notification::make()->title($eklenen.' satır eklendi')->success()->send();
    }

    public function satirSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $gozetim = $this->gozetim();

        if (! $gozetim) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $satirlar = array_map(function (array $s): array {
            if (empty($s['tarih'])) {
                $s['sonraki_tarih'] = null;
            }

            return $s;
        }, $this->satirlar);

        $gozetim->update(['satirlar' => $satirlar, 'genel_not' => $this->genelNot]);
        $this->satirlar = $gozetim->fresh()->satirlar ?? [];

        if (! $sessiz) {
            Notification::make()->title('Sağlık gözetimi listesi kaydedildi')->success()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null && $this->sekme === 'tetkik')
                ->action(fn () => $this->kaydet()),

            Action::make('pdf')
                ->label('PDF (Takip Çizelgesi)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->sekme === 'tetkik' && filled($this->gozetim()?->satirlar))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return SaglikGozetimiUretici::pdf($this->gozetim());
                }),
        ];
    }
}
