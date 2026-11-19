<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\KomutaFirma;
use App\Support\KullaniciAyarlari;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Telefon Saha Ekranı: sahada en sık yapılan işler büyük kartlarla. Hangi kartların
 * görüneceği ve Ana Sayfa yerine bu ekranın açılıp açılmayacağı kullanıcı ayarında
 * (Ayarlar > Telefon ana ekranı). Kartlar KullaniciAyarlari::SAHA_KISAYOLLARI'ndan gelir.
 */
class SahaEkrani extends Page
{
    protected string $view = 'filament.pages.saha-ekrani';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — Ayarlar > "Genel Ayarlar & Görünüm" sekmesinin alt sayfasıdır (App\Support\SayfaAileleri, 'ayarlar'). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'saha-ekrani';

    protected static ?string $title = 'Saha Ekranı';

    protected static ?string $navigationLabel = 'Saha Ekranı';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public function mount(): void
    {
        $this->firmaId = KomutaFirma::baslangic($this->firmalar);
    }

    public function updatedFirmaId(): void
    {
        KomutaFirma::sec($this->firmaId);
        if ($this->firmaId) {
            session(['ziyaret_firma' => $this->firmaId]);
        } else {
            session()->forget('ziyaret_firma');
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->secenekler()->all();
    }

    #[Computed]
    public function seciliFirma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar)
            ? Firma::find($this->firmaId)
            : null;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $kartlar = [];
        $firmaParam = $this->firmaId ? ['firma' => $this->firmaId] : [];

        foreach (KullaniciAyarlari::sahaKisayollari(Filament::auth()->user()) as $anahtar => [$etiket, $ikon, $rota, $parametreler]) {
            $ekParam = $anahtar === 'firmalar' ? [] : $firmaParam;
            $kartlar[] = [
                'anahtar' => $anahtar,
                'etiket' => $etiket,
                'ikon' => $ikon,
                'url' => route($rota, [...$ekParam, ...$parametreler]),
            ];
        }

        return ['kartlar' => $kartlar];
    }
}
