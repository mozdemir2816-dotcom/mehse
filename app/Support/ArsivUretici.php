<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Arşiv "Şablondan Üret" → sistem şablonları: modüllerin mevcut üreticileri
 * (yıllık planlar, değerlendirme raporu, risk değerlendirmesi, ADEP, ekipler,
 * tatbikat, kurul tutanağı) arşive dosya olarak üretilir. Kayda dayanan
 * belgelerde (risk, ADEP, tatbikat, kurul) firmanın ilgili modülde kaydı
 * yoksa kullanıcıya nereden oluşturacağı söylenir.
 */
final class ArsivUretici
{
    /** @return array<string, string> kategori → sistem şablonu adı */
    public static function sablonlar(): array
    {
        return [
            'yillik_calisma_plani' => 'Sistem: Yıllık Çalışma Planı (Excel)',
            'yillik_egitim_plani' => 'Sistem: Yıllık Eğitim Planı (Excel)',
            'yillik_degerlendirme' => 'Sistem: Yıllık Değerlendirme Raporu (Excel)',
            'risk_degerlendirmesi' => 'Sistem: Son Risk Değerlendirmesi (PDF)',
            'acil_durum_plani' => 'Sistem: Acil Durum Planı (PDF)',
            'acil_durum_ekipleri' => 'Sistem: Acil Durum Ekipleri Listesi (PDF)',
            'tatbikat' => 'Sistem: Son Tatbikat Tutanağı (PDF)',
            'kurul_tutanagi' => 'Sistem: Son Kurul Toplantı Tutanağı (PDF)',
        ];
    }

    public static function varMi(string $kategori): bool
    {
        return isset(self::sablonlar()[$kategori]);
    }

    /**
     * @return array{icerik: string, dosya_adi: string}|string başarıda dosya, aksi halde kullanıcıya gösterilecek hata
     */
    public static function uret(string $kategori, Firma $firma, ?int $yil, ?string $tarih): array|string
    {
        $tarihC = filled($tarih) ? Carbon::parse($tarih) : now();
        $yil ??= ArsivKurali::beklenenYil($kategori) ?? (int) now()->year;
        $slug = Str::slug($firma->kisa_ad ?: $firma->unvan);

        try {
            return match ($kategori) {
                'yillik_calisma_plani' => self::excel(YillikPlanExcelUretici::calismaDoldur(YillikPlan::firmaYilIcin($firma, $yil), $tarihC), "{$yil}-calisma-plani-{$slug}.xlsx"),
                'yillik_egitim_plani' => self::excel(YillikPlanExcelUretici::egitimDoldur(YillikPlan::firmaYilIcin($firma, $yil), true, $tarihC), "{$yil}-egitim-plani-{$slug}.xlsx"),
                'yillik_degerlendirme' => self::yanit(YillikDegerlendirmeExcelUretici::excel(YillikPlan::firmaYilIcin($firma, $yil), $tarihC), "{$yil}-yillik-degerlendirme-raporu-{$slug}.xlsx"),
                'risk_degerlendirmesi' => ($rd = $firma->riskDegerlendirmeleri()->latest('id')->first())
                    ? self::yanit(RiskDegerlendirmesiUretici::pdf($rd), "risk-degerlendirmesi-{$slug}.pdf")
                    : 'Bu firmanın risk değerlendirmesi yok. Önce Risk Değerlendirmesi modülünde oluşturun.',
                'acil_durum_plani' => ($p = $firma->acilDurumPlani)
                    ? self::yanit(AcilDurumPlaniUretici::pdf($p), "acil-durum-plani-{$slug}.pdf")
                    : 'Bu firmanın acil durum planı yok. Önce Acil Durum Planı sayfasında oluşturun.',
                'acil_durum_ekipleri' => ($ekipler = AcilEkipDurumu::ekipler($firma))->isNotEmpty()
                    ? self::yanit(AcilEkipUretici::pdf($firma, $ekipler), "acil-durum-ekipleri-{$slug}.pdf")
                    : 'Bu firmada acil durum ekibi tanımlı değil. Önce Acil Durum Ekipleri sayfasında oluşturun.',
                'tatbikat' => ($t = $firma->tatbikatTutanaklari()->orderByDesc('tatbikat_tarihi')->latest('id')->first())
                    ? self::yanit(TatbikatTutanagiUretici::pdf($t), "tatbikat-tutanagi-{$slug}.pdf")
                    : 'Bu firmanın tatbikat tutanağı yok. Önce Tatbikat Tutanağı sayfasında oluşturun.',
                'kurul_tutanagi' => ($k = $firma->kurulToplantilari()->orderByDesc('tarih')->latest('id')->first())
                    ? self::yanit(KurulToplantisiUretici::pdf($k), "kurul-tutanagi-{$slug}.pdf")
                    : 'Bu firmanın kurul toplantısı kaydı yok. Önce İSG Kurulu sayfasında oluşturun.',
                default => 'Bu kategori için sistem şablonu yok.',
            };
        } catch (Throwable $e) {
            report($e);

            return 'Belge üretilemedi: '.$e->getMessage();
        }
    }

    /** @return array{icerik: string, dosya_adi: string} */
    private static function excel(Spreadsheet $kitap, string $ad): array
    {
        $gecici = tempnam(sys_get_temp_dir(), 'aru').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($gecici);
        $icerik = (string) file_get_contents($gecici);
        @unlink($gecici);

        return ['icerik' => $icerik, 'dosya_adi' => $ad];
    }

    /** İndirme yanıtı üreten mevcut üreticilerin çıktısını yakalar. @return array{icerik: string, dosya_adi: string} */
    private static function yanit(Response $yanit, string $ad): array
    {
        ob_start();
        $yanit->sendContent();

        return ['icerik' => (string) ob_get_clean(), 'dosya_adi' => $ad];
    }
}
