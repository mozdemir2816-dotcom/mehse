<?php

namespace App\Support;

use App\Models\KkdMatrisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KKD Seçim Matrisi PDF'i (dompdf, A4 yatay).
 */
class KkdMatrisiUretici
{
    /**
     * Katalogdan ("grup" altındaki "ad" ile eşleşen madde) KKD matrisi satırı
     * kurar — varsayılan KKD gereklilikleriyle önceden doldurulmuş.
     *
     * @return array<string, mixed>
     */
    public static function satirOlustur(string $grup, string $ad): array
    {
        $madde = collect(config('isg.kkd_matris.is_kalemleri.'.$grup, []))->firstWhere('ad', $ad);
        $degerler = $madde['v'] ?? [];

        $satir = ['is_kalemi' => $ad, 'grup' => $grup];
        foreach (array_keys(config('isg.kkd_matris.sutunlar', [])) as $sutun) {
            $satir[$sutun] = $degerler[$sutun] ?? '';
        }

        return $satir;
    }

    public static function pdf(KkdMatrisi $matris): StreamedResponse
    {
        $matris->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kkd-matris', [
            'matris' => $matris,
            'firma' => $matris->firma,
            'sutunlar' => config('isg.kkd_matris.sutunlar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kkd-secim-matrisi-'.Str::slug($matris->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
