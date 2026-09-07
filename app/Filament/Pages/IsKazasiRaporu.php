<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsKazasiRaporu as IsKazasiRaporuModel;
use App\Support\IsKazasiRaporuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * İş Kazası Raporu — 6331 s.K. ve standart kaza inceleme raporu formatı
 * (5N1K + kök neden analizi). isgpratik'te ilgili ekran görüntüsü yok
 * (planNotu'ndaki 16.jpg mevcut değil); genel kabul görmüş kaza inceleme
 * raporu yapısına göre kuruldu.
 */
class IsKazasiRaporu extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.is-kazasi-raporu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 24;

    protected static ?string $slug = 'is-kazasi-raporu';

    protected static ?string $title = 'İş Kazası Raporu';

    protected static ?string $navigationLabel = 'İş Kazası Raporu';

    public ?int $firmaId = null;

    public ?int $kazazedeHizliSecId = null;

    public ?string $kazazedeAdSoyad = null;

    public ?string $kazazedeTc = null;

    public ?string $kazazedeGorev = null;

    public ?string $kazaTarihi = null;

    public ?string $kazaSaati = null;

    public ?string $kazaYeri = null;

    public ?string $kazaTuru = null;

    public ?string $agirlikDerecesi = null;

    public ?int $kayipGunSayisi = null;

    public ?string $kazaTanimi = null;

    public ?string $kazaNasilOldu = null;

    /** @var array<int, string> */
    public array $kokNedenKategorileri = [];

    public ?string $kazaNedeni = null;

    public ?string $alinanOnlemler = null;

    /** @var array<int, array{ad_soyad: string, gorev: ?string}> */
    public array $taniklar = [];

    public ?string $yeniTanikAd = null;

    public ?string $yeniTanikGorev = null;

    public bool $sgkBildirimiYapildi = false;

    public ?string $sgkBildirimTarihi = null;

    public ?string $raporHazirlayan = null;

    /**
     * Olay yeri / kaza sonrası durumun fotoğraf kanıtları.
     *
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>
     */
    public array $yeniFotograflar = [];

    public function mount(): void
    {
        $this->kazaTarihi = now()->toDateString();

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
    public function kazaTurleri(): array
    {
        return config('isg.is_kazasi.kaza_turleri');
    }

    #[Computed]
    public function agirlikDereceleri(): array
    {
        return config('isg.is_kazasi.agirlik_dereceleri');
    }

    #[Computed]
    public function kokNedenSecenekleri(): array
    {
        return config('isg.is_kazasi.kok_neden_kategorileri');
    }

    /** @return Collection<int, IsKazasiRaporuModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->isKazasiRaporlari()->latest('kaza_tarihi')->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->raporHazirlayan = $this->firma?->igu?->ad_soyad;
    }

    public function updatedKazazedeHizliSecId(): void
    {
        $c = $this->kazazedeHizliSecId ? $this->calisanlar->firstWhere('id', $this->kazazedeHizliSecId) : null;

        $this->kazazedeAdSoyad = $c?->ad_soyad;
        $this->kazazedeTc = $c?->tc;
        $this->kazazedeGorev = $c?->gorev;
    }

    public function tanikEkle(): void
    {
        if (blank($this->yeniTanikAd)) {
            return;
        }

        $this->taniklar[] = ['ad_soyad' => $this->yeniTanikAd, 'gorev' => $this->yeniTanikGorev];
        $this->reset('yeniTanikAd', 'yeniTanikGorev');
    }

    public function tanikSil(int $index): void
    {
        unset($this->taniklar[$index]);
        $this->taniklar = array_values($this->taniklar);
    }

    public function fotoSil(int $index): void
    {
        unset($this->yeniFotograflar[$index]);
        $this->yeniFotograflar = array_values($this->yeniFotograflar);
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?IsKazasiRaporuModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->kazazedeAdSoyad) || blank($this->kazaTanimi)) {
            Notification::make()->title('Kazazede adı ve kaza tanımı zorunlu')->danger()->send();

            return null;
        }

        $r = new IsKazasiRaporuModel([
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->kazazedeHizliSecId,
            'kazazede_ad_soyad' => $this->kazazedeAdSoyad,
            'kazazede_tc' => $this->kazazedeTc,
            'kazazede_gorev' => $this->kazazedeGorev,
            'kaza_tarihi' => $this->kazaTarihi,
            'kaza_saati' => $this->kazaSaati,
            'kaza_yeri' => $this->kazaYeri,
            'kaza_turu' => $this->kazaTuru,
            'agirlik_derecesi' => $this->agirlikDerecesi,
            'kayip_gun_sayisi' => $this->kayipGunSayisi,
            'kaza_tanimi' => $this->kazaTanimi,
            'kaza_nasil_oldu' => $this->kazaNasilOldu,
            'kok_neden_kategorileri' => $this->kokNedenKategorileri,
            'kaza_nedeni' => $this->kazaNedeni,
            'alinan_onlemler' => $this->alinanOnlemler,
            'taniklar' => $this->taniklar,
            'fotograflar' => collect($this->yeniFotograflar)->map(fn ($f) => $f->store('is-kazasi-foto', 'public'))->all(),
            'sgk_bildirimi_yapildi' => $this->sgkBildirimiYapildi,
            'sgk_bildirim_tarihi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimTarihi : null,
            'rapor_hazirlayan' => $this->raporHazirlayan,
            'rapor_hazirlayan_kase' => $this->firma->igu?->kase_gorseli,
        ]);
        $r->save();

        unset($this->gecmisKayitlar);

        return $r;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Raporu Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $r = $this->kaydet();

                    if (! $r) {
                        return null;
                    }

                    Notification::make()->title('İş kazası raporu kaydedildi')->body($r->belge_no)->success()->send();

                    return IsKazasiRaporuUretici::pdf($r);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $r = $this->firma?->isKazasiRaporlari()->find($id);

        return $r ? IsKazasiRaporuUretici::pdf($r) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->isKazasiRaporlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
