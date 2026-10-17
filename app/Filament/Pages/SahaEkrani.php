<?php

namespace App\Filament\Pages;

use App\Support\KullaniciAyarlari;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
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

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'saha-ekrani';

    protected static ?string $title = 'Saha Ekranı';

    protected static ?string $navigationLabel = 'Saha Ekranı';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $kartlar = [];

        foreach (KullaniciAyarlari::sahaKisayollari(Filament::auth()->user()) as $anahtar => [$etiket, $ikon, $rota, $parametreler]) {
            $kartlar[] = [
                'anahtar' => $anahtar,
                'etiket' => $etiket,
                'ikon' => $ikon,
                'url' => route($rota, $parametreler),
            ];
        }

        return ['kartlar' => $kartlar];
    }
}
