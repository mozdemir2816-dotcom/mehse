<?php

namespace App\Support;

use App\Models\Sertifika;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Sertifika PDF üretimi (dompdf) — isgpratik 66-68.jpg. Katılımcı başına
 * ayrı bir sayfa üretilir; ayrıca zip() ile her katılımcıya ayrı PDF dosyası
 * tek ZIP arşivi olarak indirilebilir.
 */
class SertifikaUretici
{
    /** @param  bool  $imzali  false = imzasız (matbu): eğitici kaşe/imza görselleri basılmaz */
    public static function pdf(Sertifika $s, bool $imzali = true, bool $tcMaskele = false, bool $arkaliOnlu = false): StreamedResponse
    {
        $s->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.sertifika', [
            'sertifika' => $s,
            'firma' => $s->firma,
            'imzali' => $imzali,
            'tcMaskele' => $tcMaskele,
            'arkaliOnlu' => $arkaliOnlu,
        ])->setPaper('a4', 'landscape');

        $ek = $arkaliOnlu ? '-arkali-onlu' : '';
        $ad = 'sertifika-'.Str::slug($s->tipEtiketi()).'-'.Str::slug($s->firma?->unvan ?? 'firma').$ek.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** Katılımcı başına ayrı PDF dosyası üreterek tek ZIP arşivi olarak indirir. */
    public static function zip(Sertifika $s, bool $imzali = true, bool $tcMaskele = false, bool $arkaliOnlu = false): BinaryFileResponse
    {
        $s->loadMissing('firma');
        $katilimcilar = array_values($s->katilimcilar ?? []);

        $geciciYol = tempnam(sys_get_temp_dir(), 'mehse_sertifika_').'.zip';
        $zip = new ZipArchive;
        $zip->open($geciciYol, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($katilimcilar as $i => $k) {
            $tekil = $s->replicate();
            $tekil->katilimcilar = [$k];
            $tekil->setRelation('firma', $s->firma);

            $pdf = Pdf::loadView('pdf.sertifika', [
                'sertifika' => $tekil,
                'firma' => $s->firma,
                'imzali' => $imzali,
                'tcMaskele' => $tcMaskele,
                'arkaliOnlu' => $arkaliOnlu,
            ])->setPaper('a4', 'landscape');

            $kisiAd = Str::slug((string) ($k['ad_soyad'] ?? 'katilimci-'.($i + 1))) ?: 'katilimci-'.($i + 1);
            $dosyaAdi = sprintf('%02d-%s-sertifika.pdf', $i + 1, $kisiAd);
            $zip->addFromString($dosyaAdi, $pdf->output());
        }

        $zip->close();

        $zipAdi = 'sertifikalar-'.Str::slug($s->tipEtiketi()).'-'.Str::slug($s->firma?->unvan ?? 'firma').'.zip';

        return response()->download($geciciYol, $zipAdi)->deleteFileAfterSend(true);
    }
}
