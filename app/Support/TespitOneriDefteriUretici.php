<?php

namespace App\Support;

use App\Models\TespitOneriDefteri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tespit ve Öneri Defteri PDF üretimi (dompdf). isgpratik 65-66.jpg.
 */
class TespitOneriDefteriUretici
{
    public static function pdf(TespitOneriDefteri $defter): StreamedResponse
    {
        $defter->loadMissing('firma.igu', 'firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.tespit-oneri-defteri', [
            'defter' => $defter,
            'firma' => $defter->firma,
        ])->setPaper('a4');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $canvas->text(485, 820, "Sayfa {$pageNumber} / {$pageCount}", $font, 7.5, [0.35, 0.4, 0.48]);
        });

        $ad = 'tespit-oneri-defteri-'.Str::slug($defter->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($dompdf->output()), $ad);
    }
}
