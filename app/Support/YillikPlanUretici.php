<?php

namespace App\Support;

use App\Models\YillikPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yıllık Çalışma Planı PDF üretimi (dompdf). isgpratik 86-87.jpg.
 */
class YillikPlanUretici
{
    public static function pdf(YillikPlan $plan): StreamedResponse
    {
        $plan->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.yillik-plan', [
            'plan' => $plan,
            'firma' => $plan->firma,
            'aylar' => ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
        ])->setPaper('a4', 'landscape');

        $ad = 'yillik-plan-'.Str::slug($plan->firma?->unvan ?? 'firma').'-'.$plan->yil.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
