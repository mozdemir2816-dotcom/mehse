<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Eski adres (geriye uyum): Komuta Merkezi sadeleştirmesinde (29.10.2026) "Firma Denetim & Operasyon"
 * sayfasının "firma360" bölümüne katıldı. Bildirimlerdeki / yer imlerindeki bağlantılar yeni sayfaya yönlenir.
 */
class IsyeriDurumMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.komuta-yonlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'isyeri-durum-merkezi';

    protected static ?string $title = 'İşyeri Durum Merkezi';

    public function mount(): void
    {
        $this->redirect(FirmaDenetimOperasyon::getUrl(array_filter([
            'firma' => request()->integer('firma') ?: null,
            'bolum' => 'firma360',
        ])));
    }
}
