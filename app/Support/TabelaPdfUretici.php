<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\TabelaTasarimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Kayıtlı tabela tasarımının PDF şablonu (dompdf) ve SVG indirmesi (30.10.2026). Levha, TabelaSvgUretici'nin
 * gerçek vektör SVG'siyle çizilir; 96 dpi'a göre gerçek ölçüde (mm) A4'e yerleşir, sığmıyorsa orantılı küçülür.
 *
 * 02.11.2026: A3 seçeneği ve "Önerilen Tüm Levhaları Otomatik Paketle (ZIP)" — risk / saha bulgusu önerilerinin
 * her biri için sayfayı dolduran baskı PDF'i + vektör SVG.
 */
class TabelaPdfUretici
{
    private const MM_PX = 25.4 / 96;

    /** Yazdırılabilir alan (12 mm kenar boşluğu, başlık ve alt satır payı düşülmüş), mm. */
    public const SAYFALAR = [
        'a4' => ['dikey' => [186.0, 240.0], 'yatay' => [273.0, 153.0]],
        'a3' => ['dikey' => [273.0, 363.0], 'yatay' => [396.0, 240.0]],
    ];

    public static function svgDosyaAdi(TabelaTasarimi $t): string
    {
        return 'tabela-'.Str::slug($t->baslik ?: 'levha').'.svg';
    }

    public static function svgIndir(TabelaTasarimi $t): StreamedResponse
    {
        $svg = TabelaSvgUretici::svg((array) $t->tasarim);

        return response()->streamDownload(fn () => print ($svg), self::svgDosyaAdi($t), ['Content-Type' => 'image/svg+xml']);
    }

    public static function pdf(TabelaTasarimi $t, string $ebat = 'a4'): StreamedResponse
    {
        $icerik = static::pdfIcerik((array) ($t->tasarim ?? TabelaTasarimi::varsayilanTasarim()), $t->baslik, $t->firma?->unvan, $ebat);

        return response()->streamDownload(fn () => print ($icerik), 'tabela-'.Str::slug($t->baslik ?: 'levha').'.pdf');
    }

    /**
     * Levhanın PDF'i (ikili içerik). $sayfayaYay: levha baskı ölçüsünde sayfayı dolduracak kadar büyütülür
     * (toplu pakette A3 seçildiğinde A3 levha basılsın diye); değilse gerçek ölçü, sığmazsa küçültülür.
     */
    public static function pdfIcerik(array $veri, ?string $baslik, ?string $firma, string $ebat = 'a4', bool $sayfayaYay = false): string
    {
        $ebat = isset(self::SAYFALAR[$ebat]) ? $ebat : 'a4';
        $svg = TabelaSvgUretici::svg($veri, pdf: true);

        $gPx = (float) ($veri['width'] ?? 480);
        $yPx = (float) ($veri['height'] ?? 680);
        $gMm = $gPx * self::MM_PX;
        $yMm = $yPx * self::MM_PX;

        $yatay = $gMm > $yMm;
        [$enGenislik, $enYukseklik] = self::SAYFALAR[$ebat][$yatay ? 'yatay' : 'dikey'];
        $sigdir = min($enGenislik / $gMm, $enYukseklik / $yMm);
        $olcek = $sayfayaYay ? $sigdir : min(1.0, $sigdir);

        return Pdf::loadView('pdf.tabela', [
            'baslik' => $baslik,
            'firma' => $firma,
            'svgUri' => 'data:image/svg+xml;base64,'.base64_encode($svg),
            'genislikMm' => round($gMm * $olcek, 1),
            'yukseklikMm' => round($yMm * $olcek, 1),
            'pikselGenislik' => (int) $gPx,
            'pikselYukseklik' => (int) $yPx,
            'olcekMetni' => match (true) {
                $sayfayaYay => strtoupper($ebat).' baskı ölçüsü',
                $olcek < 1 => 'sayfaya sığdırmak için %'.round($olcek * 100).' ölçeğinde',
                default => 'gerçek ölçü',
            },
        ])->setPaper($ebat, $yatay ? 'landscape' : 'portrait')->output();
    }

    /** Levhanın baskı ölçüsü metni (mm), WhatsApp mesajı için. */
    public static function olcuMetni(array $veri): string
    {
        return round((float) ($veri['width'] ?? 480) * self::MM_PX).' × '.round((float) ($veri['height'] ?? 680) * self::MM_PX).' mm';
    }

    /**
     * "⚡ Önerilen Tüm Levhaları Otomatik Paketle (ZIP)": firmanın risk değerlendirmesi ve açık saha
     * bulgularından önerilen her levha için A4 / A3 baskı PDF'i + vektör SVG (baskı evi her ölçüde büyütebilir).
     */
    public static function oneriPaketi(Firma $firma, string $ebat = 'a4'): ?StreamedResponse
    {
        $oneriler = TabelaOnerisi::firmaIcin($firma, 50);
        if ($oneriler === []) {
            return null;
        }

        $ebat = isset(self::SAYFALAR[$ebat]) ? $ebat : 'a4';
        $yol = tempnam(sys_get_temp_dir(), 'tabela');
        $zip = new ZipArchive;
        $zip->open($yol, ZipArchive::OVERWRITE);

        foreach (array_values($oneriler) as $i => $o) {
            $ad = sprintf('%02d-%s', $i + 1, Str::slug($o['etiket']));
            $zip->addFromString($ad.'-'.$ebat.'.pdf', static::pdfIcerik($o['tasarim'], $o['etiket'], $firma->unvan, $ebat, sayfayaYay: true));
            $zip->addFromString($ad.'.svg', TabelaSvgUretici::svg($o['tasarim']));
        }
        $zip->addFromString('OKUBENI.txt', implode("\r\n", [
            $firma->unvan.' — önerilen güvenlik levhaları ('.now()->format('d.m.Y').')',
            'Her levha için '.strtoupper($ebat).' baskı PDF\'i ve vektör SVG (baskı evi istenen ölçüde büyütebilir).',
            'Kaynak: risk değerlendirmesindeki açık maddeler ve açık saha bulguları (ANSI Z535 / ISO 7010 / Sağlık ve Güvenlik İşaretleri Yönetmeliği).',
            '',
            ...collect($oneriler)->values()->map(fn ($o, $i) => sprintf('%02d. %s — %s', $i + 1, $o['etiket'], $o['tasarim']['signalTr'] ?? '')),
        ]));
        $zip->close();

        return response()->streamDownload(function () use ($yol) {
            readfile($yol);
            @unlink($yol);
        }, 'tabela-paketi-'.Str::slug($firma->unvan).'-'.$ebat.'.zip', ['Content-Type' => 'application/zip']);
    }
}
