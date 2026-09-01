<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Support\RiskDegerlendirmesiUretici;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRiskDegerlendirmesi extends EditRecord
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => RiskDegerlendirmesiUretici::pdf($this->record)),
            DeleteAction::make(),
        ];
    }
}
