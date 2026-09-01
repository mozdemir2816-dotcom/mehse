<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class YillikPlanlar extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'yillik-planlar';

    protected static ?string $title = 'Yıllık Planlar';

    protected static ?string $navigationLabel = 'Yıllık Planlar';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — yıllık çalışma / eğitim planı';
}
