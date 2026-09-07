<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\JsaSablonu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSA (İşe Özgü Risk Değerlendirmesi) PDF üretimi (dompdf). Şablon bölünmez —
 * tüm iş adımları + notlar + imza bloğu tek belgede. Firma verilirse yalnız
 * künyeye (üst bilgi) eklenir; analiz gövdesine dokunulmaz.
 */
class JsaUretici
{
    public static function pdf(JsaSablonu $sablon, ?Firma $firma = null): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.jsa', [
            'sablon' => $sablon,
            'firma' => $firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'jsa-'.Str::slug($sablon->baslik ?: 'ise-ozgu-risk')
            .($firma ? '-'.Str::slug($firma->unvan) : '').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
