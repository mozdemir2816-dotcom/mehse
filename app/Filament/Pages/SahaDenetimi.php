<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class SahaDenetimi extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'saha-denetimi';

    protected static ?string $title = 'Saha Denetimi';

    protected static ?string $navigationLabel = 'Saha Denetimi';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 73-75.jpg — görsel saha denetimi akışı';
}
