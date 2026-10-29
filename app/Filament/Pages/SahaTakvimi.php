<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Eski adres (geriye uyum): Saha Takvimi, "Saha & Ziyaret Planlama Merkezi"nin Genel Ajanda görünümüne katıldı
 * (29.10.2026; mantık App\Filament\Pages\Concerns\PortfoyAjandasi). Bağlantılar yeni sayfaya yönlenir; eski
 * `?tarih=` ve `?ay=` parametreleri ajanda parametrelerine çevrilir.
 */
class SahaTakvimi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.komuta-yonlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'saha-takvimi';

    protected static ?string $title = 'Saha Takvimi';

    public function mount(): void
    {
        $this->redirect(ZiyaretProgrami::getUrl(array_filter([
            'gorunum' => 'ajanda',
            'ajanda_tarih' => request()->query('tarih'),
            'ajanda_ay' => request()->query('ay'),
        ])));
    }
}
