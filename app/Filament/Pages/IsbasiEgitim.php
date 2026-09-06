<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi as IsbasiEgitimTutanagiModel;
use App\Support\IsbasiEgitimTutanagiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İşbaşı / Oryantasyon Eğitim Tutanağı — isgpratik 60.jpg. İşe yeni başlayan
 * her çalışan için ayrı, tek sayfalık tutanak.
 */
class IsbasiEgitim extends Page
{
    protected string $view = 'filament.pages.isbasi-egitim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'isbasi-egitim';

    protected static ?string $title = 'İşbaşı Eğitim Tutanağı';

    protected static ?string $navigationLabel = 'İşbaşı Eğt. Tutanağı';

    public ?int $firmaId = null;

    public ?int $calisanHizliSecId = null;

    public ?string $calisanAdSoyad = null;

    public ?string $calisanTc = null;

    public bool $tcGizli = false;

    public ?string $egitimTarihi = null;

    public ?int $sureSaat = 2;

    public ?string $egitimYeri = null;

    public ?string $egitimiVeren = null;

    public string $egitimYontemi = 'Uygulamalı';

    public ?string $belgeTarihi = null;

    public bool $iguImzasi = true;

    public bool $isyeriHekimiImzasi = false;

    /** @var array<int, string> */
    public array $secilenKonular = [];

    public function mount(): void
    {
        $this->egitimTarihi = now()->toDateString();
        $this->belgeTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
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

    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function konuKategorileri(): array
    {
        return config('isg.isbasi_egitim.konu_kategorileri');
    }

    #[Computed]
    public function egitimYontemleri(): array
    {
        return config('isg.isbasi_egitim.egitim_yontemleri');
    }

    #[Computed]
    public function toplamMaddeSayisi(): int
    {
        return collect($this->konuKategorileri())->flatten()->count();
    }

    /** @return Collection<int, IsbasiEgitimTutanagiModel> */
    #[Computed]
    public function gecmisTutanaklar(): Collection
    {
        return $this->firma?->isbasiEgitimTutanaklari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisTutanaklar);
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
    }

    /*
    |--------------------------------------------------------------------------
    | Konu seçimi
    |--------------------------------------------------------------------------
    */

    public function konuToggle(string $madde): void
    {
        $this->secilenKonular = in_array($madde, $this->secilenKonular, true)
            ? array_values(array_diff($this->secilenKonular, [$madde]))
            : [...$this->secilenKonular, $madde];
    }

    public function tumKonular(bool $sec): void
    {
        $this->secilenKonular = $sec ? collect($this->konuKategorileri())->flatten()->all() : [];
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?IsbasiEgitimTutanagiModel
    {
        if (! $this->firma || blank($this->calisanAdSoyad)) {
            Notification::make()->title('Firma ve çalışan adı zorunlu')->danger()->send();

            return null;
        }

        $t = new IsbasiEgitimTutanagiModel([
            'firma_id' => $this->firma->id,
            'calisan_ad_soyad' => $this->calisanAdSoyad,
            'calisan_tc' => $this->calisanTc,
            'tc_gizli' => $this->tcGizli,
            'egitim_tarihi' => $this->egitimTarihi,
            'sure_saat' => $this->sureSaat,
            'egitim_yeri' => $this->egitimYeri,
            'egitimi_veren' => $this->egitimiVeren,
            'egitim_yontemi' => $this->egitimYontemi,
            'belge_tarihi' => $this->belgeTarihi,
            'igu_imzasi' => $this->iguImzasi,
            'isyeri_hekimi_imzasi' => $this->isyeriHekimiImzasi,
            'konular' => $this->secilenKonular,
        ]);
        $t->save();

        unset($this->gecmisTutanaklar);

        return $t;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $t = $this->kaydet();

                    return $t ? IsbasiEgitimTutanagiUretici::pdf($t) : null;
                }),

            Action::make('katilimFormu')
                ->label('Katılım Formu (Toplu)')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->tooltip('Birden fazla çalışanın aynı eğitime katılımını tek sayfada imzalatmak için')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => IsbasiEgitimTutanagiUretici::katilimFormuPdf($this->firma, [
                    'egitim_tarihi' => $this->egitimTarihi,
                    'sure_saat' => $this->sureSaat,
                    'egitim_yeri' => $this->egitimYeri,
                    'egitimi_veren' => $this->egitimiVeren,
                    'egitim_yontemi' => $this->egitimYontemi,
                    'belge_tarihi' => $this->belgeTarihi,
                    'igu_imzasi' => $this->iguImzasi,
                    'isyeri_hekimi_imzasi' => $this->isyeriHekimiImzasi,
                    'konular' => $this->secilenKonular,
                    'katilimcilar' => $this->calisanlar->map(fn ($c) => [
                        'ad_soyad' => $c->ad_soyad,
                        'tc' => $c->tc,
                        'gorev' => $c->gorev,
                    ])->all(),
                ])),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $t = $this->firma?->isbasiEgitimTutanaklari()->find($id);

        return $t ? IsbasiEgitimTutanagiUretici::pdf($t) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->isbasiEgitimTutanaklari()->find($id)?->delete();
        unset($this->gecmisTutanaklar);
    }
}
