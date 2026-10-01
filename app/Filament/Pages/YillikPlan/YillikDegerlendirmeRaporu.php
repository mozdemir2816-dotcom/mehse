<?php

namespace App\Filament\Pages\YillikPlan;

use App\Filament\Pages\YillikPlanlar;
use BackedEnum;

class YillikDegerlendirmeRaporu extends YillikPlanlar
{
    protected static string $planTuru = 'degerlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'yillik-degerlendirme-raporu';

    protected static ?string $title = 'Yıllık Değerlendirme Raporu';

    protected static ?string $navigationLabel = 'Yıllık Değerlendirme Raporu';
}
