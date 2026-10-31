<?php

namespace App\Filament\Resources\TeklifTalepleris\Widgets;

use App\Filament\Resources\TeklifTalepleris\TeklifTalebiResource;
use App\Models\TeklifTalebi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Teklif Talepleri listesinin üstündeki sayaç kartları. Her kart listeyi o duruma
 * göre süzer (Toplam süzgeci kaldırır). Kaynak klasöründe durur ki panoya düşmesin.
 */
class TeklifSayaclari extends StatsOverviewWidget
{
    protected ?string $heading = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $sayilar = TeklifTalebi::query()
            ->selectRaw('durum, COUNT(*) as adet')
            ->groupBy('durum')
            ->pluck('adet', 'durum');

        $kart = fn (string $baslik, int $adet, string $ikon, string $renk, ?string $durum) => Stat::make($baslik, $adet)
            ->icon($ikon)
            ->color($renk)
            ->url(TeklifTalebiResource::getUrl('index', $durum ? ['filters' => ['durum' => ['value' => $durum]]] : []));

        $yeni = (int) ($sayilar['yeni'] ?? 0);

        return [
            $kart('Toplam', (int) $sayilar->sum(), 'heroicon-o-inbox-stack', 'gray', null)
                ->description('Tüm talepler'),
            $kart('Yeni', $yeni, 'heroicon-o-bell-alert', $yeni ? 'danger' : 'success', 'yeni')
                ->description($yeni ? 'Dönüş bekliyor' : 'Bekleyen yok'),
            $kart(TeklifTalebi::DURUMLAR['arandi'], (int) ($sayilar['arandi'] ?? 0), 'heroicon-o-phone-arrow-up-right', 'warning', 'arandi')
                ->description('Görüşme yapıldı'),
            $kart(TeklifTalebi::DURUMLAR['teklif_verildi'], (int) ($sayilar['teklif_verildi'] ?? 0), 'heroicon-o-document-check', 'info', 'teklif_verildi')
                ->description('Yanıt bekleniyor'),
        ];
    }
}
