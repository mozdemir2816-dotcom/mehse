<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\KkdMatrisi;
use App\Support\KkdMatrisiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * KKD Seçim Matrisi — iş kalemi × KKD türü. Sektörel katalogdan iş kalemi
 * eklenir (varsayılan KKD gereklilikleriyle) veya serbest satır girilir; her
 * hücre elle düzenlenir. Firma bazlı tek kayıt.
 */
class KkdSecimMatrisi extends Page
{
    protected string $view = 'filament.pages.kkd-secim-matrisi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|UnitEnum|null $navigationGroup = 'KKD';

    protected static ?int $navigationSort = 29;

    protected static ?string $slug = 'kkd-secim-matrisi';

    protected static ?string $title = 'KKD Seçim Matrisi';

    protected static ?string $navigationLabel = 'KKD Seçim Matrisi';

    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    public ?string $yeniIsKalemi = null;

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

    #[Computed]
    public function matris(): ?KkdMatrisi
    {
        return $this->firma ? KkdMatrisi::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function sutunlar(): array
    {
        return config('isg.kkd_matris.sutunlar', []);
    }

    #[Computed]
    public function katalog(): array
    {
        return config('isg.kkd_matris.is_kalemleri', []);
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->matris);

        $matris = $this->matris();
        $this->satirlar = $matris?->satirlar ?? [];
        $this->genelNot = $matris?->genel_not;
    }

    public function katalogdanEkle(string $grup, string $ad): void
    {
        $madde = collect(config('isg.kkd_matris.is_kalemleri.'.$grup, []))->firstWhere('ad', $ad);
        $degerler = $madde['v'] ?? [];

        $satir = ['is_kalemi' => $ad, 'grup' => $grup];
        foreach (array_keys($this->sutunlar) as $sutun) {
            $satir[$sutun] = $degerler[$sutun] ?? '';
        }

        $this->satirlar[] = $satir;
    }

    public function serbestEkle(): void
    {
        if (blank($this->yeniIsKalemi)) {
            Notification::make()->title('İş kalemi adı gerekli')->danger()->send();

            return;
        }

        $satir = ['is_kalemi' => $this->yeniIsKalemi, 'grup' => 'Serbest'];
        foreach (array_keys($this->sutunlar) as $sutun) {
            $satir[$sutun] = '';
        }

        $this->satirlar[] = $satir;
        $this->reset('yeniIsKalemi');
    }

    public function satirSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $matris = $this->matris();

        if (! $matris) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $matris->update(['satirlar' => $this->satirlar, 'genel_not' => $this->genelNot]);
        $this->satirlar = $matris->fresh()->satirlar ?? [];

        if (! $sessiz) {
            Notification::make()->title('KKD seçim matrisi kaydedildi')->success()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->kaydet()),

            Action::make('pdf')
                ->label('PDF Matris')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->matris()?->satirlar))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return KkdMatrisiUretici::pdf($this->matris());
                }),
        ];
    }
}
