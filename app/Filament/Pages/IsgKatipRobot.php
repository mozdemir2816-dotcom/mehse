<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class IsgKatipRobot extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'isg-katip-robot';

    protected static ?string $title = 'İSG-KATİP Robot';

    protected static ?string $navigationLabel = 'İSG-KATİP Robot';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 7-9.jpg — Chrome eklentisi bilgi sayfası + günlük hak sayacı (stub)';
}
