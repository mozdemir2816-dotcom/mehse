<?php

namespace App\Support;

use App\Models\Sertifika;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sertifika PDF üretimi (dompdf) — isgpratik 66-68.jpg. Katılımcı başına
 * ayrı bir sayfa üretilir.
 */
class SertifikaUretici
{
    /** @param  bool  $imzali  false = imzasız (matbu): eğitici kaşe/imza görselleri basılmaz */
    public static function pdf(Sertifika $s, bool $imzali = true): StreamedResponse
    {
        $s->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.sertifika', [
            'sertifika' => $s,
            'firma' => $s->firma,
            'imzali' => $imzali,
        ])->setPaper('a4', 'landscape');

        $ad = 'sertifika-'.Str::slug($s->tipEtiketi()).'-'.Str::slug($s->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
