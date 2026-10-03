<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;

/**
 * Yıllık çalışma + eğitim planı içeriğinin TEK kaynağı: kullanıcının VİZYON
 * firması için hazırladığı Excel'lerden birebir çıkarılan şablon
 * (config/yillik_plan_sablonu.php) — 36 faaliyet (ana konu, periyot, mevzuat,
 * kayıt notu) + 5 bölümlü eğitim planı. 4. bölüm ("İşe ve İşyerine Özgü
 * Riskler") İşe Özgü Eğitim Konuları kütüphanesinden firmanın iş kalemlerine
 * (yoksa NACE koduna) göre otomatik seçilir (IseOzguEgitimKutuphanesi). Eski genel varsayılanlar (01.10.2026) kaldırıldı;
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

    /**
     * 4. bölüm (işe özgü) şablondan değil, İşe Özgü Eğitim Konuları
     * kütüphanesinden firmanın iş kalemleri / NACE koduna göre otomatik
     * seçilir (inşaat + iş kalemi yoksa sonuç VİZYON şablonunun 6 konusudur).
     *
     * @return array{faaliyetler: array<int, array<string, mixed>>, egitimler: array<int, array<string, mixed>>}
     */
    public static function icerik(Firma $firma, int $yil): array
    {
        $kilitAy = $firma->planKilitAyIndeksi($yil);
        $icerik = self::tamSablon($kilitAy);

        $iseOzgu = IseOzguEgitimKutuphanesi::firmaIcin($firma)
            ->map(fn (array $k) => IseOzguEgitimKutuphanesi::planSatiri($k, $kilitAy))->values()->all();

        $icerik['egitimler'] = self::iseOzguYerlestir($icerik['egitimler'], $iseOzgu);

        return $icerik;
    }

    /**
     * Eğitim listesindeki 4. bölümü verilen satırlarla değiştirir; satırlar
     * "Diğer Eğitimler"den önce (yoksa sona) girer.
     *
     * @param  array<int, array<string, mixed>>  $egitimler
     * @param  array<int, array<string, mixed>>  $iseOzgu
     * @return array<int, array<string, mixed>>
     */
    public static function iseOzguYerlestir(array $egitimler, array $iseOzgu): array
    {
        $digerleri = array_values(array_filter($egitimler, fn (array $e): bool => ($e['kategori'] ?? null) !== 'ise_ozgu'));
        $konum = collect($digerleri)->search(fn (array $e) => ($e['kategori'] ?? null) === 'diger');
        $konum = $konum === false ? count($digerleri) : $konum;

        array_splice($digerleri, $konum, 0, $iseOzgu);

        return $digerleri;
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
