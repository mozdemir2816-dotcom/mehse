<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\KimyasalRiskDegerlendirmesi as KimyasalRiskModel;
use App\Support\KimyasalRiskUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Kimyasal Risk Değerlendirmesi — firmanın kimyasal envanterindeki her ürün için
 * tehlike grubu × kullanım miktarı × uçuculuk'tan kontrol yaklaşımı (COSHH
 * Essentials 1-4) hesaplanır. Kimyasal Sicili envanterinden ürün çekilir.
 */
class KimyasalRiskDegerlendirmesi extends Page
{
    protected string $view = 'filament.pages.kimyasal-risk-degerlendirmesi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'kimyasal-risk-degerlendirmesi';

    protected static ?string $title = 'Kimyasal Risk Değerlendirmesi';

    protected static ?string $navigationLabel = 'Kimyasal Risk Değ.';

    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    public ?string $degerlendirmeTarihi = null;

    public ?int $yeniUrunId = null;

    public ?string $yeniSerbest = null;

    public function mount(): void
    {
        $this->degerlendirmeTarihi = now()->toDateString();

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
    public function kayit(): ?KimyasalRiskModel
    {
        return $this->firma ? KimyasalRiskModel::firmaIcin($this->firma) : null;
    }

    /** @return \Illuminate\Support\Collection */
    #[Computed]
    public function envanter()
    {
        return $this->firma?->kimyasalUrunler()->where('aktif', true)->orderBy('urun_adi')->get() ?? collect();
    }

    #[Computed]
    public function gruplar(): array
    {
        return config('isg.kimyasal_risk.tehlike_gruplari');
    }

    #[Computed]
    public function miktarlar(): array
    {
        return config('isg.kimyasal_risk.miktarlar');
    }

    #[Computed]
    public function ucuculukSecenekleri(): array
    {
        return config('isg.kimyasal_risk.ucuculuk');
    }

    #[Computed]
    public function yaklasimlar(): array
    {
        return config('isg.kimyasal_risk.yaklasimlar');
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kayit, $this->envanter);

        $kayit = $this->kayit();
        $this->satirlar = $kayit?->satirlar ?? [];
        $this->genelNot = $kayit?->genel_not;
        $this->degerlendirmeTarihi = $kayit?->degerlendirme_tarihi?->toDateString() ?? now()->toDateString();
    }

    /** GHS sınıflarından kaba bir tehlike grubu tahmini (kullanıcı düzeltir). */
    private function ghsdenGrup(array $ghs): string
    {
        return match (true) {
            in_array('ghs08', $ghs, true) => 'E',   // KMR / uzun vadeli
            in_array('ghs06', $ghs, true) => 'D',   // akut toksik
            in_array('ghs05', $ghs, true) => 'C',   // aşındırıcı
            in_array('ghs07', $ghs, true) => 'B',   // tahriş / zararlı
            default => 'A',
        };
    }

    private function satirYap(array $ust): array
    {
        return array_merge([
            'kimyasal_urun_id' => null, 'kimyasal_adi' => '', 'kullanim_alani' => null,
            'tehlike_grubu' => 'A', 'deri_goz_yolu' => false, 'cmr' => false,
            'miktar' => 'az', 'ucuculuk' => 'orta', 'maruziyet_yollari' => ['soluma'],
            'kontrol_yaklasimi' => 1, 'alinan_onlemler' => null, 'artik_risk' => 'dusuk', 'not' => null,
        ], $ust);
    }

    public function envanterdenEkle(): void
    {
        $urun = $this->envanter->firstWhere('id', $this->yeniUrunId);

        if (! $urun) {
            Notification::make()->title('Envanterden ürün seçin')->danger()->send();

            return;
        }

        if (collect($this->satirlar)->contains(fn (array $s) => (int) $s['kimyasal_urun_id'] === $urun->id)) {
            Notification::make()->title('Bu ürün zaten listede')->warning()->send();

            return;
        }

        $ghs = $urun->ghs ?? [];
        $this->satirlar[] = $this->satirYap([
            'kimyasal_urun_id' => $urun->id,
            'kimyasal_adi' => $urun->urun_adi,
            'tehlike_grubu' => $this->ghsdenGrup($ghs),
            'cmr' => in_array('ghs08', $ghs, true),
            'ucuculuk' => in_array($urun->fiziksel_hal, ['gaz', 'aerosol', 'toz'], true) ? 'yuksek' : 'orta',
        ]);
    }

    public function serbestEkle(): void
    {
        if (blank($this->yeniSerbest)) {
            Notification::make()->title('Kimyasal adı gerekli')->danger()->send();

            return;
        }

        $this->satirlar[] = $this->satirYap(['kimyasal_adi' => $this->yeniSerbest]);
        $this->reset('yeniSerbest');
    }

    public function satirSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    /** Anlık kontrol yaklaşımı önizlemesi (kaydetmeden). */
    public function yaklasimOnizle(int $index): int
    {
        $s = $this->satirlar[$index] ?? [];

        return KimyasalRiskModel::kontrolYaklasimi(
            $s['tehlike_grubu'] ?? 'A',
            $s['miktar'] ?? 'az',
            $s['ucuculuk'] ?? 'dusuk',
            (bool) ($s['cmr'] ?? false),
        );
    }

    public function kaydet(bool $sessiz = false): void
    {
        $kayit = $this->kayit();

        if (! $kayit) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $kayit->update([
            'satirlar' => $this->satirlar,
            'genel_not' => $this->genelNot,
            'degerlendirme_tarihi' => $this->degerlendirmeTarihi,
        ]);
        $this->satirlar = $kayit->fresh()->satirlar ?? [];

        if (! $sessiz) {
            Notification::make()->title('Kimyasal risk değerlendirmesi kaydedildi')->success()->send();
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
                ->label('PDF Raporu')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->kayit()?->satirlar))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return KimyasalRiskUretici::pdf($this->kayit());
                }),
        ];
    }
}
