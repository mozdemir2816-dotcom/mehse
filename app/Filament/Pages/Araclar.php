<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class Araclar extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'araclar';

    protected static ?string $title = 'Araçlar';

    protected static ?string $navigationLabel = 'Araçlar';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — yardımcı araçlar / hesaplayıcılar';
}
