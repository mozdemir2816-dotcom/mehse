<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşbaşı / Oryantasyon Eğitim Tutanağı PDF üretimi (dompdf). isgpratik 60.jpg.
 */
class IsbasiEgitimTutanagiUretici
{
    public static function pdf(IsbasiEgitimTutanagi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.isbasi-egitim-tutanagi', [
            'tutanak' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        $ad = 'isbasi-egitim-tutanagi-'.Str::slug($t->calisan_ad_soyad).'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }

    /**
     * Toplu Katılım Formu — birden çok çalışanın (yaklaşık 10 kişilik)
     * aynı işbaşı/oryantasyon eğitimine katılımını tek sayfada imzalatmak
     * için; tekil `IsbasiEgitimTutanagi` kaydı gibi veritabanına yazılmaz,
     * yalnızca anlık üretilip indirilir.
     *
     * @param  array{egitim_tarihi?: ?string, sure_saat?: ?int, egitim_yeri?: ?string, egitimi_veren?: ?string, egitim_yontemi?: ?string, belge_tarihi?: ?string, konular?: array, igu_imzasi?: bool, isyeri_hekimi_imzasi?: bool, katilimcilar?: array}  $veri
     */
    public static function katilimFormuPdf(Firma $firma, array $veri): StreamedResponse
    {
        $veri['egitim_tarihi'] = filled($veri['egitim_tarihi'] ?? null) ? Carbon::parse($veri['egitim_tarihi'])->format('d.m.Y') : null;
        $veri['belge_tarihi'] = filled($veri['belge_tarihi'] ?? null) ? Carbon::parse($veri['belge_tarihi'])->format('d.m.Y') : null;

        $pdf = Pdf::loadView('pdf.isbasi-egitim-katilim-formu', [
            'firma' => $firma,
            'veri' => $veri,
        ])->setPaper('a4');

        $ad = 'isbasi-egitim-katilim-formu-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
