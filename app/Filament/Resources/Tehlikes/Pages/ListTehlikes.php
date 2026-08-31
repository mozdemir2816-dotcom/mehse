<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTehlikes extends ListRecords
{
    protected static string $resource = TehlikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
