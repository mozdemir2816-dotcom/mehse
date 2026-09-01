<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class CezaTeblig extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 23;

    protected static ?string $slug = 'ceza-teblig';

    protected static ?string $title = 'Ceza ve Tebliğ Tutanağı';

    protected static ?string $navigationLabel = 'Ceza ve Tebliğ Tutanağı';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — disiplin/ihtar tutanağı';
}
