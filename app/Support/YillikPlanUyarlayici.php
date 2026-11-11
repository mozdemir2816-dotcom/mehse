<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;

/**
 * Bir firmanın yıllık planını (çalışma / eğitim / değerlendirme) başka bir firmaya
 * uyarlayarak kopyalar (11.11.2026 kullanıcı isteği: 2027 planları bir inşaat
 * firmasında hazırlanıp diğer inşaat firmalarına aynen uygulanır).
 *
 * Uyarlama kuralları:
 *  - Satırlar ve P (planlandı) ayları kaynaktan gelir; kaynaktaki G (gerçekleşti) başka
 *    firma için geçerli olmadığından P'ye döner. Hedefin kendi G'leri korunur, ayrıca
 *    hedefin sistem kayıtlarından yeniden işlenir.
 *  - Hedefte atanmış uzman (sözleşme) başlangıcından önceki aylar boş kalır.
 *  - İSG Kurulu ayları hedefin çalışan sayısı ve tehlike sınıfına göre yeniden yerleşir.
 *  - Eğitim planı 4. bölüm: hedefin iş kalemleri varsa onlara göre kütüphaneden gelir,
 *    yoksa kaynaktaki satırlar kalır.
 *  - Değerlendirme: elle yazılmış satırlar kopyalanır (içindeki kaynak firma adı hedefinkiyle
 *    değişir); sistemden dolan satırlar kaynağın verisini taşımasın diye hedefin kendi
 *    kayıtlarından yeniden doldurulur.
 */
class YillikPlanUyarlayici
{
    public const BOLUMLER = [
        'faaliyetler' => 'Yıllık Çalışma Planı',
        'egitimler' => 'Yıllık Eğitim Planı',
        'degerlendirmeler' => 'Yıllık Değerlendirme Raporu',
    ];

    /** @param  array<int, string>  $bolumler  BOLUMLER anahtarları */
    public static function uyarla(YillikPlan $kaynak, Firma $hedef, array $bolumler): YillikPlan
    {
        $kaynak->loadMissing('firma');
        $yil = (int) $kaynak->yil;
        $kilitAy = $hedef->planKilitAyIndeksi($yil);

        $plan = YillikPlan::firstOrNew(['firma_id' => $hedef->id, 'yil' => $yil]);

        if (in_array('faaliyetler', $bolumler, true)) {
            $faaliyetler = static::satirlariAktar($kaynak->faaliyetler ?? [], $plan->faaliyetler ?? [], 'faaliyet', $kilitAy);
            $plan->faaliyetler = YillikPlanSablonu::kurulPeriyodunuYerlestir($faaliyetler, $hedef, $kilitAy);
        }

        if (in_array('egitimler', $bolumler, true)) {
            $egitimler = static::satirlariAktar($kaynak->egitimler ?? [], $plan->egitimler ?? [], 'konu', $kilitAy);

            if (filled($hedef->is_kalemleri)) {
                $iseOzgu = IseOzguEgitimKutuphanesi::firmaIcin($hedef)
                    ->map(fn (array $k) => IseOzguEgitimKutuphanesi::planSatiri($k, $kilitAy))->values()->all();
                if ($iseOzgu !== []) {
                    $egitimler = YillikPlanSablonu::iseOzguYerlestir($egitimler, $iseOzgu);
                }
            }

            $plan->egitimler = $egitimler;
        }

        if (in_array('degerlendirmeler', $bolumler, true)) {
            $plan->degerlendirmeler = static::degerlendirmeAktar($kaynak, $hedef);
        }

        // Yeni planda seçilmeyen bölümler hedef firmanın standart şablonuyla açılır.
        if (! $plan->exists) {
            $icerik = YillikPlanSablonu::icerik($hedef, $yil);
            $plan->faaliyetler ??= $icerik['faaliyetler'];
            $plan->egitimler ??= $icerik['egitimler'];
            $plan->degerlendirmeler ??= YillikDegerlendirmeVerisi::planiDoldur($hedef, $yil, [])['satirlar'];
        }

        $plan->save();

        EgitimKonulari::kayitlariPlanaIsle($plan);
        YillikPlanGerceklesme::isle($plan);

        return $plan->fresh();
    }

    /**
     * @param  array<int, array<string, mixed>>  $kaynak
     * @param  array<int, array<string, mixed>>  $mevcut  hedefin şimdiki satırları (G'leri korunur)
     * @return array<int, array<string, mixed>>
     */
    private static function satirlariAktar(array $kaynak, array $mevcut, string $metinAlani, int $kilitAy): array
    {
        $mevcutG = [];
        foreach ($mevcut as $m) {
            $anahtar = EgitimKonulari::normalize((string) ($m[$metinAlani] ?? ''));
            foreach (($m['aylar'] ?? []) as $ay => $durum) {
                if ($durum === 'tamamlandi') {
                    $mevcutG[$anahtar][$ay] = true;
                }
            }
        }

        return array_map(function (array $satir) use ($mevcutG, $metinAlani, $kilitAy): array {
            $anahtar = EgitimKonulari::normalize((string) ($satir[$metinAlani] ?? ''));
            $aylar = [];

            for ($ay = 0; $ay < 12; $ay++) {
                $durum = $satir['aylar'][$ay] ?? 'bos';
                $durum = match (true) {
                    isset($mevcutG[$anahtar][$ay]) => 'tamamlandi',
                    $ay < $kilitAy => 'bos',
                    $durum === 'tamamlandi' => 'planlandi',
                    default => $durum,
                };
                $aylar[$ay] = $durum;
            }

            return [...$satir, 'aylar' => $aylar];
        }, array_values($kaynak));
    }

    /** @return array<int, array<string, mixed>> */
    private static function degerlendirmeAktar(YillikPlan $kaynak, Firma $hedef): array
    {
        $sablon = collect(YillikDegerlendirmeVerisi::sablonSatirlari())->keyBy('anahtar');
        $kaynakFirma = $kaynak->firma;

        $satirlar = collect($kaynak->degerlendirmeler ?? [])->map(function (array $s) use ($sablon, $kaynakFirma, $hedef): array {
            if ($s['elle'] ?? false) {
                return static::firmaAdiniDegistir($s, $kaynakFirma, $hedef);
            }

            // Sistemden dolmuş satır kaynağın kayıtlarını taşır: şablon hâline döner, hedefin verisiyle dolar.
            return $sablon->get($s['anahtar'] ?? '') ?? [...$s, 'tarih' => null, 'sonuc' => '', 'otomatik' => false];
        })->values()->all();

        return YillikDegerlendirmeVerisi::planiDoldur($hedef, (int) $kaynak->yil, $satirlar)['satirlar'];
    }

    /** Elle yazılmış metinlerde kaynak firmanın unvanı / kısa adı hedef firmanınkiyle değişir. */
    private static function firmaAdiniDegistir(array $satir, ?Firma $kaynak, Firma $hedef): array
    {
        if (! $kaynak) {
            return $satir;
        }

        $degisim = array_filter([
            trim((string) $kaynak->unvan) => (string) $hedef->unvan,
            trim((string) $kaynak->kisa_ad) => (string) ($hedef->kisa_ad ?: $hedef->unvan),
        ], fn ($yeni, $eski) => mb_strlen($eski) >= 3, ARRAY_FILTER_USE_BOTH);

        // Uzun ad önce: kısa ad unvanın başıysa unvan bütün olarak değişsin.
        uksort($degisim, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($satir as $alan => $deger) {
            if (is_string($deger) && $deger !== '') {
                $satir[$alan] = strtr($deger, $degisim);
            }
        }

        return $satir;
    }
}
