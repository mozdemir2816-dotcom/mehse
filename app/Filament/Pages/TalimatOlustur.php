<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class TalimatOlustur extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'talimat';

    protected static ?string $title = 'Talimat Oluştur';

    protected static ?string $navigationLabel = 'Talimat Oluştur';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik — güvenli çalışma talimatı (AI taslak)';
}
