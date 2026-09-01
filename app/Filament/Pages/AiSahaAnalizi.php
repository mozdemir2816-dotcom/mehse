<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class AiSahaAnalizi extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'ai-saha-analizi';

    protected static ?string $title = 'AI Saha Analizi';

    protected static ?string $navigationLabel = 'AI Saha Analizi';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik 11.jpg — foto + gözlem → risk skoru/mevzuat';
}
