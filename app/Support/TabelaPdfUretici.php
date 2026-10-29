<?php

namespace App\Support;

use App\Models\TabelaTasarimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kayıtlı tabela tasarımının PDF şablonu (dompdf) ve SVG indirmesi (30.10.2026). Levha, TabelaSvgUretici'nin
 * gerçek vektör SVG'siyle çizilir; 96 dpi'a göre gerçek ölçüde (mm) A4'e yerleşir, sığmıyorsa orantılı küçülür.
 */
class TabelaPdfUretici
{
    private const MM_PX = 25.4 / 96;

    /** A4 yazdırılabilir alan (12 mm kenar boşluğu, başlık ve alt satır payı düşülmüş), mm. */
    private const DIKEY = [186.0, 240.0];

    private const YATAY = [273.0, 153.0];

    public static function svgDosyaAdi(TabelaTasarimi $t): string
    {
        return 'tabela-'.Str::slug($t->baslik ?: 'levha').'.svg';
    }

    public static function svgIndir(TabelaTasarimi $t): StreamedResponse
    {
        $svg = TabelaSvgUretici::svg((array) $t->tasarim);

        return response()->streamDownload(fn () => print ($svg), self::svgDosyaAdi($t), ['Content-Type' => 'image/svg+xml']);
    }

    public static function pdf(TabelaTasarimi $t): StreamedResponse
    {
        $veri = $t->tasarim ?? TabelaTasarimi::varsayilanTasarim();
        $svg = TabelaSvgUretici::svg((array) $veri, pdf: true);

        $gPx = (float) ($veri['width'] ?? 480);
        $yPx = (float) ($veri['height'] ?? 680);
        $gMm = $gPx * self::MM_PX;
        $yMm = $yPx * self::MM_PX;

        $yatay = $gMm > $yMm;
        [$enGenislik, $enYukseklik] = $yatay ? self::YATAY : self::DIKEY;
        $olcek = min(1.0, $enGenislik / $gMm, $enYukseklik / $yMm);

        $pdf = Pdf::loadView('pdf.tabela', [
            'baslik' => $t->baslik,
            'firma' => $t->firma?->unvan,
            'svgUri' => 'data:image/svg+xml;base64,'.base64_encode($svg),
            'genislikMm' => round($gMm * $olcek, 1),
            'yukseklikMm' => round($yMm * $olcek, 1),
            'pikselGenislik' => (int) $gPx,
            'pikselYukseklik' => (int) $yPx,
            'olcekMetni' => $olcek < 1 ? 'sayfaya sığdırmak için %'.round($olcek * 100).' ölçeğinde' : 'gerçek ölçü',
        ])->setPaper('a4', $yatay ? 'landscape' : 'portrait');

        return response()->streamDownload(fn () => print ($pdf->output()), 'tabela-'.Str::slug($t->baslik ?: 'levha').'.pdf');
    }
}
