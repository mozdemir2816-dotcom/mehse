<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hastane risk formunun PDF çıktısı — Excel ile aynı kaynak şablondan: künye bandı ve alt bilgi
 * şablon hücrelerinden (birleşik hücre, dolgu, hizalama, kenarlık) aynen çizilir; veri satırları
 * sistemden yazılır. A4 yatay, tüm sütunlar tek sayfa genişliğine sığar, her satır sayfa
 * bölünmeden tek parça kalır, başlık satırları her sayfada tekrarlanır.
 */
class HastaneRiskPdfUretici
{
    private const SUTUNLAR = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];

    /** A4 yatay yazdırılabilir genişlik (px, 96 dpi): 297 mm − 2 × 6 mm kenar boşluğu. */
    private const SAYFA_PX = 1077;

    public static function pdf(RiskDegerlendirmesi $rd): StreamedResponse
    {
        $icerik = static::olustur($rd);
        $ad = 'risk-degerlendirmesi-'.Str::slug($rd->proje_adi ?: ($rd->firma_unvan ?: 'hastane')).'.pdf';

        return response()->streamDownload(fn () => print ($icerik), $ad, ['Content-Type' => 'application/pdf']);
    }

    public static function olustur(RiskDegerlendirmesi $rd): string
    {
        ExcelBellek::artir();
        @set_time_limit(300);

        $sablon = HastaneRiskExcelUretici::sablon($rd);
        $kitap = HastaneRiskSablonu::yukle(HastaneRiskSablonu::yol($sablon['dosya']));
        $ws = ($sablon['sayfa'] ? $kitap->getSheetByName($sablon['sayfa']) : null) ?? $kitap->getSheet(0);
        $duzen = HastaneRiskSablonu::duzen($ws) ?? throw new \RuntimeException('Hastane şablonunun düzeni çözülemedi.');

        $html = static::html($rd, $ws, $duzen);
        $kitap->disconnectWorksheets();

        return Pdf::loadHTML($html)->setPaper('a4', 'landscape')->setOption(['defaultFont' => 'DejaVu Sans', 'dpi' => 96])->output();
    }

    /** @param  array<string, int|null>  $duzen */
    private static function html(RiskDegerlendirmesi $rd, Worksheet $ws, array $duzen): string
    {
        // Sütun genişlikleri → sayfaya orantılı px.
        $genislik = [];
        foreach (static::SUTUNLAR as $s) {
            $genislik[$s] = (($ws->getColumnDimension($s)->getWidth() ?: 9.14) * 7) + 5;
        }
        $k = static::SAYFA_PX / array_sum($genislik);
        // dompdf <col> genişliklerini yok sayar; sütun genişlikleri ilk (görünmez) satırın hücrelerinden okunur.
        $olcu = '<tr class="olcu">';
        foreach ($genislik as $px) {
            $olcu .= '<td style="width:'.round($px * $k, 2).'px"></td>';
        }
        $olcu .= '</tr>';

        $ilk = 1;
        if (preg_match('/^\$?[A-Z]+\$?(\d+)/', (string) $ws->getPageSetup()->getPrintArea(), $e)) {
            $ilk = (int) $e[1];
        }

        $birlesik = static::birlesikHaritasi($ws);
        $maddeler = $rd->loadMissing('maddeler')->maddeler->sortBy([['sira', 'asc'], ['id', 'asc']]);

        $sayim = array_fill_keys(array_column(config('hastane_risk.bantlar'), 'harf'), 0);
        foreach ($maddeler as $m) {
            if ($m->duzey && isset($sayim[$m->duzey])) {
                $sayim[$m->duzey]++;
            }
        }

        $ust = static::aralik($ws, $ilk, $duzen['alt'], $k, $birlesik, null, static::logoHucresi($ws, $birlesik, $duzen['alt']));
        $veri = static::veriSatirlari($ws, $duzen, $maddeler, $k, $genislik);
        $alt = static::aralik($ws, $duzen['footer'], $duzen['son'], $k, $birlesik, [$maddeler->count(), $sayim]);

        $punto = round(12 * $k * 0.92, 2);

        return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><style>'
            .'@page{margin:6mm}body{margin:0;font-family:"DejaVu Sans",sans-serif;font-size:'.$punto.'pt;color:#000}'
            .'table{border-collapse:collapse;width:'.static::SAYFA_PX.'px}'
            .'td{border:0.4px solid #000;padding:1.2px 1.6px;overflow:hidden;word-wrap:break-word}'
            .'thead{display:table-header-group}tr{page-break-inside:avoid}'
            .'.olcu td{border:0;padding:0;height:0;line-height:0;font-size:0}'
            .'</style></head><body><table>'
            .'<thead>'.$olcu.$ust.'</thead><tbody>'.$veri.$alt.'</tbody></table></body></html>';
    }

    /**
     * Veri satırları + bölüm başlıkları; sütun stilleri (kalın, hizalama) şablonun kalıp satırından.
     *
     * @param  array<string, int|null>  $duzen
     */
    private static function veriSatirlari(Worksheet $ws, array $duzen, $maddeler, float $k, array $genislik): string
    {
        $protoVeri = $duzen['proto_veri'] ?? $duzen['ilk'];
        $protoBant = $duzen['proto_bant'];
        $stil = [];
        foreach (static::SUTUNLAR as $s) {
            $stil[$s] = static::hucreStili($ws, $s.$protoVeri, $k);
        }
        $bantStili = $protoBant ? static::hucreStili($ws, 'A'.$protoBant, $k) : 'font-weight:bold;text-align:center;background:#92D050';
        $bantYuk = $protoBant ? max(10, $ws->getRowDimension($protoBant)->getRowHeight() * $k * 1.33) : 14;

        $html = '';
        $no = 0;
        $bolum = null;
        $sayi = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        $metin = fn (?string $v) => nl2br(e((string) $v), false);

        foreach ($maddeler as $m) {
            if (filled($m->bolum) && $m->bolum !== $bolum) {
                $bolum = $m->bolum;
                $html .= '<tr><td colspan="19" style="'.$bantStili.';height:'.round($bantYuk, 1).'px">'.e($bolum).'</td></tr>';
            }
            $no++;
            $h = [
                'A' => (string) $no, 'B' => $metin($m->tehlike), 'C' => $metin($m->risk), 'D' => $metin($m->etki_alani), 'E' => $metin($m->mevcut_onlem),
                'F' => $sayi($m->olasilik), 'G' => $sayi($m->frekans), 'H' => $sayi($m->siddet), 'I' => $sayi($m->puan), 'J' => e((string) $m->duzey),
                'K' => $metin($m->oneri), 'L' => $sayi($m->son_olasilik), 'M' => $sayi($m->son_frekans), 'N' => $sayi($m->son_siddet),
                'O' => $sayi($m->son_puan), 'P' => e((string) $m->son_duzey), 'Q' => $metin($m->sorumlu), 'R' => $metin($m->termin), 'S' => $metin($m->aciklama),
            ];
            // Satır sayfaya sığmayacak kadar uzunsa (Excel'deki 409,5 pt kuralının karşılığı) yazısı küçülür.
            $boyut = static::satirPuntosu($m, $k, $genislik, round(12 * $k * 0.92, 2));
            $html .= '<tr>';
            foreach (static::SUTUNLAR as $s) {
                $dolgu = $boyut !== null ? ';font-size:'.$boyut.'pt' : '';
                if (in_array($s, ['J', 'P'], true) && ($renk = HastaneRisk::renk($s === 'J' ? $m->duzey : $m->son_duzey))) {
                    $dolgu .= ';background:#'.$renk;
                }
                $html .= '<td style="'.$stil[$s].$dolgu.'">'.$h[$s].'</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Şablonun [ilk..son] satırlarını (künye bandı ya da alt bilgi) birleşik hücre / dolgu / hizalama
     * ile aynen HTML satırlarına çevirir. $ozet verilirse özet sayım metinleri güncel sayılarla yenilenir.
     *
     * @param  array<string, array{colspan: int, rowspan: int}|true>  $birlesik
     * @param  array{0: int, 1: array<string, int>}|null  $ozet
     */
    private static function aralik(Worksheet $ws, int $ilk, int $son, float $k, array $birlesik, ?array $ozet, ?string $logoHucre = null): string
    {
        $html = '';
        for ($r = $ilk; $r <= $son; $r++) {
            $yuk = $ws->getRowDimension($r)->getRowHeight();
            $html .= '<tr'.($yuk > 0 ? ' style="height:'.round($yuk * $k * 1.333, 1).'px"' : '').'>';
            foreach (static::SUTUNLAR as $s) {
                $ad = $s.$r;
                $b = $birlesik[$ad] ?? null;
                if ($b === true) {
                    continue; // birleşik hücrenin kapsadığı diğer hücreler
                }

                $deger = $ws->getCell($ad)->getCalculatedValue();
                $deger = is_scalar($deger) ? (string) $deger : '';
                if ($ozet !== null && trim($deger) !== '') {
                    $deger = HastaneRiskExcelUretici::ozetMetni($deger, $ozet[0], $ozet[1], config('hastane_risk.bantlar'));
                }

                $resim = $ad === $logoHucre ? static::logoHtml($k) : '';

                $html .= '<td'.($b ? ' colspan="'.$b['colspan'].'" rowspan="'.$b['rowspan'].'"' : '')
                    .' style="'.static::hucreStili($ws, $ad, $k).'">'.($resim ?: nl2br(e($deger), false)).'</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }

    /** Bir satırın yüksekliği bu sınırı (pt) aşarsa yazı küçültülür: sayfa gövdesi − tekrarlanan başlık − pay. */
    private const AZAMI_SATIR_PT = 340;

    /**
     * Satırdaki en uzun metin sütununa göre tahmini yükseklik sınırı aşıyorsa, sığan en büyük yazı
     * boyutunu (pt) döndürür; sığıyorsa null (şablonun normal boyutu kalır).
     */
    private static function satirPuntosu($m, float $k, array $genislik, float $normal): ?float
    {
        $metinler = ['B' => $m->tehlike, 'C' => $m->risk, 'D' => $m->etki_alani, 'E' => $m->mevcut_onlem, 'K' => $m->oneri, 'Q' => $m->sorumlu, 'R' => $m->termin, 'S' => $m->aciklama];

        for ($boyut = $normal; $boyut >= 3.2; $boyut = round($boyut - 0.2, 2)) {
            $satir = 1;
            foreach ($metinler as $sutun => $metin) {
                $satir = max($satir, static::satirSayisi((string) $metin, $genislik[$sutun] * $k * 0.75 - 3.2, $boyut));
            }
            if ($satir * $boyut * 1.2 + 4 <= static::AZAMI_SATIR_PT) {
                return $boyut < $normal ? $boyut : null;
            }
        }

        return 3.2;
    }

    /** Sözcük sarmalı tahmini satır sayısı (DejaVu Sans ortalama karakter ≈ 0,56 em). */
    private static function satirSayisi(string $metin, float $genislikPt, float $punto): int
    {
        $sigan = max(3, (int) floor($genislikPt / (0.56 * $punto)));
        $toplam = 0;

        foreach (preg_split('/
/u', $metin) as $paragraf) {
            $uzunluk = 0;
            $satir = 1;
            foreach (preg_split('/\s+/u', trim($paragraf)) as $kelime) {
                $n = mb_strlen($kelime);
                if ($uzunluk > 0 && $uzunluk + 1 + $n > $sigan) {
                    $satir++;
                    $uzunluk = 0;
                }
                $uzunluk += ($uzunluk > 0 ? 1 : 0) + $n;
                while ($uzunluk > $sigan) {
                    $satir++;
                    $uzunluk -= $sigan;
                }
            }
            $toplam += $satir;
        }

        return $toplam;
    }

    /** Birleşik hücre haritası: sol üst → colspan/rowspan, kapsanan diğer hücreler → true. */
    private static function birlesikHaritasi(Worksheet $ws): array
    {
        $harita = [];
        foreach (array_keys($ws->getMergeCells()) as $aralik) {
            [[$c1, $r1], [$c2, $r2]] = static::sinirlar($aralik);
            foreach (range($r1, $r2) as $r) {
                foreach (range($c1, $c2) as $c) {
                    $harita[Coordinate::stringFromColumnIndex($c).$r] = true;
                }
            }
            $harita[Coordinate::stringFromColumnIndex($c1).$r1] = ['colspan' => min($c2, 19) - $c1 + 1, 'rowspan' => $r2 - $r1 + 1];
        }

        return $harita;
    }

    /** @return array{0: array{int, int}, 1: array{int, int}} */
    private static function sinirlar(string $aralik): array
    {
        [$bas, $son] = explode(':', $aralik);
        $b = Coordinate::coordinateFromString($bas);
        $s = Coordinate::coordinateFromString($son);

        return [[Coordinate::columnIndexFromString($b[0]), (int) $b[1]], [Coordinate::columnIndexFromString($s[0]), (int) $s[1]]];
    }

    /** Şablondaki logonun bağlı olduğu (birleşik) hücrenin sol üst adresi; künye bandında logo yoksa null. */
    private static function logoHucresi(Worksheet $ws, array $birlesik, int $altSatir): ?string
    {
        foreach ($ws->getDrawingCollection() as $resim) {
            $adres = $resim->getCoordinates();
            if ((int) preg_replace('/^[A-Z]+/', '', $adres) > $altSatir) {
                continue;
            }
            // B1 gibi bir hücre birleşik bir alanın (A1:C3) içindeyse, alanın sol üst hücresi bulunur.
            foreach (array_keys($ws->getMergeCells()) as $aralik) {
                [[$c1, $r1], [$c2, $r2]] = static::sinirlar($aralik);
                $c = Coordinate::columnIndexFromString(preg_replace('/\d+/', '', $adres));
                $r = (int) preg_replace('/^[A-Z]+/', '', $adres);
                if ($c >= $c1 && $c <= $c2 && $r >= $r1 && $r <= $r2) {
                    return Coordinate::stringFromColumnIndex($c1).$r1;
                }
            }

            return $adres;
        }

        return null;
    }

    private static function logoHtml(float $k): string
    {
        $yol = resource_path(config('hastane_risk.logo'));
        if (! is_file($yol)) {
            return '';
        }

        return '<img src="data:image/jpeg;base64,'.base64_encode((string) file_get_contents($yol)).'" style="height:'.round(81 * $k * 1.05, 1).'px">';
    }

    /** Hücrenin biçimi → satır içi CSS (dolgu, kalın, boyut, hizalama, kenarlık). */
    private static function hucreStili(Worksheet $ws, string $hucre, float $k): string
    {
        $stil = $ws->getStyle($hucre);
        $css = [];

        $yazi = $stil->getFont();
        $css[] = 'font-size:'.round(max(3.6, ($yazi->getSize() ?: 12) * $k * 0.92), 2).'pt';
        if ($yazi->getBold()) {
            $css[] = 'font-weight:bold';
        }

        if ($stil->getFill()->getFillType() === Fill::FILL_SOLID) {
            $css[] = 'background:#'.$stil->getFill()->getStartColor()->getRGB();
        }

        $hiza = $stil->getAlignment();
        $css[] = 'text-align:'.match ($hiza->getHorizontal()) {
            'center', 'centerContinuous' => 'center', 'right' => 'right', default => 'left',
        };
        $css[] = 'vertical-align:'.match ($hiza->getVertical()) {
            'top' => 'top', 'bottom' => 'bottom', default => 'middle',
        };

        $kenar = $stil->getBorders();
        if ($kenar->getBottom()->getBorderStyle() === Border::BORDER_NONE && $kenar->getTop()->getBorderStyle() === Border::BORDER_NONE
            && $kenar->getLeft()->getBorderStyle() === Border::BORDER_NONE && $kenar->getRight()->getBorderStyle() === Border::BORDER_NONE) {
            $css[] = 'border:0';
        }

        return implode(';', $css);
    }
}
