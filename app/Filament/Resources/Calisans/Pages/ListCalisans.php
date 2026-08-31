<?php

namespace App\Filament\Resources\Calisans\Pages;

use App\Filament\Resources\Calisans\CalisanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalisans extends ListRecords
{
    protected static string $resource = CalisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Çalışan Ekle')->icon('heroicon-o-plus'),
        ];
    }
}
