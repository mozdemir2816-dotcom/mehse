<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class EgitimKatilim extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'egitim-katilim';

    protected static ?string $title = 'Eğitim Katılım Formu';

    protected static ?string $navigationLabel = 'Eğitim Katılım';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 116-118.jpg — gün/konu seçimi + katılımcı listesi (Excel)';
}
