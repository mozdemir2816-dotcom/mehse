<?php

namespace App\Filament\Pages;

use BackedEnum;

/**
 * Online Eğitim Yönet > Takip (08.11.2026): atama ekranından ayrıldı. Tüm firmalar /
 * seçili firma takip listesi, Md.12/4 detay paneli, Excel takip listesi, İGU onayı ve
 * onaydan sonra sertifika. Mantık UzaktanEgitimAtama'dan devralınır; yalnız görünüm ayrı.
 */
class UzaktanEgitimTakip extends UzaktanEgitimAtama
{
    protected string $view = 'filament.pages.uzaktan-egitim-takip';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $slug = 'uzaktan-egitim-takip';

    protected static ?string $title = 'Uzaktan Eğitim Takibi';

    protected static ?string $navigationLabel = 'Uzaktan Eğitim Takibi';
}
