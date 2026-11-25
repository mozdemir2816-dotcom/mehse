<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    /**
     * Bir firma için hedef yılın (ör. 2027) Yıllık Eğitim Planını hazırlar:
     * hedef yıl planı yoksa veya $oncekiYildanAktar=true ise bir önceki yılın (ör. 2026)
     * hazırlanmış eğitim satırlarını (4. bölüm + Online/Yüz Yüze modu dahil) yeni yıla
     * taze P (planlandi) aylarıyla aktarır ve saatleri tehlike sınıfına göre sabitler.
     */
    public static function egitimPlaniniYilIcinHazirla(Firma $firma, int $yil, bool $oncekiYildanAktar = true): YillikPlan
    {
        $mevcut = YillikPlan::where('firma_id', $firma->id)->where('yil', $yil)->first();
        $onceki = $oncekiYildanAktar
            ? YillikPlan::where('firma_id', $firma->id)->where('yil', $yil - 1)->first()
            : null;

        $plan = $mevcut ?? YillikPlan::firmaYilIcin($firma, $yil);

        if ($onceki && ! empty($onceki->egitimler) && (! $mevcut || empty($mevcut->egitimler))) {
            $kilit = $firma->planKilitAyIndeksi($yil);
            $mod = YillikEgitimPlanlayici::moduTespitEt($onceki->egitimler, $firma);
            $satirlar = collect($onceki->egitimler)->map(function (array $satir) use ($kilit): array {
                $aylar = array_fill(0, 12, 'bos');
                foreach (($satir['aylar'] ?? []) as $ay => $durum) {
                    if ($ay >= $kilit && $ay <= 11 && in_array($durum, ['planlandi', 'tamamlandi', 'planlandi_gerceklesti', 'P', 'G', 'PG'], true)) {
                        $aylar[$ay] = 'planlandi';
                    }
                }
                $satir['aylar'] = $aylar;

                return $satir;
            })->values()->all();

            $satirlar = YillikEgitimPlanlayici::satirlaraUygula($satirlar, $firma, $mod);
            $plan->update(['egitimler' => $satirlar]);
            $plan->refresh();
        } else {
            // Mevcut 2027 planı varsa da yöntem/saat tutarlılığını garanti et
            $mod = YillikEgitimPlanlayici::moduTespitEt($plan->egitimler ?? [], $firma);
            $guncel = YillikEgitimPlanlayici::satirlaraUygula($plan->egitimler ?? [], $firma, $mod);
            if ($guncel !== ($plan->egitimler ?? [])) {
                $plan->update(['egitimler' => $guncel]);
                $plan->refresh();
            }
        }

        return $plan;
    }

    /**
     * Verilen firmaların belirtilen yıl Yıllık Eğitim Planı Excel dosyalarını,
     * her firma için ayrı klasör açarak (`<Firma Adı>/yillik-egitim-plani-...xlsx`) tek ZIP'te üretir.
     *
     * @param  Collection<int, Firma>  $firmalar
     * @return array{zip_yolu: string, adet: int, klasorler: array<int, string>}
     */
    public static function tumFirmalarEgitimZip(
        Collection $firmalar,
        int $yil,
        ?Carbon $hazirlanma = null,
        bool $imzali = true,
        bool $oncekiYildanAktar = true,
    ): array {
        ExcelBellek::artir();
        @set_time_limit(0);

        $hazirlanma ??= Carbon::parse(\App\Filament\Pages\YillikPlanlar::varsayilanHazirlanmaTarihi($yil));

        $zipYolu = tempnam(sys_get_temp_dir(), 'ypez');
        $zip = new ZipArchive;
        if ($zip->open($zipYolu, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Toplu ZIP dosyası oluşturulamadı.');
        }

        $gecici = [];
        $klasorler = [];
        $kullanilanKlasorler = [];

        try {
            foreach ($firmalar as $firma) {
                $plan = static::egitimPlaniniYilIcinHazirla($firma, $yil, $oncekiYildanAktar);
                $klasor = static::guvenliKlasorAdi((string) $firma->unvan, (int) $firma->id, $kullanilanKlasorler);
                $dosyaAdi = YillikPlanExcelUretici::dosyaAdi($plan, 'yillik-egitim-plani');

                $kitap = YillikPlanExcelUretici::egitimDoldur($plan, $imzali, $hazirlanma);
                $yol = tempnam(sys_get_temp_dir(), 'ypex');
                IOFactory::createWriter($kitap, 'Xlsx')->save($yol);
                $kitap->disconnectWorksheets();
                unset($kitap);

                $gecici[] = $yol;
                $zip->addFile($yol, $klasor.'/'.$dosyaAdi);
                $klasorler[] = $klasor;

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $zip->close();
        } finally {
            foreach ($gecici as $yol) {
                @unlink($yol);
            }
        }

        return [
            'zip_yolu' => $zipYolu,
            'adet' => count($klasorler),
            'klasorler' => $klasorler,
        ];
    }

    /** Windows & ZIP uyumlu temiz firma klasör adı (mükerrerse firma ID eklenir). */
    public static function guvenliKlasorAdi(string $unvan, int $firmaId, array &$kullanilan): string
    {
        $temiz = trim(preg_replace('/[\\\\\/:*?"<>|\r\n\t]+/u', ' ', $unvan) ?? '');
        $temiz = trim(preg_replace('/\s{2,}/u', ' ', $temiz) ?? '', " .\t\n\r\0\x0B");
        if ($temiz === '') {
            $temiz = 'Firma-'.$firmaId;
        }
        $temiz = mb_substr($temiz, 0, 80, 'UTF-8');
        $temiz = rtrim($temiz, " .");

        $anahtar = mb_strtolower($temiz, 'UTF-8');
        if (isset($kullanilan[$anahtar])) {
            $temiz .= ' ('.$firmaId.')';
            $anahtar = mb_strtolower($temiz, 'UTF-8');
        }
        $kullanilan[$anahtar] = true;

        return $temiz;
    }
}
