<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

/**
 * Panelin giriş sayfası — "Panel" yerine "Ana Sayfa" adıyla (isgsuite
 * "Ana sayfa — görev durumum"). Widget'lar: karşılama bandı, görev
 * durumu panosu, bu ayın ziyaretleri, portföy özeti.
 */
class AnaSayfa extends Dashboard
{
    protected static ?string $title = 'Ana Sayfa';

    protected static ?string $navigationLabel = 'Ana Sayfa';
}
