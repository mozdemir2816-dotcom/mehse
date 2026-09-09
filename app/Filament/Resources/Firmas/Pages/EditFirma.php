<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Models\Firma;
use App\Support\FirmaEvrakZipUretici;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFirma extends EditRecord
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tumEvrakIndir')
                ->label('Tüm Evrakları İndir (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->visible(fn (Firma $record): bool => filled(FirmaEvrakZipUretici::secenekler($record)))
                ->action(fn (Firma $record) => FirmaEvrakZipUretici::zip(
                    $record,
                    array_keys(FirmaEvrakZipUretici::secenekler($record)),
                )),

            DeleteAction::make(),
        ];
    }
}
