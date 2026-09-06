<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
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

        $ad = 'acil-durum-afis-'.$tip.'-'.strtoupper($ebat).'.pdf';

        if ($dosya = $afis['dosya'] ?? null) {
            $yol = resource_path('belge/acil-durum-afisleri/'.$dosya);

            if (is_file($yol)) {
                $icerik = static::hazirAfisiFirmaIleUret($yol, $firma, $ebat);

                return response()->streamDownload(fn () => print($icerik), $ad);
            }
        }

        $pdf = Pdf::loadView('pdf.acil-durum-afis', [
            'firma' => $firma,
            'afis' => $afis,
        ])->setPaper(strtolower($ebat), 'portrait');

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }

    /**
     * isgpratik referansındaki gibi hazır (grafik tabanlı) afiş dosyasının
     * ÜZERİNE firma adını sabit "yakıştırmak" yerine, her indirmede firma
     * bilgi şeridiyle YENİDEN birleştirir — statik dosyada başka bir firmanın
     * adı sabit yazılı kalmaz. Seçilen kağıt ebadına (A4/A3) göre afiş de
     * gerçekten ölçeklenir; A3 yalnızca dosya adında değişmez, fiziksel
     * olarak daha büyük ve baskıya uygun üretilir (setasign/fpdi ile sayfa
     * birleştirme — dompdf tek başına var olan bir PDF'i şablon olarak
     * içe aktaramadığı için).
     */
    private static function hazirAfisiFirmaIleUret(string $kaynakYol, Firma $firma, string $ebat): string
    {
        $a3 = strtolower($ebat) === 'a3';
        $bosluk = 3.0;

        $fpdi = new Fpdi();
        $fpdi->SetAutoPageBreak(false);

        $fpdi->setSourceFile(StreamReader::createByString(file_get_contents($kaynakYol)));
        $afisTpl = $fpdi->importPage(1);
        $kaynakBoyut = $fpdi->getTemplateSize($afisTpl);

        // Afişin kendi doğal yönü korunur (çoğu akış şeması yatay tasarlanır) —
        // dikey bir A4/A3 çerçeveye zorla sığdırmak posteri gereksiz küçültüp
        // üstte/altta boşluk bırakırdı.
        $yatay = ($kaynakBoyut['orientation'] ?? 'P') === 'L';
        $kisaKenar = $a3 ? 297.0 : 210.0;
        $uzunKenar = $a3 ? 420.0 : 297.0;
        $sayfaGenislikMm = $yatay ? $uzunKenar : $kisaKenar;
        $sayfaYukseklikMm = $yatay ? $kisaKenar : $uzunKenar;
        $seritYukseklikMm = $sayfaGenislikMm * 0.086;

        $seritPdf = Pdf::loadView('pdf.acil-durum-afis-serit', [
            'firma' => $firma,
            'cerceveKalinlik' => $a3 ? 3 : 2,
            'puntoUnvan' => $sayfaGenislikMm * 0.062,
            'puntoDetay' => $sayfaGenislikMm * 0.043,
        ])->setPaper([0, 0, static::mmToPt($sayfaGenislikMm), static::mmToPt($seritYukseklikMm)]);

        $fpdi->AddPage($yatay ? 'L' : 'P', [$sayfaGenislikMm, $sayfaYukseklikMm]);

        $fpdi->setSourceFile(StreamReader::createByString($seritPdf->output()));
        $seritTpl = $fpdi->importPage(1);
        $fpdi->useTemplate($seritTpl, 0, 0, $sayfaGenislikMm, $seritYukseklikMm);

        $ustY = $seritYukseklikMm + $bosluk;
        $kalanGenislik = $sayfaGenislikMm - ($bosluk * 2);
        $kalanYukseklik = $sayfaYukseklikMm - $ustY - $bosluk;

        $olcek = min($kalanGenislik / $kaynakBoyut['width'], $kalanYukseklik / $kaynakBoyut['height']);
        $hedefGenislik = $kaynakBoyut['width'] * $olcek;
        $hedefYukseklik = $kaynakBoyut['height'] * $olcek;

        $fpdi->useTemplate(
            $afisTpl,
            ($sayfaGenislikMm - $hedefGenislik) / 2,
            $ustY + (($kalanYukseklik - $hedefYukseklik) / 2),
            $hedefGenislik,
            $hedefYukseklik,
        );

        return $fpdi->Output('S');
    }

    private static function mmToPt(float $mm): float
    {
        return $mm * 2.8346456693;
    }
}
