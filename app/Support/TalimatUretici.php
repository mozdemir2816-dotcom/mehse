<?php

namespace App\Support;

use App\Models\Talimat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Çalışma Talimatı PDF üretimi (dompdf). isgpratik 82-83.jpg.
 */
class TalimatUretici
{
    public static function pdf(Talimat $talimat): StreamedResponse
    {
        $talimat->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.talimat', [
            'talimat' => $talimat,
            'firma' => $talimat->firma,
        ])->setPaper('a4');

        $ad = 'talimat-'.Str::slug($talimat->baslik).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
