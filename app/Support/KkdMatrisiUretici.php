<?php

namespace App\Support;

use App\Models\KkdMatrisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KKD Seçim Matrisi PDF'i (dompdf, A4 yatay).
 */
class KkdMatrisiUretici
{
    public static function pdf(KkdMatrisi $matris): StreamedResponse
    {
        $matris->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kkd-matris', [
            'matris' => $matris,
            'firma' => $matris->firma,
            'sutunlar' => config('isg.kkd_matris.sutunlar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kkd-secim-matrisi-'.Str::slug($matris->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
