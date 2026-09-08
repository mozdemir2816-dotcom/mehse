<?php

namespace App\Support;

use App\Models\SahaAnalizi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Saha Gözetim Raporu PDF üretimi (dompdf) — isgpratik'in gerçek "Çoklu
 * DÖF" çıktısıyla (Coklu-DOF-...pdf örneği) birebir aynı kolon düzeni.
 */
class SahaAnaliziUretici
{
    public static function pdf(SahaAnalizi $s): StreamedResponse
    {
        $s->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.saha-analiz-raporu', [
            'rapor' => $s,
            'firma' => $s->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'saha-gozetim-raporu-'.Str::slug($s->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
