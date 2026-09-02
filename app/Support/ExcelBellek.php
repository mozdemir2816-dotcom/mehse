<?php

namespace App\Support;

/**
 * PhpSpreadsheet, özellikle biçimlendirilmiş/çok sayfalı gerçek dünya
 * dosyalarında (kullanıcının risk analizi belgeleri gibi) PHP'nin varsayılan
 * 512M `memory_limit`ini kolayca aşabiliyor ("Allowed memory size exhausted"
 * — kullanıcıya boş/siyah ekran olarak yansır). Excel/CSV okuyan her yerde
 * içe aktarmadan hemen önce çağrılır.
 */
class ExcelBellek
{
    private const HEDEF_MB = 1024;

    public static function artir(): void
    {
        $mevcut = static::megabaytaCevir((string) ini_get('memory_limit'));

        // -1 = zaten sınırsız; hedeften düşükse yükselt.
        if ($mevcut !== -1 && $mevcut < self::HEDEF_MB) {
            ini_set('memory_limit', self::HEDEF_MB.'M');
        }
    }

    private static function megabaytaCevir(string $deger): int
    {
        if ($deger === '-1') {
            return -1;
        }

        $sayi = (int) $deger;
        $birim = strtoupper(substr($deger, -1));

        return match ($birim) {
            'G' => $sayi * 1024,
            'K' => intdiv($sayi, 1024),
            default => $sayi, // zaten 'M' ya da salt sayı (byte varsayimi yerine MB kabul edilir)
        };
    }
}
