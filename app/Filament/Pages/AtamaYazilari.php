<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class AtamaYazilari extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'atama-yazilari';

    protected static ?string $title = 'Atama Yazıları';

    protected static ?string $navigationLabel = 'Atama Yazıları';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik — İGU/İH/DSP görevlendirme yazıları';
}
