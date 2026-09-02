<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\EgitimKatilim as EgitimKatilimModel;
use App\Models\Firma;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\KatilimciExcelOkuyucu;
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
 * Eğitim Katılım Formu — isgpratik EĞİTİM ekranları. "İş Sağlığı ve Güvenliği"
 * seçilince Genel/Sağlık/Teknik/İşyerine Özgü Riskler 4 bloğu birden gösterilir;
 * diğer başlıklar tek bloklu sabit içerikle gelir. Her "Form PDF" tıklaması
 * yeni bir kayıt (belge) oluşturur.
 */
class EgitimKatilim extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.egitim-katilim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'egitim-katilim';

    protected static ?string $title = 'Eğitim Katılım Formu';

    protected static ?string $navigationLabel = 'Eğitim Katılım';

    public ?int $firmaId = null;

    public string $baslikAnahtari = 'genel';

    public ?string $sektorAnahtari = null;

    public ?string $egitimYeri = null;

    public ?string $belgeTarihi = null;

    public int $sureGun = 1;

    public bool $isgUzmaniVar = true;

    public bool $isyeriHekimiVar = false;

    public ?string $isyeriHekimiAdi = null;

    /** @var array<int, int> katılımcı olarak dahil edilen firma çalışanı id'leri */
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

    public function mount(): void
    {
        $this->belgeTarihi = now()->toDateString();

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
    public function basliklar(): array
    {
        return EgitimIcerikOlusturucu::basliklar();
    }

    #[Computed]
    public function sektorler(): array
    {
        return EgitimIcerikOlusturucu::sektorler();
    }

    #[Computed]
    public function icerik(): array
    {
        return EgitimIcerikOlusturucu::olustur(
            $this->baslikAnahtari,
            $this->sektorAnahtari,
            $this->firma?->tehlike_sinifi ?? 'az_tehlikeli',
        );
    }

    /** @return Collection<int, EgitimKatilimModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->egitimKatilimlari()->latest('belge_tarihi')->latest()->get() ?? collect();
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
    }

    public function updatedBaslikAnahtari(): void
    {
        if ($this->baslikAnahtari !== 'genel') {
            $this->sektorAnahtari = null;
        }

        unset($this->icerik);
    }

    public function updatedSektorAnahtari(): void
    {
        unset($this->icerik);
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

    private function kaydet(): ?EgitimKatilimModel
    {
        if (! $this->firma || ! $this->belgeTarihi) {
            Notification::make()->title('Firma ve tarih zorunlu')->danger()->send();

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

        $kayit = new EgitimKatilimModel([
            'firma_id' => $this->firma->id,
            'baslik_anahtari' => $this->baslikAnahtari,
            'sektor_anahtari' => $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null,
            'egitim_yeri' => $this->egitimYeri,
            'belge_tarihi' => $this->belgeTarihi,
            'sure_gun' => $this->sureGun,
            'isg_uzmani_var' => $this->isgUzmaniVar,
            'isyeri_hekimi_var' => $this->isyeriHekimiVar,
            'isyeri_hekimi_adi' => $this->isyeriHekimiVar ? $this->isyeriHekimiAdi : null,
            'konu_secimleri' => $this->icerik,
            'katilimcilar' => $this->katilimcilarTopla(),
        ]);
        $kayit->save();

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Form PDF (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::pdf($kayit);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        return $kayit ? EgitimKatilimUretici::pdf($kayit) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->egitimKatilimlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
