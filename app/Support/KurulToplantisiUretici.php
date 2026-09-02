<?php

namespace App\Support;

use App\Models\KurulToplantisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Kurulu toplantı tutanağı PDF üretimi (dompdf).
 * isgpratik yardım/kurul-toplantisi rehberi — "PDF İçeriği" listesi.
 */
class KurulToplantisiUretici
{
    public static function pdf(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kurul-toplantisi', [
            'toplanti' => $toplanti,
            'firma' => $toplanti->firma,
        ])->setPaper('a4');

        $ad = 'kurul-toplantisi-'.Str::slug($toplanti->firma?->unvan ?? 'firma').'-'.$toplanti->tarih?->format('Y-m-d').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
