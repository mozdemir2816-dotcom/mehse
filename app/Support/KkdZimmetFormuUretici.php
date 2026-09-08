<?php

namespace App\Support;

use App\Models\KkdZimmetFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KKD Zimmet Formu PDF üretimi (dompdf). isgpratik 71-75.jpg. Her çalışan
 * için ayrı teslim tutanağı sayfası; seçilen KKD seti hepsinde aynıdır.
 */
class KkdZimmetFormuUretici
{
    public static function pdf(KkdZimmetFormu $form): StreamedResponse
    {
        $form->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kkd-zimmet-formu', [
            'form' => $form,
            'firma' => $form->firma,
        ])->setPaper('a4');

        $ad = 'kkd-zimmet-formu-'.Str::slug($form->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
