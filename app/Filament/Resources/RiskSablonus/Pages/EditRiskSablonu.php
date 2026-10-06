<?php

namespace App\Filament\Resources\RiskSablonus\Pages;

use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditRiskSablonu extends EditRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected static string $resource = RiskSablonuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->ustKaydet(),
            DeleteAction::make()
                ->visible(fn () => $this->record->user_id === Filament::auth()->id()),
        ];
    }
}
