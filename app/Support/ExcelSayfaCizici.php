<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as PxCizim;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Bir Excel sayfasını (yazdırma alanı, sütun genişlikleri, satır yükseklikleri, birleşik hücreler, kenarlıklar,
 * dolgular, yazı tipleri, görseller, yazdırma ölçeği, sayfa sonları, ortalama) PDF'e BİREBİR taşımak için sayfa
 * modeline çevirir. Çizgi ve dolgular tek bir SVG dosyasında (sayfa başına bir kez), metinler ve görseller
 * mutlak konumlu kutular olarak döner; böylece çok sayıda katılımcı için aynı arka plan tekrar tekrar kullanılır.
 *
 * Şablonun kendisi (resources/belge/sertifika-yildiz-grup.xlsx) değiştikçe PDF de değişir — tasarım ikinci kez
 * elle çizilmez.
 */
final class ExcelSayfaCizici
{
    private const PX_PT = 0.75;   // 96 dpi: 1 px = 0.75 pt

    /** Excel yazı tipi → PDF yazı tipi (ölçü uyumlu, Türkçe karakterli açık lisanslı karşılıklar). */
    private const YAZI_TIPLERI = [
        'times new roman' => 'Liberation Serif',
        'calibri' => 'Carlito',
    ];

    /** Kenarlık stili → [kalınlık pt, kesik çizgi deseni]. */
    private const KENARLIK = [
        Border::BORDER_HAIR => [0.25, null],
        Border::BORDER_THIN => [0.75, null],
        Border::BORDER_MEDIUM => [1.5, null],
        Border::BORDER_THICK => [2.25, null],
        Border::BORDER_DOUBLE => [2.25, null],
        Border::BORDER_DOTTED => [0.75, '1,1'],
        Border::BORDER_DASHED => [0.75, '3,2'],
        Border::BORDER_MEDIUMDASHED => [1.5, '4,2'],
        Border::BORDER_DASHDOT => [0.75, '3,1,1,1'],
        Border::BORDER_MEDIUMDASHDOT => [1.5, '3,1,1,1'],
    ];

    /** @var array<int, string> geçici dosyalar (istek sonunda silinir) */
    private static array $geciciler = [];

    /**
     * @param  string  $alan  yazdırma alanı (örn. "B1:O85"); boşsa sayfanın yazdırma alanı
     * @return array{olcek: float, sayfalar: array<int, array{w: float, h: float, svg: string, kutular: array<int, array<string, mixed>>, resimler: array<int, array<string, mixed>>}>}
     */
    public static function sayfalar(Worksheet $sheet, ?string $alan = null): array
    {
        $alan = $alan ?: ($sheet->getPageSetup()->getPrintArea() ?: $sheet->calculateWorksheetDimension());
        [[$c1, $r1], [$c2, $r2]] = Coordinate::rangeBoundaries($alan);
        $olcek = max(0.1, (float) ($sheet->getPageSetup()->getScale() ?: 100) / 100);
        $varsayilanYazi = $sheet->getParent()->getDefaultStyle()->getFont();

        // Sütun x ve satır y konumları (pdf pt, ölçek uygulanmış).
        $x = [$c1 => 0.0];
        for ($c = $c1; $c <= $c2; $c++) {
            $g = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->getWidth();
            $g = $g > 0 ? $g : ($sheet->getDefaultColumnDimension()->getWidth() > 0 ? $sheet->getDefaultColumnDimension()->getWidth() : 9.14);
            $x[$c + 1] = $x[$c] + PxCizim::cellDimensionToPixels($g, $varsayilanYazi) * self::PX_PT * $olcek;
        }

        $y = [$r1 => 0.0];
        for ($r = $r1; $r <= $r2; $r++) {
            $h = $sheet->getRowDimension($r)->getRowHeight();
            $h = $h > 0 ? $h : ($sheet->getDefaultRowDimension()->getRowHeight() > 0 ? $sheet->getDefaultRowDimension()->getRowHeight() : 15.0);
            $y[$r + 1] = $y[$r] + $h * $olcek;
        }

        // Birleşik hücreler: sol-üst → [c1, r1, c2, r2]; kapsananlar atlanır.
        $birlesik = [];
        $kapsanan = [];
        foreach ($sheet->getMergeCells() as $aralik) {
            [[$a1, $b1], [$a2, $b2]] = Coordinate::rangeBoundaries($aralik);
            if ($a2 < $c1 || $a1 > $c2 || $b2 < $r1 || $b1 > $r2) {
                continue;   // alan dışı
            }
            $birlesik[Coordinate::stringFromColumnIndex($a1).$b1] = [$a1, $b1, $a2, $b2];
            for ($rr = $b1; $rr <= $b2; $rr++) {
                for ($cc = $a1; $cc <= $a2; $cc++) {
                    if ($cc !== $a1 || $rr !== $b1) {
                        $kapsanan[Coordinate::stringFromColumnIndex($cc).$rr] = true;
                    }
                }
            }
        }

        // Sayfa satır aralıkları (elle sayfa sonları; "A38" = 38. satırdan sonra).
        $sonlar = collect($sheet->getRowBreaks())->map(fn ($b) => (int) preg_replace('/\D/', '', $b->getCoordinate()))->filter(fn ($s) => $s >= $r1 && $s < $r2)->sort()->values()->all();
        $aralıklar = [];
        $baslangic = $r1;
        foreach ($sonlar as $son) {
            $aralıklar[] = [$baslangic, $son];
            $baslangic = $son + 1;
        }
        $aralıklar[] = [$baslangic, $r2];

        $resimler = self::resimleriTopla($sheet, $x, $y, $olcek, $c1, $c2, $r1, $r2);
        $sayfalar = [];

        foreach ($aralıklar as [$s1, $s2]) {
            $genislik = $x[$c2 + 1] - $x[$c1];
            $yukseklik = $y[$s2 + 1] - $y[$s1];
            $cizgiler = [];
            $dolgular = [];
            $kutular = [];

            for ($r = $s1; $r <= $s2; $r++) {
                for ($c = $c1; $c <= $c2; $c++) {
                    $koordinat = Coordinate::stringFromColumnIndex($c).$r;

                    if (isset($kapsanan[$koordinat])) {
                        continue;
                    }

                    [$a1, $b1, $a2, $b2] = $birlesik[$koordinat] ?? [$c, $r, $c, $r];
                    $b2 = min($b2, $s2);
                    $a2 = min($a2, $c2);
                    $rx = $x[$a1] - $x[$c1];
                    $ry = $y[$b1] - $y[$s1];
                    $rw = $x[$a2 + 1] - $x[$a1];
                    $rh = $y[$b2 + 1] - $y[$b1];

                    // NOT: Worksheet::getStyle() paylaşılan bir stil nesnesi döndürür; her okuma, ilgili hücrenin
                    // stili alındıktan HEMEN sonra yapılmalı (aksi hâlde başka hücrenin stili okunur).
                    $sag = Coordinate::stringFromColumnIndex($a2).$b1;
                    $alt = Coordinate::stringFromColumnIndex($a1).$b2;

                    // Dolgu
                    $dolgu = $sheet->getStyle($koordinat)->getFill();
                    if ($dolgu->getFillType() === Fill::FILL_SOLID) {
                        $renk = strtoupper($dolgu->getStartColor()->getRGB());
                        if ($renk !== 'FFFFFF') {
                            $dolgular[] = sprintf('<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="#%s"/>', $rx, $ry, $rw, $rh, $renk);
                        }
                    }

                    // Kenarlıklar: üst/sol sol-üst hücreden, sağ sağ-üst hücreden, alt sol-alt hücreden.
                    self::cizgiEkle($cizgiler, $sheet->getStyle($koordinat)->getBorders()->getTop(), $rx, $ry, $rx + $rw, $ry, $olcek);
                    self::cizgiEkle($cizgiler, $sheet->getStyle($koordinat)->getBorders()->getLeft(), $rx, $ry, $rx, $ry + $rh, $olcek);
                    self::cizgiEkle($cizgiler, $sheet->getStyle($sag)->getBorders()->getRight(), $rx + $rw, $ry, $rx + $rw, $ry + $rh, $olcek);
                    self::cizgiEkle($cizgiler, $sheet->getStyle($alt)->getBorders()->getBottom(), $rx, $ry + $rh, $rx + $rw, $ry + $rh, $olcek);

                    // Metin
                    $deger = $sheet->getCell($koordinat)->getValue();
                    $metin = $deger instanceof RichText ? $deger->getPlainText() : (string) $deger;

                    if (trim($metin) === '') {
                        continue;
                    }

                    $kutular[] = self::kutu($koordinat, $deger, $sheet->getStyle($koordinat), $rx, $ry, $rw, $rh, $olcek);
                }
            }

            $svgDosya = self::svgYaz($genislik, $yukseklik, $dolgular, $cizgiler);

            $sayfaResimleri = [];
            foreach ($resimler as $res) {
                $merkezY = $res['y'] + $res['h'] / 2;
                if ($merkezY >= $y[$s1] && $merkezY < $y[$s2 + 1]) {
                    $sayfaResimleri[] = [...$res, 'x' => $res['x'] - $x[$c1], 'y' => $res['y'] - $y[$s1]];
                }
            }

            $sayfalar[] = ['w' => $genislik, 'h' => $yukseklik, 'svg' => $svgDosya, 'kutular' => $kutular, 'resimler' => $sayfaResimleri];
        }

        return ['olcek' => $olcek, 'sayfalar' => $sayfalar];
    }

    /** Sayfa ölçüsü (pt) — kâğıt kodu (9 = A4). */
    public static function kagitBoyutu(Worksheet $sheet): array
    {
        $dikey = $sheet->getPageSetup()->getOrientation() !== 'landscape';
        [$w, $h] = [595.28, 841.89];   // A4

        return $dikey ? [$w, $h] : [$h, $w];
    }

    /** Kullanılan geçici dosyaları siler. */
    public static function temizle(): void
    {
        foreach (self::$geciciler as $g) {
            @unlink($g);
        }
        self::$geciciler = [];
    }

    // ------------------------------------------------------------------

    /** @param array<int, string> $cizgiler */
    private static function cizgiEkle(array &$cizgiler, Border $kenar, float $x1, float $y1, float $x2, float $y2, float $olcek): void
    {
        $stil = $kenar->getBorderStyle();

        if ($stil === Border::BORDER_NONE || ! isset(self::KENARLIK[$stil])) {
            return;
        }

        [$kalinlik, $desen] = self::KENARLIK[$stil];
        $renk = strtoupper($kenar->getColor()->getRGB()) ?: '000000';
        $anahtar = sprintf('%.1f,%.1f,%.1f,%.1f', $x1, $y1, $x2, $y2).$stil.$renk;
        $cizgiler[$anahtar] = sprintf(
            '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="#%s" stroke-width="%.2f"%s/>',
            $x1, $y1, $x2, $y2, $renk, max(0.3, $kalinlik * $olcek), $desen ? ' stroke-dasharray="'.$desen.'"' : '',
        );
    }

    /** @param array<int|string, string> $dolgular @param array<int|string, string> $cizgiler */
    private static function svgYaz(float $w, float $h, array $dolgular, array $cizgiler): string
    {
        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%.2f" height="%.2f" viewBox="0 0 %.2f %.2f">', $w, $h, $w, $h)
            .implode('', $dolgular).implode('', $cizgiler).'</svg>';

        return self::gecici($svg, 'svg');
    }

    /** @return array<string, mixed> */
    private static function kutu(string $koordinat, mixed $deger, \PhpOffice\PhpSpreadsheet\Style\Style $stil, float $x, float $y, float $w, float $h, float $olcek): array
    {
        $yazi = $stil->getFont();
        $hiza = $stil->getAlignment();
        $temelBoyut = (float) $yazi->getSize() * $olcek;

        $yatay = match ($hiza->getHorizontal()) {
            Alignment::HORIZONTAL_CENTER, Alignment::HORIZONTAL_CENTER_CONTINUOUS => 'center',
            Alignment::HORIZONTAL_RIGHT => 'right',
            Alignment::HORIZONTAL_JUSTIFY => 'justify',
            default => 'left',
        };
        $dikey = match ($hiza->getVertical()) {
            Alignment::VERTICAL_CENTER => 'middle',
            Alignment::VERTICAL_TOP => 'top',
            default => 'bottom',
        };
        $sar = (bool) $hiza->getWrapText();
        $girinti = (int) $hiza->getIndent() * 3.0 * $olcek;

        // Metin parçaları: [metin, yazı adı, boyut, kalın, italik, renk]
        $parcalar = [];
        if ($deger instanceof RichText) {
            foreach ($deger->getRichTextElements() as $parca) {
                $f = $parca instanceof \PhpOffice\PhpSpreadsheet\RichText\Run ? $parca->getFont() : null;
                $parcalar[] = [$parca->getText(), $f?->getName() ?? $yazi->getName(), (float) ($f?->getSize() ?? $yazi->getSize()) * $olcek, (bool) ($f?->getBold() ?? $yazi->getBold()), (bool) ($f?->getItalic() ?? $yazi->getItalic()), self::renk($f?->getColor()->getRGB() ?? $yazi->getColor()->getRGB())];
            }
        } else {
            $parcalar[] = [(string) $deger, $yazi->getName(), $temelBoyut, (bool) $yazi->getBold(), (bool) $yazi->getItalic(), self::renk($yazi->getColor()->getRGB())];
        }

        $oran = 1.0;

        if ($sar) {
            // Sarılan (wrap) hücre: dompdf satır aralığını yazı tipinin kendi ölçüsünden (Carlito ≈1,5 em) hesaplar ve
            // küçültülemez; Excel ise ≈1,22 em kullanır. Bu yüzden metin burada satırlara bölünür ve her satır Excel'deki
            // aralıkla ayrı bir blok olarak yerleştirilir. Hücre yüksekliğini aşan metin Excel gibi kırpılacağından PDF'te
            // üst satırın üstüne binmesin diye yazı, sığana kadar (en çok %30) küçültülür.
            $kullanilabilir = max(1.0, $w - 2.4 - $girinti);
            // Üst hizalı hücre aşağı doğru taşar (altı boş); ortalı/alt hizalı hücre iki yana taşıp komşuya bineceğinden küçültülür.
            $oran = $dikey === 'top' ? 1.0 : self::sigdirmaOrani($parcalar, $kullanilabilir, $h);
            $html = '';

            foreach (self::satirlaraBol($parcalar, $kullanilabilir, $oran) as $satir) {
                $yukseklik = 0.0;
                $icerik = '';

                foreach ($satir as [$metin, $i]) {
                    [, $ad, $boyut, $kalin, $italik, $renk] = $parcalar[$i];
                    $yukseklik = max($yukseklik, $boyut * $oran * self::satirKatsayisi($ad));
                    $icerik .= self::parca($metin, $ad, $boyut * $oran, $kalin, $italik, $renk);
                }

                $html .= sprintf('<div style="height:%.2fpt;line-height:%.2fpt;white-space:nowrap">%s</div>', $yukseklik ?: 10, $yukseklik ?: 10, $icerik === '' ? '&nbsp;' : $icerik);
            }
        } else {
            $html = '';

            foreach ($parcalar as [$metin, $ad, $boyut, $kalin, $italik, $renk]) {
                $html .= self::parca($metin, $ad, $boyut, $kalin, $italik, $renk);
            }
        }

        return [
            'anahtar' => $koordinat,
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'html' => $html,
            'yatay' => $yatay,
            'dikey' => $dikey,
            'sar' => $sar,
            'girinti' => $girinti,
            'boyut' => $temelBoyut * $oran,
        ];
    }

    /** Satır aralığı katsayısı (Excel): Calibri ≈1,22 em, Times ≈1,15 em. */
    private static function satirKatsayisi(string $yaziAdi): float
    {
        return str_contains(mb_strtolower($yaziAdi), 'times') ? 1.15 : 1.22;
    }

    /**
     * Toplam satır yüksekliğini $yukseklik'e sığdıracak yazı oranını (1.0 – 0.7) döndürür; satır sayısı GERÇEK yazı
     * tipi ölçülerine (hmtx) göre hesaplanır.
     *
     * @param  array<int, array{0: string, 1: string, 2: float, 3: bool, 4: bool, 5: string}>  $parcalar
     */
    private static function sigdirmaOrani(array $parcalar, float $genislik, float $yukseklik): float
    {
        try {
            for ($oran = 1.0; $oran >= 0.7; $oran -= 0.02) {
                $toplam = 0.0;

                foreach (self::satirlaraBol($parcalar, $genislik, $oran) as $satir) {
                    $toplam += max(array_map(fn ($q) => $parcalar[$q[1]][2] * $oran * self::satirKatsayisi($parcalar[$q[1]][1]), $satir ?: [['', 0]]));
                }

                if ($toplam <= $yukseklik + 0.5) {
                    return $oran;
                }
            }
        } catch (\Throwable) {
            return 1.0;
        }

        return 0.7;
    }

    /**
     * Metni verilen genişliğe sarar. Dönüş: satırlar; her satır [metin, parça-indeksi] ikilileri.
     * Açık satır sonları (\n) korunur; otomatik sarma sonrası satır başındaki boşluklar atılır.
     *
     * @param  array<int, array{0: string, 1: string, 2: float, 3: bool, 4: bool, 5: string}>  $parcalar
     * @return array<int, array<int, array{0: string, 1: int}>>
     */
    private static function satirlaraBol(array $parcalar, float $genislik, float $oran): array
    {
        $satirlar = [];
        $satir = [];
        $doluluk = 0.0;
        $otomatik = false;   // satır otomatik sarmayla mı başladı

        foreach ($parcalar as $i => [$metin, $ad, $boyut, $kalin, $italik]) {
            $harita = self::genislikler(self::yaziDosyasi($ad, $kalin, $italik));

            foreach (preg_split('/(\r\n|\n| )/u', $metin, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $parca) {
                if ($parca === "\n" || $parca === "\r\n") {
                    $satirlar[] = $satir;
                    $satir = [];
                    $doluluk = 0.0;
                    $otomatik = false;

                    continue;
                }

                $w = 0.0;
                foreach (mb_str_split($parca) as $harf) {
                    $w += ($harita[mb_ord($harf)] ?? 0.5) * $boyut * $oran;
                }

                if ($parca === ' ' && $otomatik && $doluluk === 0.0) {
                    continue;   // sarma sonrası satır başı boşluğu
                }

                if ($parca !== ' ' && $doluluk > 0 && $doluluk + $w > $genislik) {
                    $satirlar[] = self::sonBoslugu($satir);
                    $satir = [];
                    $doluluk = 0.0;
                    $otomatik = true;
                }

                // Aynı parçadan ardışık metinler tek ikilide birleştirilir.
                if ($satir && $satir[array_key_last($satir)][1] === $i) {
                    $satir[array_key_last($satir)][0] .= $parca;
                } else {
                    $satir[] = [$parca, $i];
                }

                $doluluk += $w;
            }
        }

        $satirlar[] = $satir;

        return $satirlar;
    }

    /** @param array<int, array{0: string, 1: int}> $satir */
    private static function sonBoslugu(array $satir): array
    {
        if ($satir) {
            $son = array_key_last($satir);
            $satir[$son][0] = rtrim($satir[$son][0], ' ');
        }

        return $satir;
    }

    private static function yaziDosyasi(string $ad, bool $kalin, bool $italik): string
    {
        $kok = resource_path('fonts');

        if (str_contains(mb_strtolower($ad), 'times')) {
            return $kok.'/LiberationSerif-'.($kalin ? ($italik ? 'BoldItalic' : 'Bold') : ($italik ? 'Italic' : 'Regular')).'.ttf';
        }

        return $kok.'/Carlito-'.($kalin ? 'Bold' : 'Regular').'.ttf';
    }

    /** @return array<int, float> kod noktası => ilerleme genişliği (em) */
    private static function genislikler(string $dosya): array
    {
        static $onbellek = [];

        if (isset($onbellek[$dosya])) {
            return $onbellek[$dosya];
        }

        $font = \FontLib\Font::load($dosya);
        $font->parse();
        $birim = (int) $font->getData('head', 'unitsPerEm') ?: 1000;
        $hmtx = $font->getData('hmtx');
        $harita = [];

        foreach ($font->getUnicodeCharMap() as $kod => $glif) {
            $harita[$kod] = ($hmtx[$glif][0] ?? $birim * 0.5) / $birim;
        }

        return $onbellek[$dosya] = $harita;
    }

    /** Bir metin parçası: yazı tipi/boyut/kalınlık/renk ile <span> (satır sonları <br>, boşluklar korunur). */
    public static function parca(string $metin, string $yaziAdi, float $boyut, bool $kalin, bool $italik, string $renk): string
    {
        $aile = self::YAZI_TIPLERI[mb_strtolower($yaziAdi)] ?? 'Carlito';
        // line-height <span>'a verilir: dompdf satır yüksekliğini satır içi kutunun kendi değerinden alır.
        $css = sprintf('font-family:\'%s\';font-size:%.2fpt;line-height:%.2fpt;color:%s;', $aile, $boyut, $boyut * 1.12, $renk)
            .($kalin ? 'font-weight:bold;' : '').($italik ? 'font-style:italic;' : '');

        $guvenli = htmlspecialchars($metin, ENT_QUOTES, 'UTF-8');
        // Ardışık / satır başı boşluklar korunur (girinti); satır sonları <br>.
        $guvenli = preg_replace_callback('/(^| ) +|  +/m', fn ($m) => str_replace(' ', '&nbsp;', $m[0]), $guvenli);

        return '<span style="'.$css.'">'.str_replace(["\r\n", "\n"], '<br>', $guvenli).'</span>';
    }

    private static function renk(?string $rgb): string
    {
        $rgb = strtoupper((string) $rgb);

        return preg_match('/^[0-9A-F]{6}$/', $rgb) ? '#'.$rgb : '#000000';
    }

    /** @return array<int, array<string, mixed>> Resimler: pdf pt konumu (sayfaya göre değil, alana göre). */
    private static function resimleriTopla(Worksheet $sheet, array $x, array $y, float $olcek, int $c1, int $c2, int $r1, int $r2): array
    {
        $liste = [];

        foreach ($sheet->getDrawingCollection() as $cizim) {
            [$sutun, $satir] = Coordinate::indexesFromString($cizim->getCoordinates());

            if ($sutun < $c1 || $sutun > $c2 || $satir < $r1 || $satir > $r2) {
                continue;
            }

            $icerik = $cizim instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing ? @file_get_contents($cizim->getPath()) : null;
            $bilgi = $icerik ? @getimagesizefromstring($icerik) : false;

            if (! $icerik || ! $bilgi) {
                continue;
            }

            $uzanti = match ($bilgi['mime'] ?? '') {
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/png' => 'png',
                default => null,
            };

            if (! $uzanti) {
                continue;
            }

            $liste[] = [
                'dosya' => self::gecici($icerik, $uzanti),
                'x' => $x[$sutun] + $cizim->getOffsetX() * self::PX_PT * $olcek,
                'y' => $y[$satir] + $cizim->getOffsetY() * self::PX_PT * $olcek,
                'w' => $cizim->getWidth() * self::PX_PT * $olcek,
                'h' => $cizim->getHeight() * self::PX_PT * $olcek,
            ];
        }

        return $liste;
    }

    /** İçeriği aynı olan geçici dosya tek kez yazılır. */
    private static function gecici(string $icerik, string $uzanti): string
    {
        // dompdf yalnız proje klasörü (chroot) içindeki dosyaları okur → storage altında.
        $klasor = storage_path('app/pdf-gecici');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($klasor);
        $yol = $klasor.DIRECTORY_SEPARATOR.'xsc_'.md5($icerik).'.'.$uzanti;

        if (! is_file($yol)) {
            file_put_contents($yol, $icerik);
        }

        self::$geciciler[$yol] = $yol;

        return str_replace('\\', '/', $yol);
    }
}
