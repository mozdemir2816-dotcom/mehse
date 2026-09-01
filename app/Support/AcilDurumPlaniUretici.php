<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Acil Durum Eylem Planı ve acil durum afişleri PDF üretimi (dompdf).
 * isgpratik 19-21, 146-154.jpg.
 */
class AcilDurumPlaniUretici
{
    public static function pdf(AcilDurumPlani $plan): StreamedResponse
    {
        $plan->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.acil-durum-plani', [
            'plan' => $plan,
            'firma' => $plan->firma,
            'hakkinda' => config('isg.acil_durum.hakkinda'),
        ])->setPaper('a4');

        $ad = 'acil-durum-plani-'.Str::slug($plan->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }

    public static function afis(Firma $firma, string $tip, string $ebat = 'a4'): StreamedResponse
    {
        $afis = config('isg.acil_durum.afisler.'.$tip);

        abort_if(! $afis, 404, 'Afiş bulunamadı');

        if ($dosya = $afis['dosya'] ?? null) {
            $yol = resource_path('belge/acil-durum-afisleri/'.$dosya);

            if (is_file($yol)) {
                return response()->streamDownload(
                    fn () => print(file_get_contents($yol)),
                    'acil-durum-afis-'.$tip.'-'.strtoupper($ebat).'.pdf',
                );
            }
        }

        $pdf = Pdf::loadView('pdf.acil-durum-afis', [
            'firma' => $firma,
            'afis' => $afis,
        ])->setPaper(strtolower($ebat), 'portrait');

        $ad = 'acil-durum-afis-'.$tip.'-'.strtoupper($ebat).'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
