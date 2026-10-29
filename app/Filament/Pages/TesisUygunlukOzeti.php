<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\KomutaFirmaSecimi;
use App\Filament\Pages\Concerns\TesisUygunlukVerisi;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Tesis Uygunluk Özeti · İSG Control Tower (isgsuite): seçili işyerinin
 * taşeron, PTW, periyodik kontrol, saha denetimi, birleşik aksiyon ve hizmet
 * süresi durumundan açıklanabilir 0–100 operasyon skoru ve "bugün neye
 * müdahale etmeliyim?" listesi. Salt okunur; kısayollar mevcut modülleri açar.
 *
 * 29.10.2026: Komuta Merkezi sadeleştirmesinde "Firma Denetim & Operasyon" sayfasına katıldı
 * (aynı veri, Concerns\TesisUygunlukVerisi); bu adres geriye uyum için çalışır.
 */
class TesisUygunlukOzeti extends Page
{
    use KomutaFirmaSecimi;
    use TesisUygunlukVerisi;
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.tesis-uygunluk-ozeti';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'tesis-uygunluk-ozeti';

    protected static ?string $title = 'Tesis Uygunluk Özeti · İSG Control Tower';

    protected static ?string $navigationLabel = 'Tesis Uygunluk Özeti';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public function mount(): void
    {
        $this->firmaBaslat();
    }

    public function updatedFirmaId(): void
    {
        $this->firmaSecildi();
        $this->yenile();
    }

    public function yenile(): void
    {
        unset($this->firma, $this->sonuc);
    }
}
