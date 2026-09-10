<?php

namespace App\Support;

use App\Models\IseDonusBelgesi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşe Dönüş Belgesi PDF'i (dompdf, A4 dikey).
 */
class IseDonusBelgesiUretici
{
    public static function pdf(IseDonusBelgesi $b): StreamedResponse
    {
        $b->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ise-donus-belgesi', [
            'belge' => $b,
            'firma' => $b->firma,
        ])->setPaper('a4');

        $ad = 'ise-donus-belgesi-'.Str::slug($b->calisan_ad_soyad ?: 'calisan').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
