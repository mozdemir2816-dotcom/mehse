<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class EgitimSorulari extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'egitim-sorulari';

    protected static ?string $title = 'Eğitim Soruları';

    protected static ?string $navigationLabel = 'Eğitim Soruları';

    protected static bool $aiModulu = true;

    protected static ?string $planNotu = 'isgpratik 50-53.jpg — akıllı sınav motoru (5 temel + 15 işe özgü)';
}
