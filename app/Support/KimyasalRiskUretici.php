<?php

namespace App\Support;

use App\Models\KimyasalRiskDegerlendirmesi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kimyasal Risk Değerlendirmesi PDF'i (dompdf, A4 yatay).
 */
class KimyasalRiskUretici
{
    public static function pdf(KimyasalRiskDegerlendirmesi $d): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kimyasal-risk', [
            'kayit' => $d,
            'firma' => $d->firma,
            'gruplar' => config('isg.kimyasal_risk.tehlike_gruplari'),
            'yaklasimlar' => config('isg.kimyasal_risk.yaklasimlar'),
            'miktarlar' => config('isg.kimyasal_risk.miktarlar'),
            'ucuculuk' => config('isg.kimyasal_risk.ucuculuk'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kimyasal-risk-degerlendirmesi-'.Str::slug($d->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
