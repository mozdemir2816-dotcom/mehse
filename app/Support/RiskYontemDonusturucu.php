<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;

/**
 * Matris (5x5) ↔ Fine-Kinney arası otomatik dönüştürme — her zaman YENİ, ayrı bir
 * kayıt oluşturur (kaynağı DEĞİŞTİRMEZ). Fine-Kinney'in üçüncü ekseni olan Frekans,
 * Matris'te hiç toplanmadığı için kullanıcıyla onaylanan sabit bir tablo ile
 * MEVCUT Olasılık değerinden türetilir; ters yönde de Matris'in tek Olasılık ekseni
 * yalnızca Fine-Kinney'in Olasılık değerinden geri türetilir (Frekans yok sayılır) —
 * böylece Matris→FK→Matris gidip gelmesi aynı değere döner. Üretilen puanlar
 * tahminidir — kullanıcı "Kayıtlı Değerlendirmeler"den açıp gözden geçirmeli/
 * düzeltmelidir.
 */
class RiskYontemDonusturucu
{
    /** Matris Olasılık (1-5) → Fine-Kinney Olasılık ölçeği. */
    private const OLASILIK_MATRIS_TO_FK = [1 => 0.2, 2 => 1.0, 3 => 3.0, 4 => 6.0, 5 => 10.0];

    /** Matris Olasılık (1-5) → Fine-Kinney FREKANS ölçeği (kullanıcı onaylı eşleme). */
    private const FREKANS_MATRIS_OLASILIK_TO_FK = [1 => 0.5, 2 => 1.0, 3 => 3.0, 4 => 6.0, 5 => 10.0];

    /** Matris Şiddet (1-5) → Fine-Kinney Şiddet ölçeği. */
    private const SIDDET_MATRIS_TO_FK = [1 => 1.0, 2 => 3.0, 3 => 7.0, 4 => 15.0, 5 => 40.0];

    /** Fine-Kinney Olasılık (tüm resmi değerler) → Matris Olasılık (1-5). */
    private const OLASILIK_FK_TO_MATRIS = [0.1 => 1, 0.2 => 1, 0.5 => 2, 1.0 => 2, 3.0 => 3, 6.0 => 4, 10.0 => 5];

    /** Fine-Kinney Şiddet (tüm resmi değerler) → Matris Şiddet (1-5). */
    private const SIDDET_FK_TO_MATRIS = [1.0 => 1, 3.0 => 2, 7.0 => 3, 15.0 => 4, 40.0 => 5];

    public static function matristenFineKinneye(RiskDegerlendirmesi $kaynak): RiskDegerlendirmesi
    {
        return static::donustur($kaynak, 'fine_kinney', function (RiskMaddesi $m): array {
            return [
                'olasilik' => static::esle($m->olasilik, self::OLASILIK_MATRIS_TO_FK),
                'frekans' => static::esle($m->olasilik, self::FREKANS_MATRIS_OLASILIK_TO_FK),
                'siddet' => static::esle($m->siddet, self::SIDDET_MATRIS_TO_FK),
                'son_olasilik' => static::esle($m->son_olasilik, self::OLASILIK_MATRIS_TO_FK),
                'son_frekans' => static::esle($m->son_olasilik, self::FREKANS_MATRIS_OLASILIK_TO_FK),
                'son_siddet' => static::esle($m->son_siddet, self::SIDDET_MATRIS_TO_FK),
                'not' => "(Mevcut durum / not: Matris'ten dönüştürüldü — O:{$m->olasilik} Ş:{$m->siddet} idi.)",
            ];
        }, 'Matris (5x5)');
    }

    public static function fineKinneydenMatrise(RiskDegerlendirmesi $kaynak): RiskDegerlendirmesi
    {
        return static::donustur($kaynak, 'matris_5x5', function (RiskMaddesi $m): array {
            return [
                'olasilik' => static::esleTersten($m->olasilik, self::OLASILIK_FK_TO_MATRIS),
                'frekans' => null,
                'siddet' => static::esleTersten($m->siddet, self::SIDDET_FK_TO_MATRIS),
                'son_olasilik' => static::esleTersten($m->son_olasilik, self::OLASILIK_FK_TO_MATRIS),
                'son_frekans' => null,
                'son_siddet' => static::esleTersten($m->son_siddet, self::SIDDET_FK_TO_MATRIS),
                'not' => "(Mevcut durum / not: Fine-Kinney'den dönüştürüldü — O:{$m->olasilik} F:{$m->frekans} Ş:{$m->siddet} idi.)",
            ];
        }, 'Fine-Kinney');
    }

    /** @param  \Closure(RiskMaddesi): array<string, mixed>  $esleyici */
    private static function donustur(RiskDegerlendirmesi $kaynak, string $hedefYontem, \Closure $esleyici, string $kaynakAdi): RiskDegerlendirmesi
    {
        $kaynak->loadMissing('maddeler');

        $hedefAdi = $hedefYontem === 'fine_kinney' ? 'Fine-Kinney' : 'Matris (5x5)';

        $yeni = RiskDegerlendirmesi::create([
            'firma_id' => $kaynak->firma_id,
            'yontem' => $hedefYontem,
            'rapor_tarihi' => now(),
            'gecerlilik_tarihi' => $kaynak->gecerlilik_tarihi,
            'ekip' => $kaynak->ekip,
            'revizyon_nedeni' => "{$kaynakAdi} değerlendirmesinden (#{$kaynak->id}) otomatik {$hedefAdi}'e dönüştürüldü — puanlar tahminidir, gözden geçirin.",
        ]);

        foreach ($kaynak->maddeler as $sira => $m) {
            $eslenen = $esleyici($m);

            RiskMaddesi::create([
                'risk_degerlendirmesi_id' => $yeni->id,
                'sira' => $sira,
                'bolum' => $m->bolum,
                'faaliyet' => $m->faaliyet,
                'tehlike' => $m->tehlike,
                'risk' => $m->risk,
                'mevcut_onlem' => $m->mevcut_onlem,
                'etkilenen_calisan' => $m->etkilenen_calisan,
                'etkilenen_diger' => $m->etkilenen_diger,
                'olasilik' => $eslenen['olasilik'],
                'frekans' => $eslenen['frekans'],
                'siddet' => $eslenen['siddet'],
                'oneri' => $m->oneri,
                'sorumlu' => $m->sorumlu,
                'termin' => $m->termin,
                'son_olasilik' => $eslenen['son_olasilik'],
                'son_frekans' => $eslenen['son_frekans'],
                'son_siddet' => $eslenen['son_siddet'],
                'durum' => $m->durum,
                'aciklama' => trim(($m->aciklama ?: '')."\n".$eslenen['not']),
            ]);
        }

        return $yeni;
    }

    /** Matris (1-5) → FK: tam sayıya yuvarlayıp tablodan okur. @param  array<int, float>  $tablo */
    private static function esle(?float $matrisDegeri, array $tablo): ?float
    {
        if ($matrisDegeri === null) {
            return null;
        }

        return $tablo[(int) round($matrisDegeri)] ?? null;
    }

    /** FK → Matris: en yakın resmi FK değerini bulup tablodan okur. @param  array<float, int>  $tablo */
    private static function esleTersten(?float $fkDegeri, array $tablo): ?int
    {
        if ($fkDegeri === null) {
            return null;
        }

        $enYakin = null;
        $enKucukFark = null;

        foreach ($tablo as $fkAnahtar => $matrisDegeri) {
            $fark = abs($fkDegeri - $fkAnahtar);

            if ($enKucukFark === null || $fark < $enKucukFark) {
                $enKucukFark = $fark;
                $enYakin = $matrisDegeri;
            }
        }

        return $enYakin;
    }
}
