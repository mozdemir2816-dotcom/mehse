<?php

namespace App\Support;

use App\Models\AcilDurumKrokisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcilDurumKrokisiUretici
{
    public static function pdf(AcilDurumKrokisi $kroki): StreamedResponse
    {
        $kroki->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.acil-durum-krokisi', [
            'kroki' => $kroki,
            'firma' => $kroki->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'acil-durum-krokisi-'.str($kroki->firma?->unvan ?: 'firma')->slug().'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
