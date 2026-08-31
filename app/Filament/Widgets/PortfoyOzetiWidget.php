<?php

namespace App\Filament\Widgets;

use App\Models\Calisan;
use App\Models\Firma;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortfoyOzetiWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Portföy Özeti';

    protected function getStats(): array
    {
        $userId = Filament::auth()->id();

        $firmalar = Firma::query()->where('user_id', $userId);
        $aktifFirma = (clone $firmalar)->where('aktif', true)->count();

        $calisan = Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('aktif', true)->count();

        $sozlesmeSuresiGecen = (clone $firmalar)
            ->where('aktif', true)
            ->whereNotNull('sozlesme_bitis')
            ->whereDate('sozlesme_bitis', '<', now())
            ->count();

        return [
            Stat::make('Aktif firma', $aktifFirma)
                ->description('Portföydeki işyeri')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Çalışan', $calisan)
                ->description('Aktif personel')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Süresi geçen sözleşme', $sozlesmeSuresiGecen)
                ->description($sozlesmeSuresiGecen > 0 ? 'Yenileme gerekli' : 'Sorun yok')
                ->icon('heroicon-o-document-text')
                ->color($sozlesmeSuresiGecen > 0 ? 'danger' : 'success'),
        ];
    }
}
