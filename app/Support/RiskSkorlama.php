<?php

namespace App\Support;

/**
 * Risk puanı ve düzeyi hesaplama — 5×5 L Tipi Matris ve Fine-Kinney.
 * Ölçek metinleri ve bantlar `config/isg.php`'de.
 */
class RiskSkorlama
{
    /**
     * Üç eksenli (Olasılık × Frekans/D × Şiddet) çarpılan yöntemler. Diğer tüm
     * yöntemler (matris_5x5, hazop, ileride eklenecekler) iki eksenlidir
     * (Olasılık × Şiddet) — bkz. hesapla(). Yeni bir 3 eksenli yöntem eklenirse
     * buraya eklenmesi yeterlidir.
     */
    private const UC_EKSENLI_YONTEMLER = ['fine_kinney', 'fmea'];

    public static function ucEksenliMi(string $yontem): bool
    {
        return in_array($yontem, self::UC_EKSENLI_YONTEMLER, true);
    }

    /**
     * @return array{puan:int|float, duzey:string, renk:string, eylem:string}
     */
    public static function hesapla(string $yontem, ?float $olasilik, ?float $siddet, ?float $frekans = null): array
    {
        $ucEksenli = static::ucEksenliMi($yontem);

        if ($olasilik === null || $siddet === null || ($ucEksenli && $frekans === null)) {
            return ['puan' => 0, 'duzey' => '—', 'renk' => '#6b7280', 'eylem' => ''];
        }

        $puan = $ucEksenli
            ? $olasilik * $frekans * $siddet
            : $olasilik * $siddet;

        $puan = $ucEksenli ? round($puan, 1) : (int) round($puan);

        return array_merge(['puan' => $puan], static::bant($yontem, $puan));
    }

    /** Puana karşılık gelen bant (üstten alta ilk eşleşen). */
    public static function bant(string $yontem, int|float $puan): array
    {
        foreach (config('isg.risk_'.$yontem.'.bantlar', []) as $bant) {
            if ($puan >= $bant['min']) {
                return ['duzey' => $bant['ad'], 'renk' => $bant['renk'], 'eylem' => $bant['eylem']];
            }
        }

        return ['duzey' => '—', 'renk' => '#6b7280', 'eylem' => ''];
    }

    /** @return array<int|float, string> yöntem + eksen için ölçek seçenekleri */
    public static function olcek(string $yontem, string $eksen): array
    {
        return config('isg.risk_'.$yontem.'.'.$eksen, []);
    }

    /**
     * Yöntemin Olasılık/Frekans(-Saptanabilirlik)/Şiddet eksenleri için görünen
     * etiketler — FMEA'da aynı sütunlar farklı anlam taşır (Oluşma/Saptanabilirlik).
     *
     * @return array{olasilik:string, frekans:string, siddet:string}
     */
    public static function eksenEtiketleri(string $yontem): array
    {
        return match ($yontem) {
            'fmea' => ['olasilik' => 'Oluşma Olasılığı (O)', 'frekans' => 'Saptanabilirlik (D)', 'siddet' => 'Şiddet (S)'],
            'fine_kinney' => ['olasilik' => 'Olasılık', 'frekans' => 'Frekans (maruz kalma)', 'siddet' => 'Şiddet'],
            default => ['olasilik' => 'Olasılık', 'frekans' => 'Frekans', 'siddet' => 'Şiddet'],
        };
    }

    /**
     * Maddelerin O/Ş(/F) değerleri 5x5 Matris ölçeğine (tam sayı 1-5) uymuyorsa
     * veya Frekans doluysa (yalnız Fine-Kinney'de var) Fine-Kinney önerilir.
     * Excel'den gelen puanların, seçili yönteme uymadığı için ekranda "kayıp"
     * gibi görünmesini önlemek amacıyla kullanılır.
     *
     * @param  array<int, array<string, mixed>>  $maddeler
     */
    public static function fineKinneyeUyuyorMu(array $maddeler): bool
    {
        $matris5x5Puanlari = [1.0, 2.0, 3.0, 4.0, 5.0];

        foreach ($maddeler as $m) {
            if (filled($m['frekans'] ?? null)) {
                return true;
            }

            foreach (['olasilik', 'siddet'] as $alan) {
                $deger = $m[$alan] ?? null;

                if ($deger !== null && ! in_array((float) $deger, $matris5x5Puanlari, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
