<?php

namespace App\Support;

use App\Models\EgitimSinavi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Soruları sınav kağıdı PDF üretimi (dompdf). isgpratik 69-70.jpg.
 * Her katılımcı için ayrı sınav sayfası; `cevap_anahtari_dahil` ise sonda
 * cevap anahtarı sayfası eklenir.
 */
class EgitimSinaviUretici
{
    public static function pdf(EgitimSinavi $sinav): StreamedResponse
    {
        $sinav->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.egitim-sinavi', [
            'sinav' => $sinav,
            'firma' => $sinav->firma,
        ])->setPaper('a4');

        $ad = 'egitim-sinavi-'.Str::slug($sinav->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
