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
            // "Hazırlayan" imza satırı firmaya atanmış İSG Uzmanı'ndan doldurulur.
            'uzman' => $firma?->igu,
        ])->setPaper('a4', 'landscape');

        $ad = 'jsa-'.Str::slug($sablon->baslik ?: 'ise-ozgu-risk')
            .($firma ? '-'.Str::slug($firma->unvan) : '').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * Kütüphaneden seçilen birden çok JSA'yı TEK PDF'te birleştirir (her analiz
     * kendi sayfasında). Firmada birden fazla işin — kazı, elektrik tesisatı vb.
     * — analizini tek belgede toplamak için.
     *
     * @param  iterable<int, JsaSablonu>  $sablonlar
     */
    public static function topluPdf(iterable $sablonlar, ?Firma $firma = null): StreamedResponse
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $pdf = Pdf::loadView('pdf.jsa-toplu', [
            'sablonlar' => $sablonlar,
            'firma' => $firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'jsa-toplu-'.($firma ? Str::slug($firma->unvan) : 'genel').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
