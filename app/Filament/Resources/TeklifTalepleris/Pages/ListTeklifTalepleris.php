<?php

namespace App\Filament\Resources\TeklifTalepleris\Pages;

use App\Filament\Resources\TeklifTalepleris\TeklifTalebiResource;
use App\Filament\Resources\TeklifTalepleris\Widgets\TeklifSayaclari;
use Filament\Resources\Pages\ListRecords;

class ListTeklifTalepleris extends ListRecords
{
    protected static string $resource = TeklifTalebiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [TeklifSayaclari::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 2, 'lg' => 4];
    }
}
