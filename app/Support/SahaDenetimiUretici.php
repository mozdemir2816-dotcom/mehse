<?php

namespace App\Support;

use App\Models\SahaDenetimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Saha Denetimi PDF üretimi (dompdf) — isgpratik'in gerçek "Şantiye Denetim
 * ve Değerlendirme" PDF çıktısıyla birebir aynı düzen (künye + madde tablosu
 * + ekip + sabit güvenlik uyarıları + kaşe, ardından fotoğraf kanıtı sayfaları).
 */
class SahaDenetimiUretici
{
    /** @param  bool  $imzali  false = imzasız (matbu): kaşe/imza görselleri basılmaz */
    public static function pdf(SahaDenetimi $d, bool $imzali = true): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.saha-denetimi', [
            'imzali' => $imzali,
            'denetim' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4');

        // Sağ altta "Sayfa X / Y" — tek render, page_script ile (bkz. KurulToplantisiUretici).
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Serif', 'normal');
        $canvas->page_script(function (int $sayfa, int $toplam) use ($canvas, $font): void {
            $canvas->text($canvas->get_width() - 78, $canvas->get_height() - 28, "Sayfa {$sayfa} / {$toplam}", $font, 7.5, [0.42, 0.39, 0.36]);
        });

        $ad = 'saha-denetimi-'.Str::slug($d->firma?->unvan ?? 'firma').'-r'.$d->revizyon.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
