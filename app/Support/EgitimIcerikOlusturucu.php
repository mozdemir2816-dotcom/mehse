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
    public static function olustur(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi, string $egitimTuru = 'ilk'): array
    {
        if ($baslikAnahtari !== 'genel') {
            $ozel = config('isg.egitim.ozel_basliklar.'.$baslikAnahtari);

            return [
                'tip' => 'ozel',
                'ad' => $ozel['ad'] ?? $baslikAnahtari,
                'maddeler' => static::maddeleriHazirla($ozel['maddeler'] ?? []),
            ];
        }

        $sure = $egitimTuru === 'tekrar'
            ? config('isg.egitim.sureler.tekrar')
            : (config('isg.egitim.sureler.ilk.'.$tehlikeSinifi) ?? config('isg.egitim.sureler.ilk.az_tehlikeli'));

        $hedefDk = $sure['blok_fiili_dk'];
        $sektor = $sektorAnahtari ? config('isg.egitim.isyerine_ozgu_sektorler.'.$sektorAnahtari) : null;

        return [
            'tip' => 'genel',
            'saat' => $sure['saat'],
            'egitim_turu' => $egitimTuru,
            'genel_konular' => static::maddeleriOlcekle(config('isg.egitim.genel_konular', []), $hedefDk),
            'saglik_konulari' => static::maddeleriOlcekle(config('isg.egitim.saglik_konulari', []), $hedefDk),
            'teknik_konular' => static::maddeleriOlcekle(config('isg.egitim.teknik_konular', []), $hedefDk),
            'isyerine_ozgu' => $sektor ? [
                'sektor' => $sektor['ad'],
                'maddeler' => static::maddeleriHazirla($sektor['maddeler'], $hedefDk / max(count($sektor['maddeler']), 1)),
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
     * config'teki sabit madde/dakika ağırlıklarını KORUYARAK (ör. İlkyardım
     * diğerlerinden daha az ağırlıklı), hedef toplam dakikaya orantılı ölçekler
     * — Genel/Sağlık/Teknik Konular bloklarının tehlike sınıfına/eğitim türüne
     * göre değişen toplam süreye (bkz. config isg.egitim.sureler) uyması için.
     *
     * @param  array<int, string|array{madde: string, dakika: int}>  $maddeler
     * @return array<int, array{madde: string, dakika: int, dahil: bool}>
     */
    private static function maddeleriOlcekle(array $maddeler, float $hedefToplamDk): array
    {
        $agirlikToplami = collect($maddeler)->sum(fn ($m) => is_array($m) ? $m['dakika'] : self::OZEL_VARSAYILAN_DAKIKA);

        if ($agirlikToplami <= 0) {
            return static::maddeleriHazirla($maddeler);
        }

        $olcek = $hedefToplamDk / $agirlikToplami;

        return collect($maddeler)
            ->map(function ($m) use ($olcek) {
                $agirlik = is_array($m) ? $m['dakika'] : self::OZEL_VARSAYILAN_DAKIKA;
                $ad = is_array($m) ? $m['madde'] : $m;

                return ['madde' => $ad, 'dakika' => max(1, (int) round($agirlik * $olcek)), 'dahil' => true];
            })
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
