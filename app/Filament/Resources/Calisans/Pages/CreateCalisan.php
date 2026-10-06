<?php

namespace App\Filament\Resources\Calisans\Pages;

use App\Filament\Resources\Calisans\CalisanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalisan extends CreateRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected function getHeaderActions(): array
    {
        return [$this->ustKaydet()];
    }

    protected static string $resource = CalisanResource::class;
}
