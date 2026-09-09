<?php

namespace App\Support;

use App\Models\EgitimAtamasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uzaktan Eğitim Katılım / Başarı Belgesi (dompdf). Çalışan tüm dersleri izleyip
 * final sınavını (>= geçme puanı) geçtiğinde üretilir.
 */
class UzaktanEgitimBelgesiUretici
{
    public static function pdf(EgitimAtamasi $atama): StreamedResponse
    {
        $atama->loadMissing(['calisan.firma', 'paket', 'atayan']);
        $sinav = $atama->sinavSonuclari()->where('gecti', true)->first();

        $pdf = Pdf::loadView('pdf.uzaktan-egitim-belgesi', [
            'atama' => $atama,
            'calisan' => $atama->calisan,
            'firma' => $atama->calisan?->firma,
            'paket' => $atama->paket,
            'sinav' => $sinav,
        ])->setPaper('a4', 'landscape');

        $ad = 'uzaktan-egitim-belgesi-'.Str::slug($atama->calisan?->ad_soyad ?? 'calisan').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
