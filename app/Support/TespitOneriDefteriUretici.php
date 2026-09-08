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
        $defter->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.tespit-oneri-defteri', [
            'defter' => $defter,
            'firma' => $defter->firma,
        ])->setPaper('a4');

        $ad = 'tespit-oneri-defteri-'.Str::slug($defter->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
