<?php

namespace App\Filament\Pages\YillikPlan;

use App\Filament\Pages\YillikPlanlar;
use BackedEnum;

class YillikEgitimPlani extends YillikPlanlar
{
    /** Menüde gizli — İSG Yıllık Planlama & Değerlendirme Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static string $planTuru = 'egitim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 29;

    protected static ?string $slug = 'yillik-egitim-plani';

    protected static ?string $title = 'Yıllık Eğitim Planı';

    protected static ?string $navigationLabel = 'Yıllık Eğitim Planı';
}
