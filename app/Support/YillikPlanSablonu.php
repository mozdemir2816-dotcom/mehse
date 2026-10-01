<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;

/**
 * Yıllık çalışma + eğitim planı içeriğinin TEK kaynağı: kullanıcının VİZYON
 * firması için hazırladığı Excel'lerden birebir çıkarılan şablon
 * (config/yillik_plan_sablonu.php) — 36 faaliyet (ana konu, periyot, mevzuat,
 * kayıt notu) + 5 bölümlü eğitim planı. 4. bölüm ("İşe ve İşyerine Özgü
 * Riskler") şantiyeye özgü konularla yalnız inşaat firmalarında (NACE 41–43 ya
 * da seçili inşaat iş kalemi) dolu gelir; diğer firmalarda boş başlar, firmaya
 * özgü konular elle eklenir. Eski genel varsayılanlar (01.10.2026) kaldırıldı;
 * eski yapıdaki kayıtlı planlar ilk açılışta bir kez bu şablona çevrilir
 * (YillikPlan::firmaYilIcin → eskiYapidaMi).
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
        $icerik = self::tamSablon($firma->planKilitAyIndeksi($yil));

        if (! self::santiyeMi($firma)) {
            $icerik['egitimler'] = array_values(array_filter(
                $icerik['egitimler'],
                fn (array $e): bool => ($e['kategori'] ?? null) !== 'ise_ozgu',
            ));
        }

        return $icerik;
    }

    /**
     * Şablonun tamamı (4. bölüm dahil, firma ayrımı yapılmadan).
     *
     * @return array{faaliyetler: array<int, array<string, mixed>>, egitimler: array<int, array<string, mixed>>}
     */
    public static function tamSablon(int $kilitAy = 0): array
    {
        return [
            'faaliyetler' => YillikPlan::maddeAylarIle(config('yillik_plan_sablonu.faaliyetler', []), $kilitAy),
            'egitimler' => YillikPlan::maddeAylarIle(config('yillik_plan_sablonu.egitimler', []), $kilitAy),
        ];
    }

    /**
     * 01.10.2026 öncesi (eski genel varsayılanlarla) oluşturulmuş plan mı?
     * Yeni şablonun faaliyetlerinde `ana_konu`, eğitimlerinde `hafta` vardır;
     * ikisi de hiç yoksa ve plan boş değilse eski yapıdadır.
     */
    public static function eskiYapidaMi(YillikPlan $plan): bool
    {
        $faaliyetler = collect($plan->faaliyetler ?? []);
        $egitimler = collect($plan->egitimler ?? []);

        if ($faaliyetler->isEmpty() && $egitimler->isEmpty()) {
            return false;
        }

        return ! $faaliyetler->contains(fn ($f) => array_key_exists('ana_konu', (array) $f))
            && ! $egitimler->contains(fn ($e) => array_key_exists('hafta', (array) $e));
    }

    /**
     * Eğitim planı Excel'inin alt bilgileri (katılanlar, toplam süre, bölüm
     * süreleri ve bölüm açıklamaları) — şablondaki metinler.
     *
     * @return array{katilanlar: string, toplam_sure: string, bolum_sureleri: array<string, string>, bolum_aciklamalari: array<string, string>}
     */
    public static function egitimBilgileri(): array
    {
        return config('yillik_plan_sablonu.egitim_bilgileri');
    }
}
