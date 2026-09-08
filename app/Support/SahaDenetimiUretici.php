<?php

namespace App\Support;

use App\Models\SahaDenetimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Saha Denetimi PDF üretimi (dompdf) — isgpratik'in gerçek "Şantiye Denetim
 * ve Değerlendirme" PDF çıktısıyla birebir aynı düzen (künye + madde tablosu
 * + ekip + sabit güvenlik uyarıları + kaşe, ardından fotoğraf kanıtı sayfaları).
 */
class SahaDenetimiUretici
{
    public static function pdf(SahaDenetimi $d): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.saha-denetimi', [
            'denetim' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'saha-denetimi-'.Str::slug($d->firma?->unvan ?? 'firma').'-r'.$d->revizyon.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
