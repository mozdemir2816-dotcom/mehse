<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class TatbikatTutanagi extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 17;

    protected static ?string $slug = 'tatbikat';

    protected static ?string $title = 'Tatbikat Tutanağı';

    protected static ?string $navigationLabel = 'Tatbikat Tutanağı';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — yangın/tahliye tatbikat tutanağı';
}
