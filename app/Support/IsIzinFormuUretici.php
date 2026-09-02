<?php

namespace App\Support;

use App\Models\IsIzinFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İş İzin Formu (Permit to Work) PDF üretimi (dompdf). isgpratik 76-78.jpg.
 */
class IsIzinFormuUretici
{
    public static function pdf(IsIzinFormu $form): StreamedResponse
    {
        $form->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.is-izin-formu', [
            'form' => $form,
            'firma' => $form->firma,
        ])->setPaper('a4');

        $ad = 'is-izin-formu-'.Str::slug($form->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
