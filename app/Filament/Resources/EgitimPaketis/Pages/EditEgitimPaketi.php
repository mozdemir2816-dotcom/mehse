<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEgitimPaketi extends EditRecord
{
    protected static string $resource = EgitimPaketiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
