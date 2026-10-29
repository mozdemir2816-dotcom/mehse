<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\IsyeriDurumVerisi;
use App\Filament\Pages\Concerns\KomutaFirmaSecimi;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * İşyeri Durum Merkezi (isgsuite "İşyeri Durum Merkezi / Firma 360"): seçili
 * işyerinin genel uyum durumu, süreç tablosu, yükümlülük takvimi,
 * göstergeler, profil, görevlendirmeler, son ziyaret ve olaylar — tek
 * ekranda, salt okunur. Tam Firma Dosyası (PDF), Detaylı Excel, Yazdır.
 *
 * 29.10.2026: Komuta Merkezi sadeleştirmesinde "Firma Denetim & Operasyon" sayfasına katıldı
 * (aynı veri, App\Filament\Pages\Concerns\IsyeriDurumVerisi); bu adres geriye uyum için çalışır.
 */
class IsyeriDurumMerkezi extends Page
{
    use IsyeriDurumVerisi;
    use KomutaFirmaSecimi;
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.isyeri-durum-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'isyeri-durum-merkezi';

    protected static ?string $title = 'İşyeri Durum Merkezi';

    protected static ?string $navigationLabel = 'İşyeri Durum Merkezi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public function mount(): void
    {
        $this->firmaBaslat();
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            $this->firmaSecildi();
            $this->takvimSifirla();
            $this->yenile();
        }

        $this->isyeriAlanGuncellendi($alan);
    }

    public function yenile(): void
    {
        unset($this->firma);
        $this->isyeriVerisiniSifirla();
    }
}
