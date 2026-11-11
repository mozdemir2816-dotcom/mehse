<?php

namespace App\Support;

use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Yıllık Çalışma Planı + Yıllık Eğitim Planı + Yıllık Değerlendirme Raporu tek ZIP'te
 * (11.11.2026 kullanıcı isteği). Her belgenin hazırlanma tarihi ayrı verilir; üretim
 * tekil "Çıktı Al" düğmeleriyle aynı üreticilerden geçer.
 */
class YillikPlanTopluIndirme
{
    /**
     * @param  'form'|'ydr'  $degerlendirmeBicimi
     */
    public static function indir(
        YillikPlan $plan,
        ?Carbon $calismaTarihi,
        ?Carbon $egitimTarihi,
        ?Carbon $degerlendirmeTarihi,
        string $degerlendirmeBicimi = 'form',
        bool $imzali = true,
    ): StreamedResponse {
        $zip = static::zipOlustur($plan, $calismaTarihi, $egitimTarihi, $degerlendirmeTarihi, $degerlendirmeBicimi, $imzali);

        return response()->streamDownload(function () use ($zip) {
            echo file_get_contents($zip);
            @unlink($zip);
        }, static::dosyaAdi($plan), ['Content-Type' => 'application/zip']);
    }

    public static function dosyaAdi(YillikPlan $plan): string
    {
        $plan->loadMissing('firma');

        return 'yillik-planlar-'.Str::slug($plan->firma?->unvan ?? 'firma').'-'.$plan->yil.'.zip';
    }

    /**
     * ZIP dosyasını geçici klasöre yazar ve yolunu döner (çağıran siler).
     *
     * @param  'form'|'ydr'  $degerlendirmeBicimi
     */
    public static function zipOlustur(
        YillikPlan $plan,
        ?Carbon $calismaTarihi,
        ?Carbon $egitimTarihi,
        ?Carbon $degerlendirmeTarihi,
        string $degerlendirmeBicimi = 'form',
        bool $imzali = true,
    ): string {
        ExcelBellek::artir();
        $plan->loadMissing('firma');

        $belgeler = [
            YillikPlanExcelUretici::dosyaAdi($plan, 'yillik-calisma-plani') => fn () => YillikPlanExcelUretici::calismaDoldur($plan, $calismaTarihi),
            YillikPlanExcelUretici::dosyaAdi($plan, 'yillik-egitim-plani') => fn () => YillikPlanExcelUretici::egitimDoldur($plan, $imzali, $egitimTarihi),
        ];

        if ($degerlendirmeBicimi === 'ydr') {
            $belgeler[YillikDegerlendirmeExcelUretici::dosyaAdi($plan)] = fn () => YillikDegerlendirmeExcelUretici::doldur($plan, $degerlendirmeTarihi);
        } else {
            $belgeler[YillikDegerlendirmeFormUretici::dosyaAdi($plan->firma, $plan->yil)] = fn () => YillikDegerlendirmeFormUretici::doldur($plan->firma, $plan->yil, $degerlendirmeTarihi, $imzali);
        }

        $zipYolu = tempnam(sys_get_temp_dir(), 'ypz');
        $zip = new ZipArchive;
        if ($zip->open($zipYolu, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP dosyası oluşturulamadı.');
        }

        $gecici = [];
        try {
            foreach ($belgeler as $ad => $uret) {
                $kitap = $uret();
                $yol = tempnam(sys_get_temp_dir(), 'ypx');
                IOFactory::createWriter($kitap, 'Xlsx')->save($yol);
                // Üç kitap tek istekte üretildiği için bellek her birinden sonra bırakılır.
                $kitap->disconnectWorksheets();
                unset($kitap);

                $gecici[] = $yol;
                $zip->addFile($yol, $ad);
            }

            $zip->close();
        } finally {
            foreach ($gecici as $yol) {
                @unlink($yol);
            }
        }

        return $zipYolu;
    }
}
