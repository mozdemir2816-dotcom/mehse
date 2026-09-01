<?php

namespace App\Filament\Resources\RiskSablonus\Pages;

use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListRiskSablonus extends ListRecords
{
    protected static string $resource = RiskSablonuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sihirbaz')
                ->label('Sihirbazdan oluştur')
                ->icon('heroicon-o-sparkles')
                ->url(RiskSihirbazi::getUrl()),
        ];
    }
}
