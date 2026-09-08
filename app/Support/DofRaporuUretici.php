<?php

namespace App\Support;

use App\Models\DofRaporu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DÖF Raporu PDF üretimi (dompdf) — isgpratik 158.jpg.
 */
class DofRaporuUretici
{
    public static function pdf(DofRaporu $d): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.dof-raporu', [
            'rapor' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'dof-raporu-'.Str::slug($d->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
