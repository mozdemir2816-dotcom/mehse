<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListEgitimPaketis extends ListRecords
{
    protected static string $resource = EgitimPaketiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')
                ->modal()->modalWidth(Width::FourExtraLarge)
                // CreateEgitimPaketi::mutateFormDataBeforeCreate ile aynı: paketin sahibi oluşturan.
                ->mutateDataUsing(function (array $data): array {
                    $data['user_id'] = Filament::auth()->id();

                    return $data;
                }),
        ];
    }
}
