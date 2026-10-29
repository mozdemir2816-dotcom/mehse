<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Eski adres (geriye uyum): Komuta Merkezi sadeleştirmesinde (29.10.2026) "Firma Denetim & Operasyon"
 * sayfasının "uzman" bölümüne katıldı. Bildirimlerdeki / yer imlerindeki bağlantılar yeni sayfaya yönlenir.
 */
class UzmanRaporMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.komuta-yonlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'uzman-rapor-merkezi';

    protected static ?string $title = 'Uzman Rapor Merkezi';

    public function mount(): void
    {
        $this->redirect(FirmaDenetimOperasyon::getUrl(array_filter([
            'firma' => request()->integer('firma') ?: null,
            'bolum' => 'uzman',
        ])));
    }
}
