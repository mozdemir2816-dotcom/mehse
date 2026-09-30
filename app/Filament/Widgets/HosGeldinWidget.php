<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Pages\SahaDenetimi;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Models\User;
use App\Support\PortfoyKarne;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * Panel ana sayfasının en üstündeki karşılama bandı: selamlama, tarih,
 * portföy uyum yüzdesi (Kontrol Merkezi ile aynı hesap) ve hızlı işlemler.
 * Filament'in varsayılan AccountWidget'ının yerini aldı.
 */
class HosGeldinWidget extends Widget
{
    protected static ?int $sort = -10;

    protected string $view = 'filament.widgets.hos-geldin-widget';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var User $kullanici */
        $kullanici = Filament::auth()->user();
        $saat = (int) now()->format('G');

        $ozet = PortfoyKarne::ozet($kullanici->id);

        return [
            'selam' => match (true) {
                $saat < 6 => 'İyi geceler',
                $saat < 12 => 'Günaydın',
                $saat < 18 => 'İyi günler',
                default => 'İyi akşamlar',
            },
            'ad' => explode(' ', trim((string) $kullanici->name))[0] ?: $kullanici->name,
            'tarih' => now()->locale('tr')->translatedFormat('j F Y, l'),
            'firma' => (int) $ozet['firma'],
            'uyum' => (int) $ozet['uyum_yuzde'],
            'eylemler' => [
                ['ad' => 'Firma Ekle', 'ikon' => 'heroicon-o-plus', 'url' => FirmaResource::getUrl('index', ['action' => 'create'])],
                ['ad' => 'Risk Sihirbazı', 'ikon' => 'heroicon-o-sparkles', 'url' => RiskSihirbazi::getUrl()],
                ['ad' => 'Saha Denetimi', 'ikon' => 'heroicon-o-clipboard-document-list', 'url' => SahaDenetimi::getUrl()],
                ['ad' => 'Kontrol Merkezi', 'ikon' => 'heroicon-o-squares-2x2', 'url' => KontrolMerkezi::getUrl()],
            ],
        ];
    }
}
