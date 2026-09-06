<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu PDF üretimi (dompdf). isgpratik EĞİTİM ekranları.
 */
class EgitimKatilimUretici
{
    public static function pdf(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'icerik' => $kayit->konu_secimleri,
        ])->setPaper('a4');

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }

    /**
     * Firma/katılımcı seçmeden, yalnız konu içeriğiyle boş imza formu — eğitime
     * gelenlerin kendi el yazısıyla ad/T.C./imza atması için (en az 10 satır,
     * bkz. pdf.egitim-katilim şablonundaki satır tamamlama mantığı).
     */
    public static function bosFormPdf(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi, string $egitimTuru): StreamedResponse
    {
        $icerik = EgitimIcerikOlusturucu::olustur($baslikAnahtari, $sektorAnahtari, $tehlikeSinifi, $egitimTuru);

        $kayit = new EgitimKatilim([
            'baslik_anahtari' => $baslikAnahtari,
            'egitim_turu' => $egitimTuru,
            'sure_gun' => 1,
            'isg_uzmani_var' => true,
            'isyeri_hekimi_var' => false,
            'katilimcilar' => [],
        ]);
        $kayit->belge_no = 'BOŞ FORM';

        $pdf = Pdf::loadView('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => null,
            'icerik' => $icerik,
        ])->setPaper('a4');

        $ad = 'bos-egitim-katilim-'.Str::slug($kayit->basliklarEtiketi()).'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
