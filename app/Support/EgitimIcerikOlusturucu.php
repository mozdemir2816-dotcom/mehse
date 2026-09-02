<?php

namespace App\Support;

/**
 * `config('isg.egitim')` içeriğinden bir eğitim başlığı için gösterilecek
 * konu bloklarını üretir. "genel" başlığı (İş Sağlığı ve Güvenliği) 4 sabit
 * blok içerir; diğer tüm başlıklar tek bloklu "özel" eğitimlerdir.
 *
 * Her madde `{madde, dakika, dahil}` şeklinde döner — isgpratik'in gerçek
 * ekranındaki gibi kullanıcı her maddeyi işaretleyip dakikasını
 * değiştirebilir (bkz. EgitimKatilim/SertifikaOlustur "Eğitim Konuları"
 * düzenlenebilir kontrol listesi). "Özel" başlıkların config'teki serbest
 * metin maddeleri burada varsayılan bir dakika ile nesneye çevrilir —
 * config içeriği sade tutulur, düzenlenebilirlik burada eklenir.
 */
class EgitimIcerikOlusturucu
{
    private const OZEL_VARSAYILAN_DAKIKA = 15;

    /** @return array<string, mixed> */
    public static function olustur(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi): array
    {
        if ($baslikAnahtari !== 'genel') {
            $ozel = config('isg.egitim.ozel_basliklar.'.$baslikAnahtari);

            return [
                'tip' => 'ozel',
                'ad' => $ozel['ad'] ?? $baslikAnahtari,
                'maddeler' => static::maddeleriHazirla($ozel['maddeler'] ?? []),
            ];
        }

        $sureler = config('isg.egitim.sureler.'.$tehlikeSinifi) ?? config('isg.egitim.sureler.az_tehlikeli');
        $sektor = $sektorAnahtari ? config('isg.egitim.isyerine_ozgu_sektorler.'.$sektorAnahtari) : null;

        return [
            'tip' => 'genel',
            'saat' => $sureler['saat'],
            'dinlenme_dk' => $sureler['dinlenme_dk'],
            'genel_konular' => static::maddeleriHazirla(config('isg.egitim.genel_konular', [])),
            'saglik_konulari' => static::maddeleriHazirla(config('isg.egitim.saglik_konulari', [])),
            'teknik_konular' => static::maddeleriHazirla(config('isg.egitim.teknik_konular', [])),
            'isyerine_ozgu' => $sektor ? [
                'sektor' => $sektor['ad'],
                'maddeler' => static::maddeleriHazirla($sektor['maddeler'], $sureler['ise_ozgu_dk'] / max(count($sektor['maddeler']), 1)),
            ] : null,
        ];
    }

    /**
     * @param  array<int, string|array{madde: string, dakika: int}>  $maddeler
     * @return array<int, array{madde: string, dakika: int, dahil: bool}>
     */
    private static function maddeleriHazirla(array $maddeler, float $varsayilanDakika = self::OZEL_VARSAYILAN_DAKIKA): array
    {
        return collect($maddeler)
            ->map(fn ($m) => is_array($m)
                ? ['madde' => $m['madde'], 'dakika' => $m['dakika'], 'dahil' => true]
                : ['madde' => $m, 'dakika' => (int) round($varsayilanDakika), 'dahil' => true])
            ->values()
            ->all();
    }

    /**
     * Bir kategori/bölümün toplam fiili ders (dk) ve dinlenme (dk) süresi —
     * yalnız dahil edilen maddeler (1 ders saati: 45 dk ders + 15 dk dinlenme).
     *
     * @param  array<int, array{dakika: int, dahil: bool}>  $maddeler
     * @return array{fiili: int, dinlenme: int}
     */
    public static function bolumSuresi(array $maddeler): array
    {
        $fiili = (int) collect($maddeler)->where('dahil', true)->sum('dakika');

        return ['fiili' => $fiili, 'dinlenme' => (int) round($fiili / 3)];
    }

    /** @return array<string, string> anahtar => etiket (dropdown için) */
    public static function basliklar(): array
    {
        return ['genel' => 'İş Sağlığı ve Güvenliği']
            + collect(config('isg.egitim.ozel_basliklar', []))->map(fn ($v) => $v['ad'])->all();
    }

    /** @return array<string, string> anahtar => etiket */
    public static function sektorler(): array
    {
        return collect(config('isg.egitim.isyerine_ozgu_sektorler', []))->map(fn ($v) => $v['ad'])->all();
    }
}
