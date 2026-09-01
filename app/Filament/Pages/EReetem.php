<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class EReetem extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 27;

    protected static ?string $slug = 'e-recetem';

    protected static ?string $title = 'Ücretsiz E-Reçetem';

    protected static ?string $navigationLabel = 'Ücretsiz E-Reçetem';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — e-reçete bilgilendirme';
}
