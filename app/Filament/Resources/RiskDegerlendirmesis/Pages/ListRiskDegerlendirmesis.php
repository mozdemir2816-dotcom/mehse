<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRiskDegerlendirmesis extends ListRecords
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sihirbaz')
                ->label('Yeni (Sihirbaz)')
                ->icon('heroicon-o-sparkles')
                ->url(RiskSihirbazi::getUrl()),
            CreateAction::make()->label('Boş kayıt')->icon('heroicon-o-plus')->color('gray'),
        ];
    }
}
