<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Firma;
use App\Support\KomutaFirma;
use Filament\Facades\Filament;
use Livewire\Attributes\Computed;

/**
 * Komuta Merkezi firma sayfalarının ortak firma listesi / seçimi. Sayfa sınıfı kendi `$firmaId`
 * (#[Url(as: 'firma')]) özelliğini tanımlar; seçim değişince `KomutaFirma::sec()` çağırmayı unutmaz.
 */
trait KomutaFirmaSecimi
{
    /** @return array<int, string> id => etiket (aktif firmalar) */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->secenekler()->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar)
            ? Firma::with(['igu', 'isyeriHekimi', 'dsp'])->find($this->firmaId)
            : null;
    }

    /** Sayfa açılışında: ?firma= → oturum → (istenirse) ilk firma. */
    protected function firmaBaslat(bool $ilkiniSec = true): void
    {
        $this->firmaId = KomutaFirma::baslangic($this->firmalar) ?? ($ilkiniSec ? array_key_first($this->firmalar) : null);
    }

    protected function firmaSecildi(): void
    {
        KomutaFirma::sec($this->firmaId);
    }
}
