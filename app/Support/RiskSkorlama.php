<?php

namespace App\Support;

/**
 * Risk puanı ve düzeyi hesaplama — 5×5 L Tipi Matris ve Fine-Kinney.
 * Ölçek metinleri ve bantlar `config/isg.php`'de.
 */
class RiskSkorlama
{
    /**
     * @return array{puan:int|float, duzey:string, renk:string, eylem:string}
     */
    public static function hesapla(string $yontem, ?float $olasilik, ?float $siddet, ?float $frekans = null): array
    {
        if ($olasilik === null || $siddet === null || ($yontem === 'fine_kinney' && $frekans === null)) {
            return ['puan' => 0, 'duzey' => '—', 'renk' => '#6b7280', 'eylem' => ''];
        }

        $puan = $yontem === 'fine_kinney'
            ? $olasilik * $frekans * $siddet
            : $olasilik * $siddet;

        $puan = $yontem === 'fine_kinney' ? round($puan, 1) : (int) round($puan);

        return array_merge(['puan' => $puan], static::bant($yontem, $puan));
    }

    /** Puana karşılık gelen bant (üstten alta ilk eşleşen). */
    public static function bant(string $yontem, int|float $puan): array
    {
        $anahtar = $yontem === 'fine_kinney' ? 'risk_fine_kinney' : 'risk_matris_5x5';

        foreach (config("isg.$anahtar.bantlar", []) as $bant) {
            if ($puan >= $bant['min']) {
                return ['duzey' => $bant['ad'], 'renk' => $bant['renk'], 'eylem' => $bant['eylem']];
            }
        }

        return ['duzey' => '—', 'renk' => '#6b7280', 'eylem' => ''];
    }

    /** @return array<int|float, string> yöntem + eksen için ölçek seçenekleri */
    public static function olcek(string $yontem, string $eksen): array
    {
        $anahtar = $yontem === 'fine_kinney' ? 'risk_fine_kinney' : 'risk_matris_5x5';

        return config("isg.$anahtar.$eksen", []);
    }
}
