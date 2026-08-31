<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRiskDegerlendirmesi extends CreateRecord
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
