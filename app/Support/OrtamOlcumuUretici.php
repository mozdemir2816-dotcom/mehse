<?php

namespace App\Support;

use App\Models\OrtamOlcumu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ortam Ölçümleri Takip Listesi PDF'i (dompdf, A4 yatay).
 */
class OrtamOlcumuUretici
{
    public static function pdf(OrtamOlcumu $olcum): StreamedResponse
    {
        $olcum->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ortam-olcumleri', [
            'olcum' => $olcum,
            'firma' => $olcum->firma,
            'sonuclar' => config('isg.ortam_olcum.sonuclar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'ortam-olcumleri-'.Str::slug($olcum->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
