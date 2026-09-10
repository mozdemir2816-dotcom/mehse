<?php

namespace App\Filament\Pages;

use App\Models\AcilDurumKrokisi as KrokiModel;
use App\Models\Firma;
use App\Support\AcilDurumKrokisiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Acil Durum / Tahliye Krokisi Düzenleyici — isgpratik'in "Acil Durum & Tahliye
 * Krokisi Düzenleyici" (kroki-editor) sayfasının küçültülmüş ilk sürümü: SVG
 * tuvali üzerinde 90°'ye kenetlenen duvar çizgileri + 8 sembollük çekirdek
 * palet (tam ISO 7010 kütüphanesi değil) + isteğe bağlı mimari plan altlığı.
 * Sürükleme YOK — tıkla-yerleştir / tıkla-seç-sil (bkz. MIMARI "Kalan").
 */
class AcilDurumKrokisi extends Page
{
    protected string $view = 'filament.pages.acil-durum-krokisi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'acil-durum-krokisi';

    protected static ?string $title = 'Acil Durum Krokisi';

    protected static ?string $navigationLabel = 'Acil Durum Krokisi';

    public ?int $firmaId = null;

    /** @var array<int, array{x1:float,y1:float,x2:float,y2:float}> */
    public array $duvarlar = [];

    /** @var array<int, array{tip:string,x:float,y:float,etiket:?string}> */
    public array $semboller = [];

    public ?string $hazirlanmaTarihi = null;

    public string $aktifArac = 'sembol';

    public string $aktifSembol = 'cikis';

    /** Duvar çizerken ilk tıklanan nokta; ikinci tıklamada çizgi tamamlanır. */
    public ?array $bekleyenNokta = null;

    public function mount(): void
    {
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

    public function kroki(): ?KrokiModel
    {
        return $this->firma ? KrokiModel::firmaIcin($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma);
        $this->bekleyenNokta = null;

        $kroki = $this->kroki();

        if (! $kroki) {
            $this->duvarlar = [];
            $this->semboller = [];
            $this->hazirlanmaTarihi = null;

            return;
        }

        $this->duvarlar = $kroki->duvarlar ?? [];
        $this->semboller = $kroki->semboller ?? [];
        $this->hazirlanmaTarihi = $kroki->hazirlanma_tarihi?->toDateString();
    }

    public function aracSec(string $arac): void
    {
        $this->aktifArac = $arac;
        $this->bekleyenNokta = null;
    }

    public function semboSec(string $tip): void
    {
        $this->aktifSembol = $tip;
        $this->aktifArac = 'sembol';
    }

    /** Tuval üzerine tıklanınca JS'ten SVG koordinatı gelir. */
    public function nokta(float $x, float $y): void
    {
        if (! $this->firma) {
            return;
        }

        if ($this->aktifArac === 'sembol') {
            $this->semboller[] = ['tip' => $this->aktifSembol, 'x' => round($x), 'y' => round($y), 'etiket' => null];

            return;
        }

        if ($this->aktifArac === 'duvar') {
            if (! $this->bekleyenNokta) {
                $this->bekleyenNokta = ['x' => round($x), 'y' => round($y)];

                return;
            }

            $x1 = $this->bekleyenNokta['x'];
            $y1 = $this->bekleyenNokta['y'];
            $x2 = round($x);
            $y2 = round($y);

            // 90°'ye kenetleme: yatay hareket daha büyükse tamamen yatay, değilse tamamen dikey.
            if (abs($x2 - $x1) >= abs($y2 - $y1)) {
                $y2 = $y1;
            } else {
                $x2 = $x1;
            }

            if ($x1 !== $x2 || $y1 !== $y2) {
                $this->duvarlar[] = ['x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2];
            }

            $this->bekleyenNokta = null;
        }
    }

    public function duvarSil(int $index): void
    {
        unset($this->duvarlar[$index]);
        $this->duvarlar = array_values($this->duvarlar);
    }

    public function semboSil(int $index): void
    {
        unset($this->semboller[$index]);
        $this->semboller = array_values($this->semboller);
    }

    public function temizle(): void
    {
        $this->duvarlar = [];
        $this->semboller = [];
        $this->bekleyenNokta = null;
    }

    public function kaydet(): void
    {
        $kroki = $this->kroki();

        if (! $kroki) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $kroki->forceFill([
            'duvarlar' => $this->duvarlar,
            'semboller' => $this->semboller,
            'hazirlanma_tarihi' => $this->hazirlanmaTarihi ?: now(),
        ])->save();

        Notification::make()->title('Kroki kaydedildi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('arkaPlan')
                ->label('Plan Altlığı Yükle')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->fillForm(fn (): array => ['arka_plan_gorseli' => $this->kroki()?->arka_plan_gorseli])
                ->schema([
                    FileUpload::make('arka_plan_gorseli')
                        ->label('Mimari plan / kat planı görseli')
                        ->image()
                        ->disk('public')->directory('acil-durum-kroki-arkaplan')->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    $this->kroki()?->forceFill($data)->save();
                    Notification::make()->title('Plan altlığı kaydedildi')->success()->send();
                }),

            Action::make('pdf')
                ->label('Kroki PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $this->kaydet();

                    return AcilDurumKrokisiUretici::pdf($this->kroki());
                }),
        ];
    }

    public function arkaPlanUrl(): ?string
    {
        $yol = $this->kroki()?->arka_plan_gorseli;

        return $yol ? Storage::disk('public')->url($yol) : null;
    }
}
