<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kimyasal Ürün Envanter Listesi PDF'i (dompdf) — Kimyasal Maddelerle Çalışmalarda
 * İSG Yönetmeliği kapsamında istenen tehlikeli kimyasal envanteri.
 */
class KimyasalEnvanterUretici
{
    public static function pdf(Firma $firma): StreamedResponse
    {
        $urunler = $firma->kimyasalUrunler()->where('aktif', true)->orderBy('urun_adi')->get();

        $pdf = Pdf::loadView('pdf.kimyasal-envanter', [
            'firma' => $firma,
            'urunler' => $urunler,
            'ghsTanim' => config('isg.kimyasal.ghs'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kimyasal-envanter-'.Str::slug($firma->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
