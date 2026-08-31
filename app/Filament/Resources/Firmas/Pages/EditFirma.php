<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFirma extends EditRecord
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
