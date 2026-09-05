<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;

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
    protected string $view = 'filament.pages.mevzuat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'mevzuat';

    protected static ?string $title = 'Mevzuat';

    protected static ?string $navigationLabel = 'Mevzuat';

    public string $arama = '';

    public string $kategori = 'tumu';

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
