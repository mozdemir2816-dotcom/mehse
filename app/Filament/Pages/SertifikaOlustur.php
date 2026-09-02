<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\Sertifika;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\KatilimciExcelOkuyucu;
use App\Support\SertifikaUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Sertifika Oluştur — isgpratik 66-68.jpg. 4 tip (İSG / Yüksekte Çalışma /
 * Kapalı Alan / Yangın); İSG tipi çoklu eğitici (İGU + İşyeri Hekimi, isg.egitim
 * 'genel' içeriği), diğerleri tek eğitici + sabit özel başlık içeriği. Eğitici
 * kaşesi firmaya atanmış İSG Profesyoneli'nden otomatik gelir. Katılımcı
 * başına PDF'te ayrı bir sertifika sayfası üretilir.
 */
class SertifikaOlustur extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.sertifika-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 19;

    protected static ?string $slug = 'sertifika';

    protected static ?string $title = 'Sertifika Oluştur';

    protected static ?string $navigationLabel = 'Sertifika Oluştur';

    public ?int $firmaId = null;

    public string $tip = 'isg';

    public string $tur = 'ilk_defa';

    public string $sekil = 'yuz_yuze';

    public ?string $sektorAnahtari = null;

    public int $gunSayisi = 1;

    /** @var array<int, ?string> */
    public array $egitimTarihleri = [null];

    public ?string $gecerlilikTarihi = null;

    public ?string $sureMetni = null;

    public bool $egiticiIguDahil = true;

    public ?string $egiticiIguAdi = null;

    public bool $egiticiHekimDahil = false;

    public ?string $egiticiHekimAdi = null;

    public string $logoKonumu = 'sol';

    public string $cerceve = 'klasik_siyah';

    /** @var array<int, int> */
    public array $secilenCalisanIdler = [];

    /** @var array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public array $manuelKatilimcilar = [];

    public bool $elleEklenenleriFirmayaKaydet = false;

    public ?string $yeniAdSoyad = null;

    public ?string $yeniTc = null;

    public ?string $yeniGorev = null;

    public ?UploadedFile $excelDosya = null;

    /** @var array<int, string> */
    public array $excelHatalar = [];

    /** @var array<string, mixed> EgitimIcerikOlusturucu çıktısı — kullanıcı her maddeyi dahil/hariç bırakıp dakikasını değiştirebilir. */
    public array $icerik = [];

    public function mount(): void
    {
        $this->icerikYenile();

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

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function tipler(): array
    {
        return config('isg.sertifika.tipler');
    }

    #[Computed]
    public function tipTanimi(): array
    {
        return config('isg.sertifika.tipler.'.$this->tip, []);
    }

    #[Computed]
    public function cokluEgiticiMi(): bool
    {
        return (bool) ($this->tipTanimi()['coklu_egitici'] ?? false);
    }

    #[Computed]
    public function sektorler(): array
    {
        return EgitimIcerikOlusturucu::sektorler();
    }

    private function icerikYenile(): void
    {
        $anahtar = $this->cokluEgiticiMi ? 'genel' : ($this->tipTanimi()['icerik_anahtari'] ?? 'genel');

        $this->icerik = EgitimIcerikOlusturucu::olustur(
            $anahtar,
            $this->cokluEgiticiMi ? $this->sektorAnahtari : null,
            $this->firma?->tehlike_sinifi ?? 'az_tehlikeli',
        );
    }

    /** @return Collection<int, Sertifika> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->sertifikalar()->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
        $this->egiticiIguAdi = $this->firma?->igu?->ad_soyad;
        $this->egiticiHekimAdi = $this->firma?->isyeriHekimi?->ad_soyad;
        $this->icerikYenile();
    }

    public function updatedTip(): void
    {
        unset($this->tipTanimi, $this->cokluEgiticiMi);

        if (! $this->cokluEgiticiMi) {
            $this->sektorAnahtari = null;
            $this->egiticiHekimDahil = false;
        }

        $this->icerikYenile();
    }

    public function updatedSektorAnahtari(): void
    {
        $this->icerikYenile();
    }

    public function updatedGunSayisi(): void
    {
        $this->gunSayisi = max(1, min(10, $this->gunSayisi));
        $this->egitimTarihleri = array_pad(array_slice($this->egitimTarihleri, 0, $this->gunSayisi), $this->gunSayisi, null);
    }

    public function gecerlilikOtomatik(string $tehlikeSinifi): void
    {
        $yil = config('isg.sertifika.gecerlilik_yili.'.$tehlikeSinifi, 1);
        $baz = collect($this->egitimTarihleri)->filter()->last() ?? now()->toDateString();

        $this->gecerlilikTarihi = \Carbon\Carbon::parse($baz)->addYears($yil)->toDateString();
    }

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanIdler = in_array($id, $this->secilenCalisanIdler, true)
            ? array_values(array_diff($this->secilenCalisanIdler, [$id]))
            : [...$this->secilenCalisanIdler, $id];
    }

    public function tumCalisanlar(bool $sec): void
    {
        $this->secilenCalisanIdler = $sec ? $this->calisanlar->pluck('id')->all() : [];
    }

    /*
    |--------------------------------------------------------------------------
    | Katılımcılar — manuel ekleme & Excel toplu yükleme
    |--------------------------------------------------------------------------
    */

    public function manuelEkle(): void
    {
        $this->validate(['yeniAdSoyad' => 'required|string|max:190']);

        $this->manuelKatilimcilar[] = [
            'ad_soyad' => $this->yeniAdSoyad,
            'tc' => $this->yeniTc ?: null,
            'gorev' => $this->yeniGorev ?: null,
        ];

        $this->reset('yeniAdSoyad', 'yeniTc', 'yeniGorev');
    }

    public function manuelCikar(int $index): void
    {
        unset($this->manuelKatilimcilar[$index]);
        $this->manuelKatilimcilar = array_values($this->manuelKatilimcilar);
    }

    public function excelIceAktar(): void
    {
        $this->validate(['excelDosya' => 'required|file|mimes:xlsx,xls,csv']);

        try {
            $sonuc = KatilimciExcelOkuyucu::oku($this->excelDosya->getRealPath());
        } catch (\Throwable $e) {
            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

            return;
        }

        $eklenen = 0;

        foreach ($sonuc['katilimcilar'] as $k) {
            $zaten = collect($this->manuelKatilimcilar)->contains(
                fn ($m) => mb_strtolower(trim($m['ad_soyad'])) === mb_strtolower(trim($k['ad_soyad'])),
            );

            if (! $zaten) {
                $this->manuelKatilimcilar[] = $k;
                $eklenen++;
            }
        }

        $this->excelHatalar = $sonuc['hatalar'];
        $this->excelDosya = null;

        Notification::make()->title($eklenen.' katılımcı eklendi')->success()->send();
    }

    public function excelSablonIndir()
    {
        return KatilimciExcelOkuyucu::sablonIndir();
    }

    /** @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    private function katilimcilarTopla(): array
    {
        $firmaCalisanlari = $this->calisanlar
            ->whereIn('id', $this->secilenCalisanIdler)
            ->map(fn (Calisan $c) => [
                'ad_soyad' => $c->ad_soyad,
                'tc' => $c->tc,
                'gorev' => $c->gorev,
            ])
            ->values()
            ->all();

        return [...$firmaCalisanlari, ...$this->manuelKatilimcilar];
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?Sertifika
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (! $this->katilimcilarTopla()) {
            Notification::make()->title('En az bir katılımcı gerekli')->danger()->send();

            return null;
        }

        if ($this->elleEklenenleriFirmayaKaydet) {
            foreach ($this->manuelKatilimcilar as $k) {
                $anahtar = filled($k['tc'] ?? null)
                    ? ['firma_id' => $this->firma->id, 'tc' => $k['tc']]
                    : ['firma_id' => $this->firma->id, 'ad_soyad' => $k['ad_soyad']];

                Calisan::firstOrCreate($anahtar, [
                    'firma_id' => $this->firma->id,
                    'ad_soyad' => $k['ad_soyad'],
                    'tc' => $k['tc'] ?? null,
                    'gorev' => $k['gorev'] ?? null,
                ]);
            }
        }

        $s = new Sertifika([
            'firma_id' => $this->firma->id,
            'tip' => $this->tip,
            'tur' => $this->tur,
            'sekil' => $this->sekil,
            'sektor_anahtari' => $this->cokluEgiticiMi ? $this->sektorAnahtari : null,
            'gun_sayisi' => $this->gunSayisi,
            'egitim_tarihleri' => $this->egitimTarihleri,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'sure_metni' => $this->sureMetni,
            'egitici_igu_dahil' => $this->cokluEgiticiMi ? $this->egiticiIguDahil : true,
            'egitici_igu_adi' => $this->egiticiIguAdi,
            'egitici_igu_kase' => $this->firma->igu?->kase_gorseli,
            'egitici_hekim_dahil' => $this->cokluEgiticiMi && $this->egiticiHekimDahil,
            'egitici_hekim_adi' => $this->cokluEgiticiMi ? $this->egiticiHekimAdi : null,
            'egitici_hekim_kase' => $this->cokluEgiticiMi && $this->egiticiHekimDahil ? $this->firma->isyeriHekimi?->kase_gorseli : null,
            'logo_konumu' => $this->logoKonumu,
            'cerceve' => $this->cerceve,
            'konu_icerigi' => $this->icerik,
            'katilimcilar' => $this->katilimcilarTopla(),
        ]);
        $s->save();

        unset($this->gecmisKayitlar);

        return $s;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Sertifikayı Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $s = $this->kaydet();

                    if (! $s) {
                        return null;
                    }

                    Notification::make()->title('Sertifika kaydedildi')->body($s->belge_no)->success()->send();

                    return SertifikaUretici::pdf($s);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $s = $this->firma?->sertifikalar()->find($id);

        return $s ? SertifikaUretici::pdf($s) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->sertifikalar()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
