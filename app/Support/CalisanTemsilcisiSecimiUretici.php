<?php

namespace App\Support;

use App\Models\CalisanTemsilcisiSecimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Çalışan Temsilcisi Seçim süreci belgeleri — isgpratik ATAMA YAZISI/ÇALIŞAN
 * TEMSİLCİSİ referansı (Seçim Duyuru İlanı, Aday Başvuru Dilekçesi, Kesin
 * Aday Listesi, Oy Pusulası, Atama Tutanağı). Hepsi tek bir `dompdf` şablon
 * ailesinde (`pdf.calisan-temsilcisi-*`), atama-yazisi.blade.php ile aynı
 * görsel dilde üretilir.
 */
class CalisanTemsilcisiSecimiUretici
{
    public static function duyuruPdf(CalisanTemsilcisiSecimi $secim): StreamedResponse
    {
        return static::indir($secim, 'duyuru', 'pdf.calisan-temsilcisi-duyuru');
    }

    public static function basvuruDilekcesiPdf(CalisanTemsilcisiSecimi $secim): StreamedResponse
    {
        return static::indir($secim, 'aday-basvuru-dilekcesi', 'pdf.calisan-temsilcisi-basvuru');
    }

    public static function adayListesiPdf(CalisanTemsilcisiSecimi $secim): StreamedResponse
    {
        return static::indir($secim, 'aday-listesi', 'pdf.calisan-temsilcisi-aday-listesi');
    }

    public static function oyPusulasiPdf(CalisanTemsilcisiSecimi $secim): StreamedResponse
    {
        return static::indir($secim, 'oy-pusulasi', 'pdf.calisan-temsilcisi-oy-pusulasi');
    }

    public static function tutanakPdf(CalisanTemsilcisiSecimi $secim): StreamedResponse
    {
        return static::indir($secim, 'atama-tutanagi', 'pdf.calisan-temsilcisi-tutanak');
    }

    private static function indir(CalisanTemsilcisiSecimi $secim, string $ad, string $view): StreamedResponse
    {
        $secim->loadMissing('firma');

        $pdf = Pdf::loadView($view, ['secim' => $secim, 'firma' => $secim->firma])->setPaper('a4');

        $dosyaAdi = 'calisan-temsilcisi-'.$ad.'-'.Str::slug($secim->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $dosyaAdi);
    }

    /**
     * Firmadan/kayıttan bağımsız, elle doldurulmak üzere "boş şablon" seti —
     * firma adı ve aday/çalışan listeleri boş bırakılır (fiziksel olarak
     * basılıp elle doldurulacak evrak istendiği için). Her 5 belge de aynı
     * blade'i `bos=true` ile render eder; şablonlar kendi içinde firma/aday
     * alanlarını boş çizgi/hücreyle değiştirir.
     */
    public static function bosSablonZip(): StreamedResponse
    {
        $secim = new CalisanTemsilcisiSecimi;

        $belgeler = [
            'secim-duyuru-ilani-bos.pdf' => 'pdf.calisan-temsilcisi-duyuru',
            'aday-basvuru-dilekcesi-bos.pdf' => 'pdf.calisan-temsilcisi-basvuru',
            'kesin-aday-listesi-bos.pdf' => 'pdf.calisan-temsilcisi-aday-listesi',
            'oy-pusulasi-bos.pdf' => 'pdf.calisan-temsilcisi-oy-pusulasi',
            'atama-tutanagi-bos.pdf' => 'pdf.calisan-temsilcisi-tutanak',
        ];

        $zipYolu = tempnam(sys_get_temp_dir(), 'mehse-ct-bos').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($belgeler as $dosyaAdi => $view) {
            $pdf = Pdf::loadView($view, ['secim' => $secim, 'firma' => null, 'bos' => true])->setPaper('a4');
            $zip->addFromString($dosyaAdi, $pdf->output());
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipYolu) {
            print(file_get_contents($zipYolu));
            @unlink($zipYolu);
        }, 'calisan-temsilcisi-secimi-bos-sablon-seti.zip');
    }
}
