<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\IsyeriDurumVerisi;
use App\Filament\Pages\Concerns\KomutaFirmaSecimi;
use App\Filament\Pages\Concerns\TesisUygunlukVerisi;
use App\Filament\Pages\Concerns\UzmanRaporVerisi;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Komuta Merkezi · Firma Denetim & Operasyon (29.10.2026). Seçilen firmanın tek ekranda:
 *  - Operasyon Skoru & "bugün neye müdahale etmeliyim?" (eski Tesis Uygunluğu),
 *  - Firma 360: süreç tablosu, yükümlülük takvimi, göstergeler (eski İşyeri Durumu),
 *  - Uzman Raporu: uygunluk özeti ve öncelikli aksiyonlar (eski Uzman Raporları).
 * Üç eski sayfa aynı verileri geriye uyum için kendi adreslerinde göstermeye devam eder; mantık ortak
 * Concerns\* parçalarındadır. Firma seçimi App\Support\KomutaFirma ile sekmeler arası paylaşılır.
 */
class FirmaDenetimOperasyon extends Page
{
    use IsyeriDurumVerisi;
    use KomutaFirmaSecimi;
    use TesisUygunlukVerisi;
    use UzmanRaporVerisi;

    public const BOLUMLER = [
        'skor' => 'Operasyon Skoru & Aksiyonlar',
        'firma360' => 'Firma 360 & Termin Takvimi',
        'uzman' => 'Uzman Raporu',
    ];

    protected string $view = 'filament.pages.firma-denetim-operasyon';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — Komuta Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'firma-denetim-operasyon';

    protected static ?string $title = 'Firma Denetim & Operasyon';

    protected static ?string $navigationLabel = 'Firma Denetim & Operasyon';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Url(as: 'bolum')]
    public string $bolum = 'skor';

    /** Üç eski sayfayı görebilen kullanıcı bu birleşik sayfaya da girer (yetki kayıtları değişmeden). */
    public static function canAccess(): bool
    {
        $kullanici = auth()->user();

        if (! $kullanici || ! $kullanici->aktif) {
            return false;
        }

        foreach (['firma-denetim-operasyon', 'isyeri-durum-merkezi', 'tesis-uygunluk-ozeti', 'uzman-rapor-merkezi'] as $anahtar) {
            if ($kullanici->sayfaErisimiVarMi($anahtar)) {
                return true;
            }
        }

        return false;
    }

    public function mount(): void
    {
        $this->firmaBaslat();

        if (! array_key_exists($this->bolum, self::BOLUMLER)) {
            $this->bolum = 'skor';
        }
    }

    public function bolumSec(string $bolum): void
    {
        if (array_key_exists($bolum, self::BOLUMLER)) {
            $this->bolum = $bolum;
        }
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            $this->firmaSecildi();
            $this->takvimSifirla();
            $this->gosterilen = 20;
            $this->yenile();
        }

        $this->isyeriAlanGuncellendi($alan);
    }

    public function yenile(): void
    {
        unset($this->firma, $this->sonuc, $this->rapor);
        $this->isyeriVerisiniSifirla();
    }
}
