<?php

namespace App\Filament\Resources\IsgProfesyonelis\Pages;

use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIsgProfesyoneli extends EditRecord
{
    protected static string $resource = IsgProfesyoneliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
