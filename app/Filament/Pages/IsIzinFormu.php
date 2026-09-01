<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class IsIzinFormu extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'is-izin-formu';

    protected static ?string $title = 'İş İzin Formu (PTW)';

    protected static ?string $navigationLabel = 'İş İzin Formu';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — sıcak iş / yüksekte / kapalı alan iş izni';
}
