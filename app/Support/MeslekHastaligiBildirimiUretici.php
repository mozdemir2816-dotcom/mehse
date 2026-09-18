<?php

namespace App\Support;

use App\Models\MeslekHastaligiBildirimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Meslek Hastalığı Bildirimi kayıt formu PDF üretimi (dompdf).
 */
class MeslekHastaligiBildirimiUretici
{
    public static function pdf(MeslekHastaligiBildirimi $m): StreamedResponse
    {
        $m->loadMissing('firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.meslek-hastaligi-bildirimi', [
            'form' => $m,
            'firma' => $m->firma,
        ])->setPaper('a4');

        $ad = 'meslek-hastaligi-bildirimi-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
