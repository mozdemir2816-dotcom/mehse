<?php

namespace App\Support;

use App\Models\OnBilgilendirme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ön Bilgilendirme yazısının Tebliğ-Tebellüğ Tutanağı (dompdf). */
class OnBilgilendirmeUretici
{
    public static function tutanak(OnBilgilendirme $b): StreamedResponse
    {
        $b->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.on-bilgilendirme-tutanagi', ['b' => $b, 'firma' => $b->firma])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), 'teblig-tutanagi-'.Str::slug($b->konu).'.pdf');
    }
}
