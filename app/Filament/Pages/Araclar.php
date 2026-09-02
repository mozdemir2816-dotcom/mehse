<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Araçlar — bağımsız, kalıcı veri tutmayan İSG hesaplayıcıları.
 * isgpratik'te tam karşılığı görülmedi; standart İSG mevzuatı
 * formülleriyle (kaza sıklık/ağırlık hızı, gürültü Lex,8h) kuruldu.
 */
class Araclar extends Page
{
    protected string $view = 'filament.pages.araclar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'araclar';

    protected static ?string $title = 'Araçlar';

    protected static ?string $navigationLabel = 'Araçlar';

    // --- Kaza Sıklık Hızı / Ağırlık Hızı ---
    public ?int $kazaSayisi = null;

    public ?int $kayipGunSayisi = null;

    public ?float $toplamCalismaSaati = null;

    // --- Gürültü Maruziyet Düzeyi (Lex,8h) ---
    /** @var array<int, array{db: ?float, saat: ?float}> */
    public array $gurultuOlcumleri = [
        ['db' => null, 'saat' => null],
    ];

    public function sikikHizi(): ?float
    {
        if (! $this->kazaSayisi || ! $this->toplamCalismaSaati) {
            return null;
        }

        return round(($this->kazaSayisi * 1_000_000) / $this->toplamCalismaSaati, 2);
    }

    public function agirlikHizi(): ?float
    {
        if (! $this->kayipGunSayisi || ! $this->toplamCalismaSaati) {
            return null;
        }

        return round(($this->kayipGunSayisi * 1_000) / $this->toplamCalismaSaati, 2);
    }

    public function gurultuOlcumEkle(): void
    {
        $this->gurultuOlcumleri[] = ['db' => null, 'saat' => null];
    }

    public function gurultuOlcumSil(int $index): void
    {
        unset($this->gurultuOlcumleri[$index]);
        $this->gurultuOlcumleri = array_values($this->gurultuOlcumleri);
    }

    public function lex8h(): ?float
    {
        $gecerliSatirlar = collect($this->gurultuOlcumleri)
            ->filter(fn (array $s): bool => is_numeric($s['db'] ?? null) && is_numeric($s['saat'] ?? null) && $s['saat'] > 0);

        if ($gecerliSatirlar->isEmpty()) {
            return null;
        }

        $toplamDoz = $gecerliSatirlar->sum(fn (array $s): float => (float) $s['saat'] * (10 ** ((float) $s['db'] / 10)));

        return round(10 * log10($toplamDoz / 8), 1);
    }

    /** @return array{esik: int, etiket: string, renk: string, aciklama: string}|null */
    public function gurultuSeviyesi(): ?array
    {
        $deger = $this->lex8h();

        if ($deger === null) {
            return null;
        }

        foreach (config('isg.araclar.gurultu_sinirlari') as $sinir) {
            if ($deger >= $sinir['esik']) {
                return $sinir;
            }
        }

        return null;
    }

    public function kazaHesaplayiciSifirla(): void
    {
        $this->reset(['kazaSayisi', 'kayipGunSayisi', 'toplamCalismaSaati']);
    }

    public function gurultuHesaplayiciSifirla(): void
    {
        $this->gurultuOlcumleri = [['db' => null, 'saat' => null]];
    }
}
