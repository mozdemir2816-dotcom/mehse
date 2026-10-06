<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTehlike extends CreateRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected function getHeaderActions(): array
    {
        return [$this->ustKaydet()];
    }

    protected static string $resource = TehlikeResource::class;
}
