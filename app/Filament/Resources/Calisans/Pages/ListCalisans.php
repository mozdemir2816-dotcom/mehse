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

    /** Portföy toplamı: aktif / pasif / toplam. Tablo sayacı filtreye göre değişir; fark burada görünür. */
    public function getSubheading(): ?string
    {
        $sayim = CalisanResource::getEloquentQuery()->toBase()->selectRaw('count(*) as toplam, sum(case when calisanlar.aktif = 1 then 1 else 0 end) as aktif')->first();
        $toplam = (int) ($sayim->toplam ?? 0);
        $aktif = (int) ($sayim->aktif ?? 0);

        return $aktif.' aktif · '.($toplam - $aktif).' pasif · portföyde toplam '.$toplam.' çalışan';
    }

    protected function getHeaderActions(): array
    {
        return [
            ...CalisanAksiyonlari::hepsi(varsayilanFirmaId: fn () => $this->tableFilters['firma_id']['value'] ?? null),
            CreateAction::make()->label('Çalışan Ekle')->icon('heroicon-o-plus')
                ->modal()->modalWidth(Width::FiveExtraLarge)->modalHeading('Yeni Çalışan'),
        ];
    }
}
