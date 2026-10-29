<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\KomutaFirmaSecimi;
use App\Filament\Pages\Concerns\UzmanRaporVerisi;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Uzman Rapor Merkezi (isgsuite): aktif görevlendirmeli işyerinin sağlık
 * dışı İSG görünümü. Firma seçilmeden rapor verisi yüklenmez. Göstergeler,
 * işyeri uygunluk özeti (yasal dayanak bağlantılarıyla), kalem bazında
 * öncelikli aksiyonlar ve TXT çıktısı. Klinik sağlık kayıtları dahil değildir.
 *
 * 29.10.2026: Komuta Merkezi sadeleştirmesinde "Firma Denetim & Operasyon" sayfasına katıldı
 * (aynı veri, Concerns\UzmanRaporVerisi); bu adres geriye uyum için çalışır.
 */
class UzmanRaporMerkezi extends Page
{
    use KomutaFirmaSecimi;
    use UzmanRaporVerisi;
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.uzman-rapor-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'uzman-rapor-merkezi';

    protected static ?string $title = 'Uzman Rapor Merkezi';

    protected static ?string $navigationLabel = 'Uzman Rapor Merkezi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public function mount(): void
    {
        $this->firmaBaslat(ilkiniSec: false);
    }

    public function updatedFirmaId(): void
    {
        $this->firmaSecildi();
        $this->gosterilen = 20;
        $this->yenile();
    }

    public function yenile(): void
    {
        unset($this->firma, $this->rapor);
    }
}
