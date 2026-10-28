<?php

namespace App\Support;

use App\Models\RiskMaddesi;

/**
 * Hastane risk şablonu için puan / RG harfi yardımcıları (config/hastane_risk.php).
 */
class HastaneRisk
{
    /** RP = O × F × Ş; üç eksenden biri boşsa null. */
    public static function puan(?float $o, ?float $f, ?float $s): ?float
    {
        return $o === null || $f === null || $s === null ? null : round($o * $f * $s, 1);
    }

    /** RP → RG harfi (A kırmızı … E yeşil). */
    public static function harf(?float $puan): ?string
    {
        if ($puan === null) {
            return null;
        }

        foreach (config('hastane_risk.bantlar') as $bant) {
            if ($puan >= $bant['min']) {
                return $bant['harf'];
            }
        }

        return 'E';
    }

    public static function renk(?string $harf): ?string
    {
        foreach (config('hastane_risk.bantlar') as $bant) {
            if ($bant['harf'] === $harf) {
                return $bant['renk'];
            }
        }

        return null;
    }

    /**
     * Hastane modunda madde puanları: RP hesaplanır, boş "Son Risk" boş kalır, sorumlu/termin
     * varsayılanla doldurulmaz. RG harfi dosyadan geldiği gibi korunur; yalnız puan değiştiyse
     * veya boşsa yeniden hesaplanır.
     */
    public static function maddeyiHesapla(RiskMaddesi $m): void
    {
        $puanDegisti = ! $m->exists || $m->isDirty(['olasilik', 'frekans', 'siddet']);
        $m->puan = static::puan($m->olasilik, $m->frekans, $m->siddet);
        if ($m->puan === null) {
            $m->duzey = null;
        } elseif ($puanDegisti && ! ($m->isDirty('duzey') && filled($m->duzey)) || blank($m->duzey)) {
            $m->duzey = static::harf($m->puan);
        }

        $sonDegisti = ! $m->exists || $m->isDirty(['son_olasilik', 'son_frekans', 'son_siddet']);
        $m->son_puan = static::puan($m->son_olasilik, $m->son_frekans, $m->son_siddet);
        if ($m->son_puan === null) {
            $m->son_duzey = null;
        } elseif ($sonDegisti && ! ($m->isDirty('son_duzey') && filled($m->son_duzey)) || blank($m->son_duzey)) {
            $m->son_duzey = static::harf($m->son_puan);
        }
    }
}
