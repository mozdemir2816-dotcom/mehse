<?php

namespace App\Filament\Resources\IsgProfesyonelis\Pages;

use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIsgProfesyoneli extends CreateRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected function getHeaderActions(): array
    {
        return [$this->ustKaydet()];
    }

    protected static string $resource = IsgProfesyoneliResource::class;
}
