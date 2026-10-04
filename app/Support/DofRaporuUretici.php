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
    /** @param  bool  $imzali  false = imzasız (matbu): kaşe/imza görselleri basılmaz */
    public static function pdf(DofRaporu $d, bool $imzali = true): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.dof-raporu', [
            'imzali' => $imzali,
            'rapor' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'dof-raporu-'.Str::slug($d->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
