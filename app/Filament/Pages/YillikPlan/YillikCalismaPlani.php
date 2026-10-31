<?php

namespace App\Filament\Pages\YillikPlan;

use App\Filament\Pages\YillikPlanlar;
use BackedEnum;
use UnitEnum;

class YillikCalismaPlani extends YillikPlanlar
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    protected static string $planTuru = 'calisma';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    /** Her yıl yenilendiği için grupsuz, Eğitim'in altında ayrı menü girişi (31.10.2026 kullanıcı isteği). */
    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'yillik-calisma-plani';

    protected static ?string $title = 'Yıllık Çalışma Planı';

    protected static ?string $navigationLabel = 'Yıllık Planlar & Değerlendirme';
}
