<?php

namespace App\Filament\Pages;

use App\Support\GeminiMevzuatDanismani;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Mevzuat Kütüphanesi — isgpratik'te genel mevzuat.gov.tr tarzı TAM bir kütüphane
 * olarak görülmüştü (Anayasa/Kanun/Yönetmelik/Tebliğ/Rehber kategorileriyle, İSG
 * dışı binlerce mevzuatı da kapsıyordu). mehse'de kapsam BİLİNÇLİ olarak İSG'ye
 * özgü, zaten uygulamanın diğer modüllerinde dayanak olarak kullanılan mevzuatla
 * sınırlandırıldı (config('isg.mevzuat')) — modül/akış taklit edildi, içerik
 * birebir kopyalanmadı.
 */
class Mevzuat extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.mevzuat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — Sistem & Ayarlar Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'mevzuat';

    protected static ?string $title = 'Mevzuat';

    protected static ?string $navigationLabel = 'Mevzuat';

    public string $arama = '';

    public string $kategori = 'tumu';

    public string $mevzuatSoru = '';

    public ?string $danismanYaniti = null;

    /** @var array<int, array{baslik: string, kategori: string, aciklama: string}> */
    public array $danismanKaynaklari = [];

    public function mevzuataSor(): void
    {
        if (blank(trim($this->mevzuatSoru))) {
            return;
        }

        $sonuc = GeminiMevzuatDanismani::sor($this->mevzuatSoru);
        $this->danismanYaniti = $sonuc['yanit'];
        $this->danismanKaynaklari = $sonuc['kaynaklar'];
    }

    public function hazirSoruSor(string $anahtar): void
    {
        $hazir = GeminiMevzuatDanismani::HAZIR_SORULAR[$anahtar] ?? null;
        if (! $hazir) {
            return;
        }

        $this->mevzuatSoru = $hazir['soru'];
        $this->mevzuataSor();
    }

    public function danismanTemizle(): void
    {
        $this->mevzuatSoru = '';
        $this->danismanYaniti = null;
        $this->danismanKaynaklari = [];
    }

    /** @return array<int, array{baslik: string, kategori: string, aciklama: string}> */
    public function sonuclar(): array
    {
        $terim = Str::lower(trim($this->arama));

        return collect(config('isg.mevzuat.liste'))
            ->filter(fn (array $m) => $this->kategori === 'tumu' || $m['kategori'] === $this->kategori)
            ->filter(fn (array $m) => $terim === '' || Str::contains(Str::lower($m['baslik'].' '.$m['aciklama']), $terim))
            ->values()
            ->all();
    }

    public function kategoriSec(string $kategori): void
    {
        $this->kategori = $kategori;
    }
}
