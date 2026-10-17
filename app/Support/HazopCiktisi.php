<?php

namespace App\Support;

use App\Models\RiskMaddesi;

/**
 * HAZOP risk analizi çıktı düzeni (PDF, Excel, Word ortak). Kaynak HAZOP Excel'indeki
 * sütunlarla birebir aynıdır; puan, olasılık, frekans ve şiddet içermez.
 *
 * Değerler `risk_maddeleri.hazop` (içe aktarılan hücre metinleri) öncelikli okunur.
 * Bu alan olmayan eski kayıtlarda ilgili eski metin alanlarına düşülür.
 */
class HazopCiktisi
{
    /**
     * [başlık, değer(RiskMaddesi, sıra no), Excel genişliği, PDF genişliği (%)]
     *
     * @return array<int, array{0: string, 1: \Closure(RiskMaddesi, int): mixed, 2: int, 3: float}>
     */
    public static function sutunlar(): array
    {
        $h = static fn (RiskMaddesi $m, string $anahtar, mixed $geri = null): mixed => (($m->hazop ?? [])[$anahtar] ?? null) ?: $geri;

        return [
            ['No', fn ($m, $no) => $no, 5, 2.5],
            ['Düğüm', fn ($m) => $h($m, 'dugum', $m->bolum), 20, 7],
            ['Parametre', fn ($m) => $h($m, 'parametre', $m->faaliyet), 13, 6.5],
            ['Kılavuz kelime', fn ($m) => $h($m, 'kilavuz'), 12, 6.5],
            ['Sapma', fn ($m) => $h($m, 'sapma', $m->tehlike), 26, 8.5],
            ['Olası nedenler', fn ($m) => $h($m, 'neden', $m->risk), 28, 9.5],
            ['Olası sonuçlar', fn ($m) => $h($m, 'sonuc'), 28, 9.5],
            ['Doğrulanacak korumalar', fn ($m) => $h($m, 'korumalar'), 30, 11],
            ['Öneri / aksiyon', fn ($m) => $h($m, 'oneri', $m->oneri), 40, 12],
            ['Öncelik', fn ($m) => $h($m, 'oncelik'), 9, 3.5],
            ['Sorumlu rol', fn ($m) => $h($m, 'sorumlu', $m->sorumluMetni()), 16, 6.5],
            ['Hedef tarih', fn ($m) => $h($m, 'termin', $m->termin ?: RiskMaddesi::VARSAYILAN_TERMIN), 13, 5],
            ['Kapanış kanıtı', fn ($m) => $h($m, 'kapanis'), 20, 7],
            ['Durum', fn ($m) => $h($m, 'durum'), 9, 5],
        ];
    }
}
