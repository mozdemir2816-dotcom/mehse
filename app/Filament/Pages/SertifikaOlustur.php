<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class SertifikaOlustur extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 19;

    protected static ?string $slug = 'sertifika';

    protected static ?string $title = 'Sertifika Oluştur';

    protected static ?string $navigationLabel = 'Sertifika Oluştur';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — eğitim katılım sertifikası';
}
