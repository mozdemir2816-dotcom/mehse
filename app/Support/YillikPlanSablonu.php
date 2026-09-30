<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;

/**
 * Yıllık plan içerik şablonları. "Şantiye" şablonu kullanıcının VİZYON (inşaat)
 * firması için hazırladığı eğitim/çalışma planından birebir çıkarıldı
 * (config/yillik_plan_santiye.php): 5 bölümlü eğitim planı (4. bölüm "İşe ve
 * İşyerine Özgü Riskler") + 36 faaliyetli, ana konu gruplu çalışma planı.
 * İnşaat firmalarında (NACE 41–43 ya da seçili inşaat iş kalemi) yeni plan bu
 * şablonla açılır; diğer firmalar config isg.yillik_plan varsayılanlarıyla.
 */
class YillikPlanSablonu
{
    public static function santiyeMi(Firma $firma): bool
    {
        return filled($firma->is_kalemleri)
            || (bool) preg_match('/^4[123]/', preg_replace('/\D/', '', (string) $firma->nace_kodu));
    }

    /** @return array{faaliyetler: array<int, array<string, mixed>>, egitimler: array<int, array<string, mixed>>} */
    public static function icerik(Firma $firma, int $yil): array
    {
        $kilitAy = $firma->planKilitAyIndeksi($yil);

        if (! self::santiyeMi($firma)) {
            return [
                'faaliyetler' => YillikPlan::maddeAylarIle(config('isg.yillik_plan.varsayilan_faaliyetler'), $kilitAy),
                'egitimler' => YillikPlan::maddeAylarIle(config('isg.yillik_plan.varsayilan_egitimler'), $kilitAy),
            ];
        }

        return self::santiye($kilitAy);
    }

    /** @return array{faaliyetler: array<int, array<string, mixed>>, egitimler: array<int, array<string, mixed>>} */
    public static function santiye(int $kilitAy = 0): array
    {
        return [
            'faaliyetler' => YillikPlan::maddeAylarIle(config('yillik_plan_santiye.faaliyetler', []), $kilitAy),
            'egitimler' => YillikPlan::maddeAylarIle(config('yillik_plan_santiye.egitimler', []), $kilitAy),
        ];
    }

    /**
     * Eğitim planı Excel'inin alt bilgileri (katılanlar, toplam süre, bölüm
     * süreleri ve bölüm açıklamaları) — şantiye şablonundaki metinler.
     *
     * @return array{katilanlar: string, toplam_sure: string, bolum_sureleri: array<string, string>, bolum_aciklamalari: array<string, string>}
     */
    public static function egitimBilgileri(): array
    {
        return config('yillik_plan_santiye.egitim_bilgileri');
    }
}
