<?php

namespace App\Support;

use App\Models\IsbasiEgitimTutanagi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşbaşı / Oryantasyon Eğitim Tutanağı PDF üretimi (dompdf). isgpratik 60.jpg.
 */
class IsbasiEgitimTutanagiUretici
{
    public static function pdf(IsbasiEgitimTutanagi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.isbasi-egitim-tutanagi', [
            'tutanak' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        $ad = 'isbasi-egitim-tutanagi-'.Str::slug($t->calisan_ad_soyad).'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
