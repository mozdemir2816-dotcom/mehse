<?php

namespace App\Support;

use App\Models\TatbikatTutanagi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tatbikat Tutanağı PDF üretimi (dompdf). isgpratik 61-65.jpg.
 */
class TatbikatTutanagiUretici
{
    public static function pdf(TatbikatTutanagi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.tatbikat-tutanagi', [
            'tutanak' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        $ad = 'tatbikat-tutanagi-'.Str::slug($t->senaryoEtiketi()).'-'.Str::slug($t->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
