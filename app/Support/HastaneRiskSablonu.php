<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Bursa Şehir Hastanesi risk formu (A–S sütunları) — kaynak Excel dosyalarının düzenini çözer.
 *
 * Dosyaların künye bandı (1–5. satır) üç farklı düzende; başlık satırı 6/7/8. satırda. Bu yüzden
 * düzen sabit satır numarasıyla değil, "O | F | Ş" alt başlık satırından bulunur. Çıktı da bu
 * kaynak dosya kalıp alınarak üretilir (logo, künye, stiller, sütun genişlikleri aynen korunur).
 */
class HastaneRiskSablonu
{
    /** Sütun → alan (tek satırlık veri satırı). */
    public const SUTUNLAR = [
        'A' => 'no', 'B' => 'tehlike', 'C' => 'risk', 'D' => 'etki_alani', 'E' => 'mevcut_onlem',
        'F' => 'olasilik', 'G' => 'frekans', 'H' => 'siddet', 'I' => 'puan', 'J' => 'duzey',
        'K' => 'oneri', 'L' => 'son_olasilik', 'M' => 'son_frekans', 'N' => 'son_siddet', 'O' => 'son_puan',
        'P' => 'son_duzey', 'Q' => 'sorumlu', 'R' => 'termin', 'S' => 'aciklama',
    ];

    /** Şablon dosyasının tam yolu: önce yüklenenler (storage), sonra pakete dahil olanlar (resources). */
    public static function yol(string $dosya): ?string
    {
        foreach ([storage_path('app/'.config('hastane_risk.sablon_klasoru')), resource_path(config('hastane_risk.sablon_klasoru'))] as $klasor) {
            if (is_file($klasor.DIRECTORY_SEPARATOR.$dosya)) {
                return $klasor.DIRECTORY_SEPARATOR.$dosya;
            }
        }

        return null;
    }

    public static function yukle(string $yol): Spreadsheet
    {
        ExcelBellek::artir();

        return IOFactory::load($yol);
    }

    /**
     * Sayfanın düzeni; hastane formu değilse null.
     *
     * @return array{ust: int, alt: int, ilk: int, son_veri: int, footer: int, son: int, proto_veri: ?int, proto_bant: ?int}|null
     */
    public static function duzen(Worksheet $ws): ?array
    {
        $alt = null;
        $enAz = min($ws->getHighestRow(), 15);
        for ($r = 2; $r <= $enAz; $r++) {
            if (static::metin($ws, 'F', $r) === 'O' && static::metin($ws, 'H', $r) === 'Ş' && static::metin($ws, 'A', $r - 1) === 'NO') {
                $alt = $r;
                break;
            }
        }
        if ($alt === null) {
            return null;
        }

        $enYuksek = min($ws->getHighestRow(), 2000);
        $sonVeri = $alt;
        $protoVeri = $protoBant = null;
        for ($r = $alt + 1; $r <= $enYuksek; $r++) {
            if (static::bantMi($ws, $r)) {
                $protoBant ??= $r;
                $sonVeri = $r;
            } elseif (static::veriMi($ws, $r)) {
                $protoVeri ??= $r;
                $sonVeri = $r;
            }
        }

        // Alt bilgi: son veri satırından sonraki ilk dolu satır (özet bloğu); yoksa veriden hemen sonra.
        $footer = $sonVeri + 1;
        for ($r = $sonVeri + 1; $r <= $enYuksek; $r++) {
            if (static::metin($ws, 'A', $r) !== '' || static::metin($ws, 'I', $r) !== '') {
                $footer = $r;
                break;
            }
        }

        $son = $footer;
        foreach ($ws->getMergeCells() as $aralik) {
            if (preg_match('/\d+$/', $aralik, $e)) {
                $son = max($son, min((int) $e[0], $enYuksek));
            }
        }
        for ($r = $footer; $r <= $enYuksek; $r++) {
            if (static::metin($ws, 'A', $r) !== '' || static::metin($ws, 'I', $r) !== '') {
                $son = max($son, $r);
            }
        }

        return ['ust' => $alt - 1, 'alt' => $alt, 'ilk' => $alt + 1, 'son_veri' => $sonVeri, 'footer' => $footer, 'son' => $son,
            'proto_veri' => $protoVeri, 'proto_bant' => $protoBant];
    }

    public static function bantMi(Worksheet $ws, int $r): bool
    {
        return (bool) preg_match('/^BÖLÜM(?!\s+İÇERSİNDEKİ)/u', static::metin($ws, 'A', $r));
    }

    public static function veriMi(Worksheet $ws, int $r): bool
    {
        // Numara çoğunlukla sayı; bazı satırlarda "9-" / "12." gibi metin olarak yazılmış.
        $no = $ws->getCell('A'.$r)->getValue();

        return (is_numeric($no) || (is_string($no) && preg_match('/^\s*\d+\s*[-.)]*\s*$/', $no))) && static::metin($ws, 'B', $r) !== '';
    }

    /** Hücrenin hesaplanmış, kenarları kırpılmış metni (formüller çözülür). */
    public static function metin(Worksheet $ws, string $sutun, int $satir): string
    {
        $v = $ws->getCell($sutun.$satir)->getCalculatedValue();

        return is_scalar($v) ? trim((string) $v) : '';
    }

    /** Excel hücresindeki çok satırlı metni olduğu gibi (satır sonları korunarak) alır. */
    public static function ham(Worksheet $ws, string $sutun, int $satir): ?string
    {
        $v = $ws->getCell($sutun.$satir)->getCalculatedValue();
        if ($v === null || ! is_scalar($v)) {
            return null;
        }
        $v = trim(str_replace(["\r\n", "\r"], "\n", (string) $v));

        return $v === '' ? null : $v;
    }
}
