<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\SaglikGozetimi as SaglikGozetimiModel;
use App\Support\SaglikGozetimiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Sağlık Gözetimi Takibi — firma başına. Çalışan × sağlık tetkiki (işe giriş /
 * periyodik muayene, odyometri, SFT, portör vb.). Her satır için tetkik tarihi +
 * sonuç girilir; sonraki tetkik tarihi periyoda / tehlike sınıfına göre otomatik.
 */
class SaglikGozetimi extends Page
{
    protected string $view = 'filament.pages.saglik-gozetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'saglik-gozetimi';

    protected static ?string $title = 'Sağlık Gözetimi Takibi';

    protected static ?string $navigationLabel = 'Sağlık Gözetimi';

    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    public ?int $yeniCalisanId = null;

    public string $yeniTur = 'periyodik';

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
        unset($this->firma, $this->gozetim, $this->calisanlar);

        $gozetim = $this->gozetim();
        $this->satirlar = $gozetim?->satirlar ?? [];
        $this->genelNot = $gozetim?->genel_not;
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
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->kaydet()),

            Action::make('pdf')
                ->label('PDF (Takip Çizelgesi)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->gozetim()?->satirlar))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return SaglikGozetimiUretici::pdf($this->gozetim());
                }),
        ];
    }
}
