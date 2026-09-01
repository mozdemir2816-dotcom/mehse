<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Risk Değerlendirmesi PDF üretimi (dompdf) — kapak + metodoloji (5x5/Fine-Kinney
 * ölçekleri config'ten) + risk tablosu + ekip listesi + onay.
 */
class RiskDegerlendirmesiUretici
{
    public static function pdf(RiskDegerlendirmesi $rd): StreamedResponse
    {
        $rd->loadMissing('maddeler', 'firma.user');

        $yontemAnahtari = $rd->yontem === 'fine_kinney' ? 'risk_fine_kinney' : 'risk_matris_5x5';

        $pdf = Pdf::loadView('pdf.risk-degerlendirmesi', [
            'rd' => $rd,
            'firma' => $rd->firma,
            'uzman' => $rd->firma?->user,
            'metodoloji' => config('isg.'.$yontemAnahtari),
        ])->setPaper('a4');

        $ad = 'risk-degerlendirmesi-'.Str::slug($rd->firma_unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
