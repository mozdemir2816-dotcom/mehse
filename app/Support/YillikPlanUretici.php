<?php

namespace App\Support;

use App\Models\YillikPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yıllık Değerlendirme Raporu PDF üretimi (dompdf). Çalışma ve eğitim planı
 * artık kullanıcının Excel şablonlarıyla üretilir (YillikPlanExcelUretici).
 */
class YillikPlanUretici
{
    public static function pdf(YillikPlan $plan, bool $imzali = true): StreamedResponse
    {
        $plan->loadMissing(['firma.igu', 'firma.isyeriHekimi']);

        $pdf = Pdf::loadView('pdf.yillik-plan', [
            'plan' => $plan,
            'firma' => $plan->firma,
            'aylar' => ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'],
            'imzali' => $imzali,
        ])->setPaper('a4', 'landscape');

        $ad = 'yillik-plan-'.Str::slug($plan->firma?->unvan ?? 'firma').'-'.$plan->yil.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
