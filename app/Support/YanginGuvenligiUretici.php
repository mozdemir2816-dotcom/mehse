<?php

namespace App\Support;

use App\Models\YanginGuvenligiDegerlendirmesi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yangın Güvenliği Genel Durum Değerlendirme Raporu PDF'i (dompdf, A4 dikey).
 */
class YanginGuvenligiUretici
{
    public static function pdf(YanginGuvenligiDegerlendirmesi $d): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.yangin-guvenligi', [
            'kayit' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4');

        $ad = 'yangin-guvenligi-degerlendirme-raporu-'.Str::slug($d->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
