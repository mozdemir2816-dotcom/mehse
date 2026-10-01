<?php

namespace App\Filament\Pages\YillikPlan;

use App\Filament\Pages\YillikPlanlar;
use BackedEnum;

class YillikCalismaPlani extends YillikPlanlar
{
    protected static string $planTuru = 'calisma';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 28;

    protected static ?string $slug = 'yillik-calisma-plani';

    protected static ?string $title = 'Yıllık Çalışma Planı';

    protected static ?string $navigationLabel = 'Yıllık Çalışma Planı';
}
