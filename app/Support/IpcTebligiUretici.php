<?php

namespace App\Support;

use App\Models\IpcTebligi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşverene İPC (İdari Para Cezası) Tebliği PDF üretimi (dompdf).
 */
class IpcTebligiUretici
{
    public static function pdf(IpcTebligi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ipc-tebligi', [
            'tebligi' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        $ad = 'ipc-tebligi-'.Str::slug($t->firma?->unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
