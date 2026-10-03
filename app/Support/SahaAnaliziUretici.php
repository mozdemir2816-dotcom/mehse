<?php

namespace App\Support;

use App\Models\SahaAnalizi;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Saha raporu PDF üretimi (dompdf), iki biçim:
 * - 'gozlem'  : Saha Gözlem Raporu (isgsuite referansı, A4 dikey) — Fine-Kinney
 *               risk sütunu, mevzuat QR'ları, taslakta "TASLAK" filigranı,
 *               her sayfada Hazırlayan/Onaylayan şeridi ve "Sayfa X/Y".
 * - 'gozetim' : İSG Saha Gözetim Raporu (isgpratik "Çoklu DÖF" birebir, A4 yatay).
 */
class SahaAnaliziUretici
{
    public static function pdf(SahaAnalizi $s, string $bicim = 'gozlem', bool $imzali = true): StreamedResponse
    {
        $s->loadMissing('firma.igu');

        if ($bicim === 'gozetim') {
            $pdf = Pdf::loadView('pdf.saha-analiz-raporu', [
                'rapor' => $s,
                'firma' => $s->firma,
                'imzali' => $imzali,
            ])->setPaper('a4', 'landscape');

            $ad = 'saha-gozetim-raporu-'.Str::slug($s->firma?->unvan ?? 'firma').'.pdf';

            return response()->streamDownload(fn () => print ($pdf->output()), $ad);
        }

        $pdf = Pdf::loadView('pdf.saha-gozlem-raporu', [
            'rapor' => $s,
            'firma' => $s->firma,
            'imzali' => $imzali,
            'taslak' => ! $s->tamamlandiMi(),
            'referanslar' => self::referanslar($s),
        ])->setPaper('a4');

        // Tek render + page_script (bkz. KurulToplantisiUretici — çift render yasak).
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_script(function (int $sayfa, int $toplam) use ($canvas, $font): void {
            $canvas->text($canvas->get_width() - 78, 22, "Sayfa {$sayfa}/{$toplam}", $font, 7.5, [0.39, 0.45, 0.55]);
        });

        $ad = 'saha-gozlem-raporu-'.Str::slug($s->firma?->unvan ?? 'firma').'-'.Str::slug($s->belge_no ?? '').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** @return array<int, array{ad: string, url: ?string, qr: ?string}> */
    public static function referanslar(SahaAnalizi $s): array
    {
        return collect($s->mevzuat_referanslari ?? [])
            ->filter(fn ($r) => filled($r['ad'] ?? null))
            ->map(fn ($r) => [
                'ad' => $r['ad'],
                'url' => $r['url'] ?? null,
                'qr' => filled($r['url'] ?? null) ? self::qr($r['url']) : null,
            ])
            ->values()
            ->all();
    }

    /** SVG data URI — GD gerekmez, dompdf'te çalışır (Ziyaretci::qrDataUri ile aynı). */
    private static function qr(string $url): ?string
    {
        try {
            return (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))->render($url);
        } catch (Throwable) {
            return null;
        }
    }
}
