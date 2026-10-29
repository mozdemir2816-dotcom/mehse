<?php

namespace App\Support;

use App\Models\IsIzinFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İş İzin Formu (Permit to Work) PDF üretimi (dompdf). isgpratik 76-78.jpg.
 */
class IsIzinFormuUretici
{
    /** Formun imzaya hazır çıktısındaki QR (SVG data URI — GD gerektirmez); izin kimliğini düz metin taşır. */
    public static function qrDataUri(IsIzinFormu $form): string
    {
        $metin = 'PTW | '.$form->izin_no.' | '.($form->firma?->unvan ?? '—').' | '.$form->durumEtiketi();

        return (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))->render($metin);
    }

    public static function pdf(IsIzinFormu $form): StreamedResponse
    {
        $form->loadMissing('firma', 'sahaDenetimi');

        $pdf = Pdf::loadView('pdf.is-izin-formu', [
            'form' => $form,
            'firma' => $form->firma,
        ])->setPaper('a4');

        $ad = 'is-izin-formu-'.Str::slug($form->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
