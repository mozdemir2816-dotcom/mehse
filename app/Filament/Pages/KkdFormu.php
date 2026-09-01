<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class KkdFormu extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'kkd-formu';

    protected static ?string $title = 'KKD Zimmet Formu';

    protected static ?string $navigationLabel = 'KKD Formu';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — KKD teslim/zimmet formu';
}
