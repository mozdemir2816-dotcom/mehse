<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class MuayeneFormu extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 26;

    protected static ?string $slug = 'muayene-formu';

    protected static ?string $title = 'Muayene Formu (EK-2)';

    protected static ?string $navigationLabel = 'Muayene Form (EK-2)';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — işe giriş / periyodik muayene formu EK-2';
}
