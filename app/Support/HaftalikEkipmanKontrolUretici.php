<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Haftalık (Resimli) Ekipman Kontrol Formu — Periyodik Kontrol'den (resmî
 * muayene) bağımsız, operatörün sahada her hafta elle doldurduğu BOŞ
 * şablon. Kullanıcının kendi referans belgesi (41 makine türü × 12 soru,
 * config('isg.haftalik_ekipman_kontrol.tipler')) birebir esas alınır —
 * Saha Denetimi sayfasından firmada bulunan tipler seçilip tek PDF'te
 * (her tip kendi sayfasında) toplu üretilir, firmaya teslim edilir.
 */
class HaftalikEkipmanKontrolUretici
{
    /**
     * @param  array<int, int>  $secilenIndeksler  config listesindeki tip indeksleri
     */
    public static function pdf(array $secilenIndeksler, ?string $firmaUnvan = null): StreamedResponse
    {
        $tumTipler = config('isg.haftalik_ekipman_kontrol.tipler');

        $secilenler = collect($secilenIndeksler)
            ->map(fn ($i) => $tumTipler[$i] ?? null)
            ->filter()
            ->values();

        $pdf = Pdf::loadView('pdf.haftalik-ekipman-kontrol', [
            'formlar' => $secilenler,
            'firmaUnvan' => $firmaUnvan,
        ])->setPaper('a4');

        $ad = 'haftalik-ekipman-kontrol-'.Str::slug($firmaUnvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
