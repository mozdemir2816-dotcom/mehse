<?php

namespace App\Support;

use App\Models\OlayKaydi;
use Illuminate\Support\Collection;

/**
 * Olay Komuta 360° — olay kayıtlarından pano / analitik / vücut haritası / rapor
 * rakamları. Saf hesaplama; veritabanına dokunmaz (Collection<OlayKaydi> alır).
 */
class OlayKomuta
{
    public const AYLAR = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

    /** Kayıtlı yaralanma: ilk yardımın ötesinde tıbbi müdahale gerektirenler. */
    private const KAYITLI = ['tibbi_mudahale', 'is_gunu_kaybi', 'kalici_hasar', 'olum'];

    private const KAYIPLI = ['is_gunu_kaybi', 'kalici_hasar', 'olum'];

    /** Vücut haritası şiddet sütunları: anahtar => [kısa, ad, sonuç_türleri]. */
    public const SIDDET_GRUPLARI = [
        'hafif' => ['H', 'Hafif (yaralanmasız / ilk yardım)', ['yaralanmasiz', 'ilk_yardim', null, '']],
        'tibbi' => ['T', 'Tıbbi müdahale', ['tibbi_mudahale']],
        'kayip' => ['K', 'İş günü kaybı', ['is_gunu_kaybi']],
        'agir' => ['A', 'Kalıcı hasar / ölüm', ['kalici_hasar', 'olum']],
    ];

    public static function kayitliYaralanmaMi(OlayKaydi $o): bool
    {
        return in_array($o->sonuc_turu, self::KAYITLI, true);
    }

    public static function kayipliMi(OlayKaydi $o): bool
    {
        return in_array($o->sonuc_turu, self::KAYIPLI, true) || (int) $o->kayip_gun_sayisi > 0;
    }

    public static function ilkYardimMi(OlayKaydi $o): bool
    {
        return $o->sonuc_turu === 'ilk_yardim' || $o->olay_tipi === 'ilk_yardim';
    }

    /** @param Collection<int, OlayKaydi> $olaylar */
    public static function ozet(Collection $olaylar): array
    {
        $toplam = $olaylar->count();
        $kapanan = $olaylar->where('durum', 'kapandi')->count();

        return [
            'toplam' => $toplam,
            'yaralanma' => $olaylar->filter(fn ($o) => self::kayitliYaralanmaMi($o))->count(),
            'kayipli' => $olaylar->filter(fn ($o) => self::kayipliMi($o))->count(),
            'kayip_gun' => (int) $olaylar->sum('kayip_gun_sayisi'),
            'olumlu' => $olaylar->where('sonuc_turu', 'olum')->count(),
            'ilk_yardim' => $olaylar->filter(fn ($o) => self::ilkYardimMi($o))->count(),
            'ramak_kala' => $olaylar->where('olay_tipi', 'ramak_kala')->count(),
            'acik' => $olaylar->where('durum', '!=', 'kapandi')->count(),
            'kapanis_orani' => $toplam ? (int) round($kapanan / $toplam * 100) : 0,
        ];
    }

    /**
     * Seçili yılın 12 ayı (yıl 0 ise tüm yılların aylara göre toplamı).
     *
     * @param Collection<int, OlayKaydi> $olaylar
     * @return array<int, array{ay: string, kisa: string, olay: int, yaralanma: int, kayip_gun: int}>
     */
    public static function aylik(Collection $olaylar): array
    {
        return collect(range(1, 12))->map(function (int $ay) use ($olaylar) {
            $aydakiler = $olaylar->filter(fn ($o) => $o->olay_tarihi && (int) $o->olay_tarihi->month === $ay);

            return [
                'ay' => self::AYLAR[$ay - 1],
                'kisa' => mb_substr(self::AYLAR[$ay - 1], 0, 3),
                'olay' => $aydakiler->count(),
                'yaralanma' => $aydakiler->filter(fn ($o) => self::kayitliYaralanmaMi($o))->count(),
                'kayip_gun' => (int) $aydakiler->sum('kayip_gun_sayisi'),
            ];
        })->all();
    }

    /** @return array<int, array{etiket: string, adet: int, yuzde: int}> */
    public static function siniflandirma(Collection $olaylar, int $enFazla = 6): array
    {
        $toplam = max(1, $olaylar->count());

        $gruplar = $olaylar
            ->groupBy(fn ($o) => $o->siniflandirma
                ? config('isg.olay.siniflandirmalar.'.$o->siniflandirma, $o->siniflandirma)
                : config('isg.olay.tipler.'.$o->olay_tipi, 'Sınıflandırılmamış'))
            ->map->count()
            ->sortDesc();

        $ilk = $gruplar->take($enFazla);
        $kalan = $gruplar->skip($enFazla)->sum();
        if ($kalan > 0) {
            $ilk->put('Diğer', $kalan);
        }

        return $ilk->map(fn ($adet, $etiket) => [
            'etiket' => self::kisalt((string) $etiket),
            'adet' => (int) $adet,
            'yuzde' => (int) round($adet / $toplam * 100),
        ])->values()->all();
    }

    /** Olay türüne göre dağılım (ramak kala, iş kazası, maddi hasar …). */
    public static function turDagilimi(Collection $olaylar): array
    {
        $toplam = max(1, $olaylar->count());

        return $olaylar->groupBy(fn ($o) => $o->olay_tipi ?: 'belirsiz')
            ->map->count()->sortDesc()
            ->map(fn ($adet, $tip) => [
                'etiket' => self::kisalt((string) config('isg.olay.tipler.'.$tip, 'Belirtilmemiş')),
                'adet' => (int) $adet,
                'yuzde' => (int) round($adet / $toplam * 100),
            ])->values()->all();
    }

    /** Sonuç şiddeti dağılımı. */
    public static function siddetDagilimi(Collection $olaylar): array
    {
        return collect(config('isg.olay.sonuc_turleri'))->map(fn ($etiket, $anahtar) => [
            'anahtar' => $anahtar,
            'etiket' => $etiket,
            'adet' => $olaylar->where('sonuc_turu', $anahtar)->count(),
        ])->values()->all();
    }

    /**
     * Vücut bölgesine göre yaralanma vakaları + şiddet kırılımı.
     *
     * @return array{toplam: int, bolgeler: array<string, array>}
     */
    public static function bolgeler(Collection $olaylar): array
    {
        $yaralilar = $olaylar->filter(fn ($o) => filled($o->yaralanan_bolge));
        $toplam = $yaralilar->count();
        $tanim = config('isg.olay.vucut_bolgeleri');

        $sonuc = [];
        foreach ($tanim as $anahtar => $b) {
            $buradakiler = $yaralilar->where('yaralanan_bolge', $anahtar);
            $adet = $buradakiler->count();
            $kirilim = [];
            foreach (self::SIDDET_GRUPLARI as $g => [$kisa, , $turler]) {
                $kirilim[$g] = $buradakiler->filter(fn ($o) => in_array($o->sonuc_turu, $turler, true))->count();
            }
            $sonuc[$anahtar] = [
                'ad' => $b['ad'],
                'x' => $b['x'],
                'y' => $b['y'],
                'adet' => $adet,
                'yuzde' => $toplam ? round($adet / $toplam * 100, 1) : 0,
                'kirilim' => $kirilim,
            ];
        }

        return ['toplam' => $toplam, 'bolgeler' => $sonuc];
    }

    /** Kök neden kategorilerine göre olay sayısı (çoktan aza). */
    public static function kokNedenler(Collection $olaylar, int $enFazla = 8): array
    {
        $sayim = [];
        foreach ($olaylar as $o) {
            foreach ((array) $o->kok_neden_kategorileri as $k) {
                $sayim[$k] = ($sayim[$k] ?? 0) + 1;
            }
        }
        arsort($sayim);

        return collect($sayim)->take($enFazla)->map(fn ($adet, $k) => [
            'etiket' => self::kisalt((string) preg_replace('/\s*\(.*$/u', '', (string) config('isg.is_kazasi.kok_neden_kategorileri.'.$k, $k)), 34),
            'adet' => $adet,
        ])->values()->all();
    }

    /** Bölüm güvenlik karnesi. */
    public static function bolumKarnesi(Collection $olaylar): array
    {
        return $olaylar->groupBy(fn ($o) => trim((string) $o->bolum) ?: 'Bölüm belirtilmemiş')
            ->map(function (Collection $g, string $bolum) {
                $kapanan = $g->where('durum', 'kapandi')->count();
                $oran = (int) round($kapanan / max(1, $g->count()) * 100);
                $agir = $g->filter(fn ($o) => self::kayipliMi($o))->count();

                return [
                    'bolum' => $bolum,
                    'olay' => $g->count(),
                    'yaralanma' => $g->filter(fn ($o) => self::kayitliYaralanmaMi($o))->count(),
                    'kapanis' => $oran,
                    // Durum kuralı: iş günü kayıplı olay varsa "Riskli"; kapanış %70 altındaysa "İzleniyor".
                    'durum' => $agir > 0 ? 'Riskli' : ($oran < 70 ? 'İzleniyor' : 'Uyumlu'),
                ];
            })->sortByDesc('olay')->values()->all();
    }

    /** Yıllık karşılaştırma tablosu (olayların bulunduğu yıllar). */
    public static function yillik(Collection $olaylar): array
    {
        return $olaylar->filter(fn ($o) => $o->olay_tarihi)
            ->groupBy(fn ($o) => (int) $o->olay_tarihi->year)
            ->map(fn (Collection $g, $yil) => [
                'yil' => (int) $yil,
                'olay' => $g->count(),
                'yaralanma' => $g->filter(fn ($o) => self::kayitliYaralanmaMi($o))->count(),
                'kayipli' => $g->filter(fn ($o) => self::kayipliMi($o))->count(),
                'kayip_gun' => (int) $g->sum('kayip_gun_sayisi'),
                'ramak_kala' => $g->where('olay_tipi', 'ramak_kala')->count(),
            ])->sortKeys()->values()->all();
    }

    /** Sıklık oranları: TRIR (200 bin saat), LTIFR (1 milyon saat). Saat yoksa null. */
    public static function oranlar(array $ozet, int $calismaSaati): array
    {
        if ($calismaSaati <= 0) {
            return ['trir' => null, 'ltifr' => null];
        }

        return [
            'trir' => round($ozet['yaralanma'] * 200000 / $calismaSaati, 2),
            'ltifr' => round($ozet['kayipli'] * 1000000 / $calismaSaati, 2),
        ];
    }

    private static function kisalt(string $s, int $uzunluk = 30): string
    {
        return mb_strlen($s) > $uzunluk ? rtrim(mb_substr($s, 0, $uzunluk - 1)).'…' : $s;
    }
}
