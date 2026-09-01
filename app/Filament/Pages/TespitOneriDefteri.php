<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class TespitOneriDefteri extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 18;

    protected static ?string $slug = 'tespit-oneri-defteri';

    protected static ?string $title = 'Tespit ve Öneri Defteri';

    protected static ?string $navigationLabel = 'Tespit Öneri Defteri';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — onaylı tespit/öneri defteri kayıtları';
}
