<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class IsKazasiRaporu extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 24;

    protected static ?string $slug = 'is-kazasi-raporu';

    protected static ?string $title = 'İş Kazası Raporu';

    protected static ?string $navigationLabel = 'İş Kazası Raporu';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 16.jpg — kaza inceleme raporu + 5N kök neden';
}
