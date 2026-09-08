<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Görevlendirme (atama) yazısı PDF üretimi (dompdf). isgpratik 38-44.jpg.
 */
class AtamaYazisiUretici
{
    public static function pdf(AtamaYazisi $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.atama-yazisi', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'rol' => $kayit->rol(),
        ])->setPaper('a4');

        $ad = 'atama-yazisi-'.Str::slug($kayit->rolEtiketi()).'-'.Str::slug($kayit->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
