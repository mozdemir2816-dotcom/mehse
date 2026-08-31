<?php

namespace App\Filament\Resources\Calisans\Pages;

use App\Filament\Resources\Calisans\CalisanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCalisan extends EditRecord
{
    protected static string $resource = CalisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
