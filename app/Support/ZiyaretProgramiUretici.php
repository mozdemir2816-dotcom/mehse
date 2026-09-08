<?php

namespace App\Support;

use App\Models\ZiyaretProgrami;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ziyaret Programı PDF üretimi (dompdf).
 */
class ZiyaretProgramiUretici
{
    public static function pdf(ZiyaretProgrami $p): StreamedResponse
    {
        $p->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ziyaret-programi', [
            'program' => $p,
            'firma' => $p->firma,
        ])->setPaper('a4');

        $ad = 'ziyaret-programi-'.Str::slug($p->firma?->unvan ?: 'firma').'-'.$p->yil.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
