<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEgitimPaketis extends ListRecords
{
    protected static string $resource = EgitimPaketiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
