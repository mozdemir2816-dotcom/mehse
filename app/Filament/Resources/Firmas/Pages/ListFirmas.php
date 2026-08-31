<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFirmas extends ListRecords
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Firma Ekle')->icon('heroicon-o-plus'),
        ];
    }
}
