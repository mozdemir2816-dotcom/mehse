<?php

namespace App\Support;

use App\Models\SaglikGozetimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sağlık Gözetimi Takip Çizelgesi PDF'i (dompdf, A4 yatay).
 */
class SaglikGozetimiUretici
{
    public static function pdf(SaglikGozetimi $gozetim): StreamedResponse
    {
        $gozetim->loadMissing('firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.saglik-gozetimi', [
            'gozetim' => $gozetim,
            'firma' => $gozetim->firma,
            'turler' => config('isg.saglik_tetkik.turleri'),
            'sonuclar' => config('isg.saglik_tetkik.sonuclar'),
        ])->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $canvas->text(730, 578, "Sayfa {$pageNumber} / {$pageCount}", $font, 7.5, [0.35, 0.4, 0.48]);
        });

        $ad = 'saglik-gozetimi-'.Str::slug($gozetim->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($dompdf->output()), $ad);
    }
}
