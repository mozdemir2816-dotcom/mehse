<?php

namespace App\Support;

use App\Models\IsKazasiRaporu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İş Kazası Raporu PDF üretimi (dompdf).
 */
class IsKazasiRaporuUretici
{
    public static function pdf(IsKazasiRaporu $r): StreamedResponse
    {
        $r->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.is-kazasi-raporu', [
            'rapor' => $r,
            'firma' => $r->firma,
        ])->setPaper('a4');

        $ad = 'is-kazasi-raporu-'.Str::slug($r->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
