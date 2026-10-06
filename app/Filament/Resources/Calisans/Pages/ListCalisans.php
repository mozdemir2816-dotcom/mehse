<?php

namespace App\Filament\Resources\Calisans\Pages;

use App\Filament\Resources\Calisans\CalisanAksiyonlari;
use App\Filament\Resources\Calisans\CalisanResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCalisans extends ListRecords
{
    protected static string $resource = CalisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...CalisanAksiyonlari::hepsi(varsayilanFirmaId: fn () => $this->tableFilters['firma_id']['value'] ?? null),
            CreateAction::make()->label('Çalışan Ekle')->icon('heroicon-o-plus')
                ->modal()->modalWidth(Width::FiveExtraLarge)->modalHeading('Yeni Çalışan'),
        ];
    }
}
