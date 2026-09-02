<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu PDF üretimi (dompdf). isgpratik EĞİTİM ekranları.
 */
class EgitimKatilimUretici
{
    public static function pdf(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'icerik' => $kayit->konu_secimleri,
        ])->setPaper('a4');

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
