<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEgitimPaketi extends CreateRecord
{
    protected static string $resource = EgitimPaketiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Filament::auth()->id();

        return $data;
    }
}
