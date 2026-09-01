<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;

class KontrolMerkezi extends HazirlanryorPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'kontrol-merkezi';

    protected static ?string $title = 'İSG Komuta Merkezi';

    protected static ?string $navigationLabel = 'Kontrol Merkezi';

    protected static bool $aiModulu = false;

    protected static ?string $planNotu = 'isgpratik 4.jpg — Günlük Akış / Firma Asistanı / Çalışan Asistanı sekmeleri';
}
