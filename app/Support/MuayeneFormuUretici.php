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
    public static function pdf(MuayeneFormu $m, bool $imzali = true): StreamedResponse
    {
        $m->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.muayene-formu', [
            'imzali' => $imzali,
            'form' => $m,
            'firma' => $m->firma,
        ])->setPaper('a4');

        $ad = 'muayene-formu-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
