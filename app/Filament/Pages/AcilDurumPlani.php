<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class AcilDurumPlani extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'acil-durum-plani';

    protected static ?string $title = 'Acil Durum Eylem Planı';

    protected static ?string $navigationLabel = 'Acil Durum Planı';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 19-20.jpg — firma + rapor bilgileri, acil durum konuları, PDF/Word';
}
