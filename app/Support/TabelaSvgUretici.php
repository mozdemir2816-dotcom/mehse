<?php

namespace App\Support;

use App\Models\TabelaTasarimi;

/**
 * Tabela tasarımından GERÇEK vektör SVG üretir (30.10.2026). Editörün kendi SVG çıktısı HTML'i
 * <foreignObject> içine gömdüğü için baskıya/PDF'e uygun değildir; bu sınıf aynı düzeni
 * (başlık şeridi, piktogram alanı, metin bloğu, kontur, vida delikleri) saf SVG şekil ve metniyle çizer.
 * Çıktı hem "SVG indir" düğmesinde hem PDF şablonunda (dompdf, php-svg-lib) kullanılır.
 *
 * Metin satır kırma, karakter genişliği tahminiyle yapılır (SVG'de otomatik satır kaydırma yoktur);
 * tarayıcı editöründeki görünümle birebir aynı olmayabilir, ölçüler ve renkler aynıdır.
 */
class TabelaSvgUretici
{
    private const YAZI = [
        'font-roboto' => "'Roboto Condensed', 'Arial Narrow', Arial, sans-serif",
        'font-impact' => "Impact, 'Arial Black', sans-serif",
        'font-sans' => 'Inter, Arial, sans-serif',
        'font-montserrat' => 'Montserrat, Arial, sans-serif',
        'font-oswald' => "Oswald, 'Arial Narrow', Arial, sans-serif",
        'font-arial' => 'Arial, Helvetica, sans-serif',
    ];

    /** dompdf'te Türkçe karakter (İ, Ş, Ğ) yalnız gömülü DejaVu ile çıkar; PDF modunda bu yazı tipi kullanılır. */
    private const PDF_YAZI = "'DejaVu Sans', sans-serif";

    private const HEADER_YUKSEKLIK = 46;

    private const DIPNOT_YUKSEKLIK = 20;

    /** @param  array<string, mixed>  $tasarim  TabelaTasarimi::tasarim (eksik alanlar varsayılanla tamamlanır) */
    public static function svg(array $tasarim, bool $pdf = false): string
    {
        $t = TabelaTasarimi::temizle($tasarim + TabelaTasarimi::varsayilanTasarim());
        $yazi = $pdf ? self::PDF_YAZI : (self::YAZI[$t['fontFamily']] ?? self::YAZI['font-arial']);

        $w = (float) $t['width'];
        $h = (float) $t['height'];
        $bw = (float) $t['borderWidth'];
        $r = (float) $t['borderRadius'];

        $ix = $bw;
        $iy = $bw;
        $iw = $w - 2 * $bw;
        $ih = $h - 2 * $bw;

        $cizim = '';

        // Başlık şeridi (yalnız ANSI)
        $govdeUst = $iy;
        if ($t['standardType'] === 'ansi') {
            $cizim .= self::baslikSeridi($t, $yazi, $ix, $iy, $iw);
            $govdeUst += self::HEADER_YUKSEKLIK;
        }

        $govdeAlt = $iy + $ih - self::DIPNOT_YUKSEKLIK;
        $gx = $ix;
        $gw = $iw;
        $gh = $govdeAlt - $govdeUst;
        $cizim .= self::govde($t, $yazi, $gx, $govdeUst, $gw, $gh);

        // Şartname dipnotu
        $dipnot = $t['specNote'] !== '' ? $t['specNote'] : 'T.C. İSG YÖNETMELİK STANDARDI';
        $cizim .= sprintf(
            '<text x="%s" y="%s" text-anchor="middle" font-family="%s" font-size="8" fill="%s" fill-opacity="0.65">%s</text>',
            self::n($w / 2), self::n($h - $bw - 7), self::e($yazi), $t['bodyTextCol'], self::e($dipnot),
        );

        // Çift kontur
        if ($t['doubleBorder']) {
            $cizim .= sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="none" stroke="%s" stroke-width="1.5"/>',
                self::n($bw + 5), self::n($bw + 5), self::n($w - 2 * $bw - 10), self::n($h - 2 * $bw - 10),
                self::n(max(0, $r - 4)), $t['bodyBg'] === '#ffffff' ? '#000000' : '#ffffff',
            );
        }

        $cizim .= self::vidalar($t, $w, $h, $bw);

        $kes = sprintf(
            '<clipPath id="tabela-kes"><rect x="%s" y="%s" width="%s" height="%s" rx="%s"/></clipPath>',
            self::n($bw / 2), self::n($bw / 2), self::n($w - $bw), self::n($h - $bw), self::n($r),
        );

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="%1$s" height="%2$s" viewBox="0 0 %1$s %2$s"><defs>%3$s</defs>'
            .'<rect x="%4$s" y="%4$s" width="%5$s" height="%6$s" rx="%7$s" fill="%8$s" stroke="%9$s" stroke-width="%10$s"/>'
            .'<g clip-path="url(#tabela-kes)">%11$s</g></svg>',
            self::n($w), self::n($h), $kes, self::n($bw / 2), self::n($w - $bw), self::n($h - $bw), self::n($r),
            $t['bodyBg'], $t['borderColor'], self::n($bw), $cizim,
        );
    }

    /** @param  array<string, mixed>  $t */
    private static function baslikSeridi(array $t, string $yazi, float $x, float $y, float $w): string
    {
        $hh = self::HEADER_YUKSEKLIK;
        $tr = self::buyuk($t['signalTr']);
        $en = $t['signalEn'] !== '' ? '/ '.mb_strtoupper($t['signalEn']) : '';
        $trBoyut = 22;
        $enBoyut = 11;

        $trGenislik = self::genislik($tr, $trBoyut, 0.8);
        $enGenislik = $en !== '' ? self::genislik($en, $enBoyut, 0.62) : 0;
        $ikon = $t['hasTriangle'] ? 22 : 0;
        $toplam = ($ikon ? $ikon + 8 : 0) + $trGenislik + ($enGenislik ? 6 + $enGenislik : 0);
        $bx = $x + ($w - $toplam) / 2;
        $by = $y + $hh / 2;

        $s = sprintf('<rect x="%s" y="%s" width="%s" height="%s" fill="%s"/>', self::n($x), self::n($y), self::n($w), self::n($hh), $t['headerBg']);
        $s .= sprintf('<rect x="%s" y="%s" width="%s" height="3" fill="#000000"/>', self::n($x), self::n($y + $hh - 3), self::n($w));

        if ($ikon) {
            // İç içe <svg> yerine transform: php-svg-lib (PDF) iç içe svg'nin x/y/viewBox'ını yok sayar.
            $s .= sprintf(
                '<g transform="translate(%s %s) scale(0.9167)"><path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 10h2v4h-2zm0 6h2v2h-2z" fill="%s"/></g>',
                self::n($bx), self::n($by - 12), $t['headerTextCol'],
            );
            $bx += $ikon + 8;
        }

        $s .= sprintf(
            '<text x="%s" y="%s" font-family="%s" font-size="%d" font-weight="900" letter-spacing="1" fill="%s">%s</text>',
            self::n($bx), self::n($by + 7), self::e($yazi), $trBoyut, $t['headerTextCol'], self::e($tr),
        );

        if ($en !== '') {
            $s .= sprintf(
                '<text x="%s" y="%s" font-family="%s" font-size="%d" fill="%s" fill-opacity="0.85">%s</text>',
                self::n($bx + $trGenislik + 6), self::n($by + 6), self::e($yazi), $enBoyut, $t['headerTextCol'], self::e($en),
            );
        }

        return $s;
    }

    /** @param  array<string, mixed>  $t */
    private static function govde(array $t, string $yazi, float $x, float $y, float $w, float $h): string
    {
        $pos = $t['picPosition'];
        $cizgi = fn (float $x1, float $y1, float $x2, float $y2): string => sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#000000" stroke-width="2"/>', self::n($x1), self::n($y1), self::n($x2), self::n($y2),
        );

        if ($pos === 'none') {
            $blok = self::metinBloku($t, $yazi, $w);

            return $blok['ciz']($x, $y + max(0, ($h - $blok['yukseklik']) / 2));
        }

        if ($pos === 'top' || $pos === 'bottom') {
            $blok = self::metinBloku($t, $yazi, $w);
            $pikH = max(40.0, $h - $blok['yukseklik']);
            $metinH = $h - $pikH;

            if ($pos === 'top') {
                return self::piktogramlar($t, $x, $y, $w, $pikH)
                    .$cizgi($x, $y + $pikH, $x + $w, $y + $pikH)
                    .$blok['ciz']($x, $y + $pikH + max(0, ($metinH - $blok['yukseklik']) / 2));
            }

            return $blok['ciz']($x, $y + max(0, ($metinH - $blok['yukseklik']) / 2))
                .$cizgi($x, $y + $metinH, $x + $w, $y + $metinH)
                .self::piktogramlar($t, $x, $y + $metinH, $w, $pikH);
        }

        // left / right: 5/12 piktogram, 7/12 metin
        $pikW = $w * 5 / 12;
        $metinW = $w - $pikW;
        $blok = self::metinBloku($t, $yazi, $metinW);
        $metinY = $y + max(0, ($h - $blok['yukseklik']) / 2);

        if ($pos === 'left') {
            return self::piktogramlar($t, $x, $y, $pikW, $h)
                .$cizgi($x + $pikW, $y, $x + $pikW, $y + $h)
                .$blok['ciz']($x + $pikW, $metinY);
        }

        return $blok['ciz']($x, $metinY)
            .$cizgi($x + $metinW, $y, $x + $metinW, $y + $h)
            .self::piktogramlar($t, $x + $metinW, $y, $pikW, $h);
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array{yukseklik: float, ciz: \Closure}
     */
    private static function metinBloku(array $t, string $yazi, float $genislik): array
    {
        $ped = 16.0;
        $ic = max(40.0, $genislik - 2 * $ped);
        $ts = (float) $t['titleSize'];
        $ds = (float) $t['descSize'];

        $baslik = self::sar(self::buyuk($t['mainTitle']), $ts, $ic, 0.62);
        $aciklama = self::sar($t['descText'], $ds, $ic, 0.52);
        $enBaslik = $t['showEn'] && $t['enTitle'] !== '' ? self::sar(mb_strtoupper($t['enTitle']), 12, $ic, 0.6) : [];
        $enAciklama = $t['showEn'] && $t['enDesc'] !== '' ? self::sar($t['enDesc'], 10, $ic, 0.5) : [];

        $bLh = $ts * 1.15;
        $aLh = $ds * 1.5;
        $enVar = $enBaslik !== [] || $enAciklama !== [];
        $yukseklik = 2 * $ped + count($baslik) * $bLh + 6 + count($aciklama) * $aLh
            + ($enVar ? 14 + count($enBaslik) * 14.4 + count($enAciklama) * 14 : 0);

        $hiza = match ($t['textAlign']) {
            'text-left' => ['start', fn (float $x) => $x + $ped],
            'text-right' => ['end', fn (float $x) => $x + $genislik - $ped],
            default => ['middle', fn (float $x) => $x + $genislik / 2],
        };

        $ciz = function (float $x, float $y) use ($t, $yazi, $ped, $ts, $ds, $baslik, $aciklama, $enBaslik, $enAciklama, $bLh, $aLh, $enVar, $hiza, $genislik): string {
            $tx = $hiza[1]($x);
            $anchor = $hiza[0];
            $s = '';
            $cy = $y + $ped;

            $satirlar = function (array $liste, float $boyut, float $lh, string $ek) use (&$s, &$cy, $tx, $anchor, $yazi, $t): void {
                foreach ($liste as $satir) {
                    $cy += $lh;
                    $s .= sprintf(
                        '<text x="%s" y="%s" text-anchor="%s" font-family="%s" font-size="%s" fill="%s" %s>%s</text>',
                        self::n($tx), self::n($cy - $lh * 0.22), $anchor, self::e($yazi), self::n($boyut), $t['bodyTextCol'], $ek, self::e($satir),
                    );
                }
            };

            $satirlar($baslik, $ts, $bLh, 'font-weight="900"');
            $cy += 6;
            $satirlar($aciklama, $ds, $aLh, 'font-weight="normal"');

            if ($enVar) {
                $cy += 6;
                $s .= sprintf(
                    '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#cbd5e1" stroke-width="1"/>',
                    self::n($x + $ped), self::n($cy), self::n($x + $genislik - $ped), self::n($cy),
                );
                $cy += 8;
                $satirlar($enBaslik, 12, 14.4, 'font-weight="900" fill-opacity="0.9"');
                $satirlar($enAciklama, 10, 14, 'fill-opacity="0.75"');
            }

            return $s;
        };

        return ['yukseklik' => $yukseklik, 'ciz' => $ciz];
    }

    /** @param  array<string, mixed>  $t */
    private static function piktogramlar(array $t, float $x, float $y, float $w, float $h): string
    {
        $n = max(1, (int) $t['picCount']);
        $dikey = in_array($t['picPosition'], ['left', 'right'], true);
        $sutun = $dikey ? ($n > 2 ? 2 : 1) : $n;
        $satir = (int) ceil($n / $sutun);
        $hw = $w / $sutun;
        $hh = $h / $satir;

        $s = '';
        for ($i = 0; $i < $n; $i++) {
            $kx = $x + ($i % $sutun) * $hw;
            $ky = $y + intdiv($i, $sutun) * $hh;
            $taban = min($hw, $hh) * 0.85;
            $boyut = min(min($hw, $hh), min(130.0, $taban) * ($t['picScale'] / 100));
            $s .= self::slot($t, $i, $kx + ($hw - $boyut) / 2, $ky + ($hh - $boyut) / 2, $boyut);
        }

        return $s;
    }

    /** @param  array<string, mixed>  $t */
    private static function slot(array $t, int $i, float $x, float $y, float $s): string
    {
        $ozel = $t['slotCustomImages'][$i] ?? null;
        $anahtar = $t['slots'][$i] ?? null;
        $ic = ($ozel || ! TabelaPiktogramlari::var($anahtar)) ? null : TabelaPiktogramlari::ic($anahtar);

        $ikon = function (float $ix, float $iy, float $is, bool $beyaz = false) use ($ozel, $ic): string {
            if ($ozel) {
                return sprintf('<image x="%s" y="%s" width="%s" height="%s" href="%s" xlink:href="%s" preserveAspectRatio="xMidYMid meet"/>', self::n($ix), self::n($iy), self::n($is), self::n($is), htmlspecialchars($ozel, ENT_XML1 | ENT_QUOTES), htmlspecialchars($ozel, ENT_XML1 | ENT_QUOTES));
            }

            if (! $ic) {
                return '';
            }

            $govde = $beyaz ? preg_replace('/\b(fill|stroke)="(?!none)[^"]*"/', '$1="#ffffff"', $ic['ic']) : $ic['ic'];

            $vb = preg_split('/[\s,]+/', trim($ic['viewBox']));
            $olcek = $is / max(1.0, (float) ($vb[2] ?? 100), (float) ($vb[3] ?? 100));

            return sprintf('<g transform="translate(%s %s) scale(%s)">%s</g>', self::n($ix - (float) ($vb[0] ?? 0) * $olcek), self::n($iy - (float) ($vb[1] ?? 0) * $olcek), rtrim(rtrim(number_format($olcek, 5, '.', ''), '0'), '.'), $govde);
        };

        switch ($t['picWrapper']) {
            case 'warning_triangle':
                return sprintf(
                    '<polygon points="%s,%s %s,%s %s,%s" fill="#FED402" stroke="#000000" stroke-width="%s" stroke-linejoin="round"/>',
                    self::n($x + $s * .5), self::n($y + $s * .06), self::n($x + $s * .94), self::n($y + $s * .92), self::n($x + $s * .06), self::n($y + $s * .92), self::n($s * .06),
                ).$ikon($x + $s * .25, $y + $s * .36, $s * .5);

            case 'mandatory_circle':
                return sprintf('<circle cx="%s" cy="%s" r="%s" fill="#005AA9" stroke="#ffffff" stroke-width="%s"/>', self::n($x + $s / 2), self::n($y + $s / 2), self::n($s * .47), self::n($s * .04))
                    .$ikon($x + $s * .21, $y + $s * .21, $s * .58, true);

            case 'prohibition_circle':
                return sprintf('<circle cx="%s" cy="%s" r="%s" fill="#ffffff" stroke="#D02C2F" stroke-width="%s"/>', self::n($x + $s / 2), self::n($y + $s / 2), self::n($s * .43), self::n($s * .09))
                    .$ikon($x + $s * .22, $y + $s * .22, $s * .56)
                    .sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#D02C2F" stroke-width="%s" stroke-linecap="round"/>', self::n($x + $s * .18), self::n($y + $s * .18), self::n($x + $s * .82), self::n($y + $s * .82), self::n($s * .08));

            default:
                return $ikon($x, $y, $s);
        }
    }

    /** @param  array<string, mixed>  $t */
    private static function vidalar(array $t, float $w, float $h, float $bw): string
    {
        $noktalar = match ($t['screwOption']) {
            '4_corners' => [[$bw + 14, $bw + 14], [$w - $bw - 14, $bw + 14], [$bw + 14, $h - $bw - 14], [$w - $bw - 14, $h - $bw - 14]],
            '2_sides' => [[$bw + 12, $h / 2], [$w - $bw - 12, $h / 2]],
            default => [],
        };

        return implode('', array_map(
            fn (array $p) => sprintf('<circle cx="%s" cy="%s" r="4" fill="#e2e8f0" stroke="#64748b" stroke-width="1"/>', self::n($p[0]), self::n($p[1])),
            $noktalar,
        ));
    }

    /**
     * Sözcük sınırından satır kırar; genişlik karakter sayısı × boyut × çarpan tahminidir.
     *
     * @return array<int, string>
     */
    private static function sar(string $metin, float $boyut, float $genislik, float $carpan): array
    {
        $enFazla = max(4, (int) floor($genislik / ($boyut * $carpan)));
        $satirlar = [];

        foreach (preg_split('/\R/u', trim($metin)) ?: [] as $paragraf) {
            $satir = '';

            foreach (preg_split('/\s+/u', $paragraf, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $sozcuk) {
                $dene = $satir === '' ? $sozcuk : $satir.' '.$sozcuk;

                if (mb_strlen($dene) > $enFazla && $satir !== '') {
                    $satirlar[] = $satir;
                    $satir = $sozcuk;
                } else {
                    $satir = $dene;
                }
            }

            if ($satir !== '') {
                $satirlar[] = $satir;
            }
        }

        return $satirlar;
    }

    private static function genislik(string $metin, float $boyut, float $carpan): float
    {
        return mb_strlen($metin) * $boyut * $carpan;
    }

    /** Türkçe büyük harf: i→İ, ı→I (mb_strtoupper locale'siz bunu yapmaz). */
    private static function buyuk(string $metin): string
    {
        return mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], $metin));
    }

    private static function e(string $metin): string
    {
        return htmlspecialchars($metin, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function n(float|int $sayi): string
    {
        return rtrim(rtrim(number_format((float) $sayi, 2, '.', ''), '0'), '.') ?: '0';
    }
}
