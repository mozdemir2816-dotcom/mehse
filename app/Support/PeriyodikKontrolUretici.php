<?php

namespace App\Support;

use App\Models\PeriyodikKontrol;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İş Ekipmanları Periyodik Kontrol Takip Listesi PDF'i (dompdf).
 */
class PeriyodikKontrolUretici
{
    public static function pdf(PeriyodikKontrol $kontrol): StreamedResponse
    {
        $kontrol->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.periyodik-kontrol', [
            'kontrol' => $kontrol,
            'firma' => $kontrol->firma,
            'sonuclar' => config('isg.periyodik_kontrol.sonuclar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'periyodik-kontrol-'.Str::slug($kontrol->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
