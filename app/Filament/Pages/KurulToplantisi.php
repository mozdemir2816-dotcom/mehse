<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class KurulToplantisi extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'kurul-toplantisi';

    protected static ?string $title = 'İSG Kurul Toplantısı';

    protected static ?string $navigationLabel = 'Kurul Toplantısı';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik — kurul toplantı tutanağı + AI gündem';
}
