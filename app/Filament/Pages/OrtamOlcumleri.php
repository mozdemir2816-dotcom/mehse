<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\OrtamOlcumu as OrtamOlcumuModel;
use App\Support\OrtamOlcumuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İş Hijyeni / Ortam Ölçümleri Takibi — işyerinde yapılması gereken ölçümler
 * (gürültü, toz, aydınlatma, termal konfor, kimyasal maruziyet vb.) listelenir;
 * her ölçüm için tarih, laboratuvar, ölçülen/sınır değer ve sonuç girilir.
 * Bir sonraki ölçüm tarihi periyoda göre otomatik hesaplanır.
 */
class OrtamOlcumleri extends Page
{
    protected string $view = 'filament.pages.ortam-olcumleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'ortam-olcumleri';

    protected static ?string $title = 'Ortam Ölçümleri Takibi';

    protected static ?string $navigationLabel = 'Ortam Ölçümleri';

    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    // Serbest ölçüm ekleme
    public ?string $yeniParametre = null;

    public ?string $yeniGrup = null;

    public int $yeniPeriyot = 24;

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
    public function olcum(): ?OrtamOlcumuModel
    {
        return $this->firma ? OrtamOlcumuModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function katalog(): array
    {
        return config('isg.ortam_olcum.katalog', []);
    }

    #[Computed]
    public function sonuclar(): array
    {
        return config('isg.ortam_olcum.sonuclar', []);
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->olcum);

        $olcum = $this->olcum();
        $this->satirlar = $olcum?->olcumler ?? [];
        $this->genelNot = $olcum?->genel_not;
    }

    public function katalogdanEkle(string $grup, string $ad): void
    {
        $madde = collect(config('isg.ortam_olcum.katalog.'.$grup, []))->firstWhere('ad', $ad);

        $this->satirlar[] = [
            'parametre' => $ad,
            'grup' => $grup,
            'bolge' => null,
            'periyot_ay' => $madde['periyot_ay'] ?? 24,
            'olcum_tarihi' => null,
            'sonraki_olcum_tarihi' => null,
            'laboratuvar' => null,
            'rapor_no' => null,
            'olculen_deger' => null,
            'sinir_deger' => $madde['sinir_deger'] ?? null,
            'birim' => $madde['birim'] ?? null,
            'sonuc' => 'bekliyor',
            'not' => null,
        ];
    }

    public function serbestOlcumEkle(): void
    {
        if (blank($this->yeniParametre)) {
            Notification::make()->title('Ölçüm parametresi gerekli')->danger()->send();

            return;
        }

        $this->satirlar[] = [
            'parametre' => $this->yeniParametre,
            'grup' => $this->yeniGrup ?: 'Diğer',
            'bolge' => null,
            'periyot_ay' => max(1, $this->yeniPeriyot),
            'olcum_tarihi' => null,
            'sonraki_olcum_tarihi' => null,
            'laboratuvar' => null,
            'rapor_no' => null,
            'olculen_deger' => null,
            'sinir_deger' => null,
            'birim' => null,
            'sonuc' => 'bekliyor',
            'not' => null,
        ];

        $this->reset('yeniParametre', 'yeniGrup', 'yeniPeriyot');
        $this->yeniPeriyot = 24;
    }

    public function olcumSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $olcum = $this->olcum();

        if (! $olcum) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        // Ölçüm tarihi temizlendiyse "sonraki" yeniden hesaplansın diye sıfırla.
        $satirlar = array_map(function (array $s): array {
            if (empty($s['olcum_tarihi'])) {
                $s['sonraki_olcum_tarihi'] = null;
            }

            return $s;
        }, $this->satirlar);

        $olcum->update(['olcumler' => $satirlar, 'genel_not' => $this->genelNot]);

        $this->satirlar = $olcum->fresh()->olcumler ?? [];

        if (! $sessiz) {
            Notification::make()->title('Ortam ölçümleri listesi kaydedildi')->success()->send();
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
                ->label('PDF (Takip Listesi)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->olcum()?->olcumler))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return OrtamOlcumuUretici::pdf($this->olcum());
                }),
        ];
    }
}
