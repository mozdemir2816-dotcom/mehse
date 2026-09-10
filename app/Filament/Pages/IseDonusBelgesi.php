<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IseDonusBelgesi as IseDonusBelgesiModel;
use App\Support\IseDonusBelgesiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İşe Dönüş Belgesi — uzun süreli rapor / iş kazası / meslek hastalığı sonrası
 * işyeri hekiminin işe dönüş uygunluk değerlendirmesi ve geçici iş kısıtlamaları.
 */
class IseDonusBelgesi extends Page
{
    protected string $view = 'filament.pages.ise-donus-belgesi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|UnitEnum|null $navigationGroup = 'Sağlık Gözetimi';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'ise-donus-belgesi';

    protected static ?string $title = 'İşe Dönüş Belgesi';

    protected static ?string $navigationLabel = 'İşe Dönüş Belgesi';

    public ?int $firmaId = null;

    public ?int $calisanHizliSecId = null;

    public ?string $calisanAdSoyad = null;

    public ?string $calisanTc = null;

    public ?string $gorev = null;

    public string $neden = 'hastalik';

    public ?string $devamsizlikBaslangic = null;

    public ?string $devamsizlikBitis = null;

    public ?string $iseDonusTarihi = null;

    public string $uygunluk = 'tam';

    /** @var array<int, string> */
    public array $kisitlamalar = [];

    public ?string $hekimGorusu = null;

    public ?string $kontrolMuayeneTarihi = null;

    public ?string $hekimAdi = null;

    public function mount(): void
    {
        $this->iseDonusTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
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
    public function nedenler(): array
    {
        return config('isg.ise_donus.nedenler');
    }

    #[Computed]
    public function uygunlukSecenekleri(): array
    {
        return config('isg.ise_donus.uygunluk');
    }

    #[Computed]
    public function kisitlamaKutuphanesi(): array
    {
        return config('isg.ise_donus.kisitlama_kutuphanesi');
    }

    /** @return Collection<int, IseDonusBelgesiModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->iseDonusBelgeleri()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->hekimAdi = $this->firma?->isyeriHekimi?->ad_soyad;
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
        $this->gorev = $c?->gorev;
    }

    private function kaydet(): ?IseDonusBelgesiModel
    {
        if (! $this->firma || blank($this->calisanAdSoyad)) {
            Notification::make()->title('Firma ve çalışan adı zorunlu')->danger()->send();

            return null;
        }

        $b = IseDonusBelgesiModel::create([
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->calisanHizliSecId,
            'calisan_ad_soyad' => $this->calisanAdSoyad,
            'calisan_tc' => $this->calisanTc,
            'gorev' => $this->gorev,
            'neden' => $this->neden,
            'devamsizlik_baslangic' => $this->devamsizlikBaslangic ?: null,
            'devamsizlik_bitis' => $this->devamsizlikBitis ?: null,
            'ise_donus_tarihi' => $this->iseDonusTarihi ?: null,
            'uygunluk' => $this->uygunluk,
            'kisitlamalar' => $this->uygunluk === 'kisitli' || $this->uygunluk === 'gecici_gorev' ? array_values($this->kisitlamalar) : [],
            'hekim_gorusu' => $this->hekimGorusu,
            'kontrol_muayene_tarihi' => $this->kontrolMuayeneTarihi ?: null,
            'hekim_adi' => $this->hekimAdi ?: $this->firma->isyeriHekimi?->ad_soyad,
            'hekim_kase' => $this->firma->isyeriHekimi?->kase_gorseli,
        ]);

        unset($this->gecmisKayitlar);

        return $b;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Belgeyi Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $b = $this->kaydet();

                    if (! $b) {
                        return null;
                    }

                    Notification::make()->title('İşe dönüş belgesi kaydedildi')->body($b->belge_no)->success()->send();

                    return IseDonusBelgesiUretici::pdf($b);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $b = $this->firma?->iseDonusBelgeleri()->find($id);

        return $b ? IseDonusBelgesiUretici::pdf($b) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->iseDonusBelgeleri()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
