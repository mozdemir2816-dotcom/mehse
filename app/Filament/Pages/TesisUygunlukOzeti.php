<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Eski adres (geriye uyum): Komuta Merkezi sadeleştirmesinde (29.10.2026) "Firma Denetim & Operasyon"
 * sayfasının "skor" bölümüne katıldı. Bildirimlerdeki / yer imlerindeki bağlantılar yeni sayfaya yönlenir.
 */
class TesisUygunlukOzeti extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.komuta-yonlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'tesis-uygunluk-ozeti';

    protected static ?string $title = 'Tesis Uygunluk Özeti · İSG Control Tower';

    public function mount(): void
    {
        $this->redirect(FirmaDenetimOperasyon::getUrl(array_filter([
            'firma' => request()->integer('firma') ?: null,
            'bolum' => 'skor',
        ])));
    }
}
