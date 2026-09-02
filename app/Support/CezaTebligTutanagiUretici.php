<?php

namespace App\Support;

use App\Models\CezaTebligTutanagi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Ceza ve Tebliğ Tutanağı PDF üretimi (dompdf). isgpratik 79-80.jpg.
 */
class CezaTebligTutanagiUretici
{
    public static function pdf(CezaTebligTutanagi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ceza-teblig-tutanagi', [
            'tutanak' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        $ad = 'ceza-teblig-tutanagi-'.Str::slug($t->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
