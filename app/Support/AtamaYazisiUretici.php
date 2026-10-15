<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Görevlendirme (atama) yazısı PDF üretimi (dompdf). isgpratik 38-44.jpg.
 */
class AtamaYazisiUretici
{
    /** Boş (elle doldurulacak) formda kişi alanlarının yerine basılan noktalı çizgi. */
    public const BOS_ALAN = '.......................................';

    /**
     * Kaydedilmeyen, kişi alanları noktalı boş atama yazısı (elle doldurmak için) — Atama
     * Yazıları "Boş Form" ve firma evrak paketi ortak. Toplu rol (risk ekibi) her ekip
     * görevi için bir sayfa; diğerleri $adet sayfa.
     */
    public static function bosKayit(Firma $firma, string $rol, int $adet = 1, ?string $tarih = null, ?string $isverenVekili = null): AtamaYazisi
    {
        $uye = fn (?string $ekipGorevi = null) => array_filter([
            'ad_soyad' => self::BOS_ALAN, 'tc' => self::BOS_ALAN, 'gorev' => self::BOS_ALAN, 'bas_uye' => false, 'ekip_gorevi' => $ekipGorevi,
        ], fn ($v) => $v !== null);

        $uyeler = (config('isg.atama.roller.'.$rol.'.tip') === 'toplu')
            ? array_map($uye, ['İş Güvenliği Uzmanı', 'İşyeri Hekimi', 'Çalışan Temsilcisi', 'Destek Elemanı', 'Bilgi Sahibi Çalışan'])
            : array_fill(0, max(1, min(30, $adet)), $uye());

        $kayit = new AtamaYazisi([
            'firma_id' => $firma->id,
            'rol_anahtari' => $rol,
            'tarih' => $tarih,
            'isveren_vekili_adi' => $isverenVekili ?? ($firma->isveren_vekili ?: $firma->isveren_ad),
            'uyeler' => $uyeler,
        ]);
        $kayit->dokuman_no = 'BOŞ FORM';
        $kayit->setRelation('firma', $firma);

        return $kayit;
    }

    public static function pdf(AtamaYazisi $kayit, bool $imzali = true): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.atama-yazisi', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'rol' => $kayit->rol(),
            'imzali' => $imzali,
        ])->setPaper('a4');

        $ad = 'atama-yazisi-'.Str::slug($kayit->rolEtiketi()).'-'.Str::slug($kayit->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
