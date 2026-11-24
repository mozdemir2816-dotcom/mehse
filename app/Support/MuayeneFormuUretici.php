<?php

namespace App\Support;

use App\Models\MuayeneFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Muayene Formu (EK-2) PDF üretimi (dompdf).
 */
class MuayeneFormuUretici
{
    /** @param  bool  $imzali  false = imzasız (matbu): kaşe/imza görselleri basılmaz */
    public static function pdf(MuayeneFormu $m, bool $imzali = true, ?string $canliImza = null): StreamedResponse
    {
        $m->loadMissing('firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.muayene-formu', [
            'imzali' => $imzali,
            'form' => $m,
            'firma' => $m->firma,
            'canliImza' => $canliImza,
        ])->setPaper('a4');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $canvas->text(485, 822, "Sayfa {$pageNumber} / {$pageCount}", $font, 7.5, [0.35, 0.4, 0.48]);
        });

        $ad = 'muayene-formu-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print ($dompdf->output()), $ad);
    }
}
