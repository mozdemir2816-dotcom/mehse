<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class DofOlustur extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'dof';

    protected static ?string $title = 'DÖF Oluştur';

    protected static ?string $navigationLabel = 'DÖF Oluştur';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik — DÖF (düzeltici/önleyici faaliyet) formu';
}
