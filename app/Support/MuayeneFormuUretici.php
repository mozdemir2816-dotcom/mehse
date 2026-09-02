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
    public static function pdf(MuayeneFormu $m): StreamedResponse
    {
        $m->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.muayene-formu', [
            'form' => $m,
            'firma' => $m->firma,
        ])->setPaper('a4');

        $ad = 'muayene-formu-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
