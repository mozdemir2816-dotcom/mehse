<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\GeminiZiyaretDanismani;
use App\Support\ZiyaretProgramiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı onayıyla
 * BASİTLEŞTİRİLMİŞ liste (firma+yıl başına 12 aylık satır) olarak kuruldu.
 * isgpratik'teki tam takvim/sürükle-bırak/çoklu-firma-OSGB arayüzü kapsam
 * dışı bırakıldı.
 */
class ZiyaretProgrami extends Page
{
    protected string $view = 'filament.pages.ziyaret-programi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'ziyaret-programi';

    protected static ?string $title = 'Ziyaret Programı';

    protected static ?string $navigationLabel = 'Ziyaret Programı';

    public ?int $firmaId = null;

    public int $yil;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');

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
    public function program(): ?ZiyaretProgramiModel
    {
        return $this->firma ? ZiyaretProgramiModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    #[Computed]
    public function amacKategorileri(): array
    {
        return config('isg.ziyaret_programi.amac_kategorileri');
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->program);
    }

    public function updatedYil(): void
    {
        unset($this->program);
    }

    /*
    |--------------------------------------------------------------------------
    | Ay satırı düzenleme
    |--------------------------------------------------------------------------
    */

    public function ayGuncelle(int $ayIndex, string $alan, mixed $deger): void
    {
        $p = $this->program();
        $satirlar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($satirlar[$ayIndex])) {
            return;
        }

        $satirlar[$ayIndex][$alan] = $deger;
        $p->update(['ziyaretler' => $satirlar]);
        unset($this->program);
    }

    public function durumDegistir(int $ayIndex): void
    {
        $p = $this->program();
        $satirlar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($satirlar[$ayIndex])) {
            return;
        }

        $mevcut = $satirlar[$ayIndex]['durum'] ?? 'bos';
        $siraIndex = array_search($mevcut, ZiyaretProgramiModel::DURUM_SIRASI, true);
        $satirlar[$ayIndex]['durum'] = ZiyaretProgramiModel::DURUM_SIRASI[($siraIndex + 1) % count(ZiyaretProgramiModel::DURUM_SIRASI)];

        $p->update(['ziyaretler' => $satirlar]);
        unset($this->program);
    }

    public function aiAmacOner(int $ayIndex): void
    {
        $p = $this->program();

        if (! $p || ! $this->firma) {
            return;
        }

        $ayAdi = ZiyaretProgramiModel::AYLAR[$ayIndex] ?? null;

        if (! $ayAdi) {
            return;
        }

        $oneri = GeminiZiyaretDanismani::oner($ayAdi, $this->firma->nace_aciklama, $this->firma->tehlikeSinifiEtiketi());

        if ($oneri) {
            $this->ayGuncelle($ayIndex, 'amac', $oneri);
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->program() !== null)
                ->action(fn () => ZiyaretProgramiUretici::pdf($this->program())),
        ];
    }
}
