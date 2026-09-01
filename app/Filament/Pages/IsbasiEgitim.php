<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class IsbasiEgitim extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'isbasi-egitim';

    protected static ?string $title = 'İşbaşı Eğitim Tutanağı';

    protected static ?string $navigationLabel = 'İşbaşı Eğt. Tutanağı';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — işbaşı eğitim tutanağı';
}
