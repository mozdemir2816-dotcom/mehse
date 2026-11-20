<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Müşteri Firma / İşveren İSG Uyum Karnesi (1 Sayfalık Yönetici Özeti PDF)
 * — isgpratik "Firma Yetkilendir / İşveren Özeti" karşılığı.
 */
class IsverenFirmaKarnesiUretici
{
    public static function pdf(Firma $firma): StreamedResponse
    {
        $firma->loadMissing(['igu', 'isyeriHekimi']);
        $matris = PortfoyKarne::hizliEvrakMatrisi($firma);
        $kontenjan = collect(PortfoyKarne::ekipKontenjanTablosu($firma->user_id, $firma->id))->first();

        $calisan = max((int) $firma->calisanlar()->where('aktif', true)->count(), (int) $firma->calisan_sayisi);
        $sinif = $firma->tehlike_sinifi;
        $iguDk = $calisan * match ($sinif) { 'cok_tehlikeli' => 40, 'tehlikeli' => 20, default => 10 };
        $hekimDk = $calisan * match ($sinif) { 'cok_tehlikeli' => 15, 'tehlikeli' => 10, default => 5 };
        $dspDk = ($sinif === 'cok_tehlikeli' && $calisan >= 10)
            ? $calisan * ($calisan < 50 ? 10 : ($calisan < 250 ? 15 : 20))
            : 0;

        $pdf = Pdf::loadView('pdf.isveren-firma-karnesi', [
            'firma' => $firma,
            'calisan' => $calisan,
            'ozet' => $matris['ozet'],
            'firmaEvraklari' => $matris['firma_evraklari'],
            'calisanEvraklari' => $matris['calisan_evraklari'],
            'kontenjan' => $kontenjan,
            'sureler' => ['igu_dk' => $iguDk, 'hekim_dk' => $hekimDk, 'dsp_dk' => $dspDk],
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        $ad = 'isveren-isg-uyum-karnesi-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
