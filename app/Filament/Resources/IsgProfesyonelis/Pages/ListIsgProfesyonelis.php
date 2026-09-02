<?php

namespace App\Filament\Resources\IsgProfesyonelis\Pages;

use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIsgProfesyonelis extends ListRecords
{
    protected static string $resource = IsgProfesyoneliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Profesyonel Ekle')->icon('heroicon-o-plus'),
        ];
    }
}
