<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class ZiyaretProgrami extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'ziyaret-programi';

    protected static ?string $title = 'Ziyaret Programı';

    protected static ?string $navigationLabel = 'Ziyaret Programı';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik — aylık saha ziyaret programı (AI önerili)';
}
