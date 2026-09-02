<?php

namespace App\Support;

/**
 * `config('isg.egitim')` içeriğinden bir eğitim başlığı için gösterilecek
 * konu bloklarını üretir. "genel" başlığı (İş Sağlığı ve Güvenliği) 4 sabit
 * blok içerir; diğer tüm başlıklar tek bloklu "özel" eğitimlerdir.
 */
class EgitimIcerikOlusturucu
{
    /** @return array<string, mixed> */
    public static function olustur(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi): array
    {
        if ($baslikAnahtari !== 'genel') {
            $ozel = config('isg.egitim.ozel_basliklar.'.$baslikAnahtari);

            return [
                'tip' => 'ozel',
                'ad' => $ozel['ad'] ?? $baslikAnahtari,
                'maddeler' => $ozel['maddeler'] ?? [],
            ];
        }

        $sureler = config('isg.egitim.sureler.'.$tehlikeSinifi) ?? config('isg.egitim.sureler.az_tehlikeli');
        $sektor = $sektorAnahtari ? config('isg.egitim.isyerine_ozgu_sektorler.'.$sektorAnahtari) : null;

        return [
            'tip' => 'genel',
            'saat' => $sureler['saat'],
            'dinlenme_dk' => $sureler['dinlenme_dk'],
            'genel_konular' => config('isg.egitim.genel_konular', []),
            'saglik_konulari' => config('isg.egitim.saglik_konulari', []),
            'teknik_konular' => config('isg.egitim.teknik_konular', []),
            'isyerine_ozgu' => $sektor ? [
                'sektor' => $sektor['ad'],
                'dakika' => $sureler['ise_ozgu_dk'],
                'maddeler' => $sektor['maddeler'],
            ] : null,
        ];
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
