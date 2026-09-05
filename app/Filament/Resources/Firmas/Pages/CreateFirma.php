<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFirma extends CreateRecord
{
    protected static string $resource = FirmaResource::class;

    /**
     * Profilim > Pazarlama "Firmaya Dönüştür" kısayolundan gelen aday firma
     * bilgilerini forma ön-doldurur (diğer alan varsayılanları bozulmadan).
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        if ($veri = session()->pull('aday_firma_donusum')) {
            $this->form->fillPartially($veri, array_keys($veri));
        }
    }
}
