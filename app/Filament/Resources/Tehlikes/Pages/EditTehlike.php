<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTehlike extends EditRecord
{
    protected static string $resource = TehlikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
