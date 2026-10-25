<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\YillikPlan;
use Illuminate\Support\Str;

/**
 * Temel İSG eğitimi konularının ORTAK ANA LİSTESİ (25.10.2026, kullanıcı
 * kararı): Eğitim Katılım, Yıllık Eğitim Planı ve sertifikalar aynı konuyu
 * sabit bir kodla (G1–G4 genel, S1–S5 sağlık, T1–T12 teknik) tanır. Metinler
 * `config('isg.egitim.*_konulari')`'ndan gelir; plan şablonu (VİZYON Excel'i),
 * eski planlar ve eski katılım kayıtlarındaki farklı yazımlar ES_ANLAMLILAR ile
 * aynı koda bağlanır. Eğitim kaydedildiğinde plan hücresi kendiliğinden
 * "tamamlandı" olur (planiIsle).
 */
class EgitimKonulari
{
    public const BLOKLAR = ['genel_konular' => 'genel', 'saglik_konulari' => 'saglik', 'teknik_konular' => 'teknik'];

    /** Ana metinden farklı yazımlar (normalize edilmiş halleriyle karşılaştırılır). */
    private const ES_ANLAMLILAR = [
        'S2' => ['Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması'],
        'S5' => ['Tütün ürünlerinin zararları ve pasif etkilenim', 'Tütün ürünlerinin zararları', 'Bağımlılık yapıcı maddelerin zararları'],
        'T3' => ['Parlama, patlama, yangın ve yangından korunma'],
        'T8' => ['İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması', 'İş kazalarının sebepleri'],
        'T9' => ['Güvenlik ve sağlık işaretleri'],
        'T12' => ['Tahliye ve kurtarma', 'Acil durumlar', 'Acil durum, tahliye ve kurtarma'],
    ];

    /** Birleşik yazılmış eski plan satırı → kapsadığı tüm kodlar. */
    private const BIRLESIK = [
        'Parlama, patlama, yangın ve yangından korunma' => ['T3', 'T4'],
    ];

    /** @return array<string, array{kod: string, madde: string, kategori: string}> kod => konu (sıralı) */
    public static function tumu(): array
    {
        $liste = [];

        foreach (self::BLOKLAR as $blok => $kategori) {
            foreach (config('isg.egitim.'.$blok, []) as $m) {
                $liste[$m['kod']] = ['kod' => $m['kod'], 'madde' => $m['madde'], 'kategori' => $kategori];
            }
        }

        return $liste;
    }

    public static function normalize(?string $metin): string
    {
        return Str::of((string) $metin)->replace(['İ', 'I'], ['i', 'ı'])->lower()->ascii()
            ->replaceMatches('/[^a-z0-9 ]/', ' ')->squish()->toString();
    }

    /** Konu metninin kodu (ana metin veya eş anlamlı); bulunamazsa null. */
    public static function kodBul(?string $metin): ?string
    {
        return self::kodlarBul($metin)[0] ?? null;
    }

    /** @return array<int, string> Birleşik satırlarda birden fazla kod döner. */
    public static function kodlarBul(?string $metin): array
    {
        $n = self::normalize($metin);

        if ($n === '') {
            return [];
        }

        foreach (self::BIRLESIK as $birlesik => $kodlar) {
            if (self::normalize($birlesik) === $n) {
                return $kodlar;
            }
        }

        foreach (self::tumu() as $kod => $konu) {
            if (self::normalize($konu['madde']) === $n) {
                return [$kod];
            }
        }

        foreach (self::ES_ANLAMLILAR as $kod => $yazimlar) {
            foreach ($yazimlar as $y) {
                if (self::normalize($y) === $n) {
                    return [$kod];
                }
            }
        }

        return [];
    }

    /** Bir konu maddesinin kodu: kayıtlı `kod` alanı, yoksa metinden. */
    public static function maddeKodu(array $madde, string $metinAlani = 'madde'): ?string
    {
        return $madde['kod'] ?? self::kodBul($madde[$metinAlani] ?? null);
    }

    /**
     * Yıllık eğitim planı satırlarını ana listeye çevirir: temel (genel/sağlık/
     * teknik) satırlar kodlanır, metni ve kategorisi ana listeden gelir; birleşik
     * satır bölünür; aynı koda düşen satırlar ay durumlarıyla birleştirilir;
     * planda hiç olmayan temel konu kendi kategorisinin sonuna eklenir (ay durumu
     * kategorideki ilk satırdan, "tamamlandı"lar "planlandı" sayılarak). İşe özgü,
     * diğer ve eşleşmeyen satırlara dokunulmaz.
     *
     * @param  array<int, array<string, mixed>>  $satirlar
     * @return array<int, array<string, mixed>>
     */
    public static function planSatirlariniCevir(array $satirlar): array
    {
        $ana = self::tumu();
        $kodlu = [];   // kod => satır
        $digerleri = [];
        $sira = [];    // çıktı sırası: ['kod', x] | ['satir', i]

        foreach (array_values($satirlar) as $i => $s) {
            $temel = in_array($s['kategori'] ?? null, self::BLOKLAR, true) || isset($s['kod']);
            $kodlar = $temel ? (isset($s['kod']) ? [$s['kod']] : self::kodlarBul($s['konu'] ?? null)) : [];
            $kodlar = array_values(array_filter($kodlar, fn ($k) => isset($ana[$k])));

            if (! $kodlar) {
                $digerleri[$i] = $s;
                $sira[] = ['satir', $i];

                continue;
            }

            foreach ($kodlar as $kod) {
                $yeni = [...$s, 'kod' => $kod, 'konu' => $ana[$kod]['madde'], 'kategori' => $ana[$kod]['kategori']];

                if (isset($kodlu[$kod])) {
                    $kodlu[$kod]['aylar'] = self::aylariBirlestir($kodlu[$kod]['aylar'] ?? [], $yeni['aylar'] ?? []);
                } else {
                    $kodlu[$kod] = $yeni;
                    $sira[] = ['kod', $kod];
                }
            }
        }

        // Eksik temel konular, kendi kategorisindeki son koddan sonra.
        foreach ($ana as $kod => $konu) {
            if (isset($kodlu[$kod])) {
                continue;
            }

            $ornek = collect($kodlu)->firstWhere('kategori', $konu['kategori']);
            $kodlu[$kod] = [
                'kod' => $kod,
                'konu' => $konu['madde'],
                'kategori' => $konu['kategori'],
                'hedef' => $konu['madde'].' hakkında çalışanları bilgilendirmek',
                'egitici' => $ornek['egitici'] ?? 'İŞ GÜVENLİĞİ UZMANI',
                'hafta' => $ornek['hafta'] ?? null,
                'aylar' => array_map(fn ($d) => $d === 'tamamlandi' ? 'planlandi' : $d, $ornek['aylar'] ?? array_fill(0, 12, 'bos')),
            ];

            $konum = self::sonKonum($sira, $kodlu, $konu['kategori']);
            array_splice($sira, $konum, 0, [['kod', $kod]]);
        }

        // Temel konular ana listedeki sırayla, kategori blokları yerinde kalır.
        $sonuc = [];
        $kategoriSirasi = array_flip(array_keys($ana));
        $bekleyen = collect($sira)->where(0, 'kod')->pluck(1)->sortBy(fn ($k) => $kategoriSirasi[$k])->values()->all();
        $j = 0;

        foreach ($sira as [$tur, $x]) {
            $sonuc[] = $tur === 'kod' ? $kodlu[$bekleyen[$j++]] : $digerleri[$x];
        }

        return $sonuc;
    }

    /** Kategorinin son kodlu satırından sonraki sıra konumu (yoksa ilk temel olmayan bloktan önce). */
    private static function sonKonum(array $sira, array $kodlu, string $kategori): int
    {
        $konum = null;

        foreach ($sira as $i => [$tur, $x]) {
            if ($tur === 'kod' && ($kodlu[$x]['kategori'] ?? null) === $kategori) {
                $konum = $i + 1;
            }
        }

        if ($konum !== null) {
            return $konum;
        }

        foreach ($sira as $i => [$tur]) {
            if ($tur === 'satir') {
                return $i;
            }
        }

        return count($sira);
    }

    /** Ay durumları birleşimi: tamamlandı > planlandı > boş. */
    private static function aylariBirlestir(array $a, array $b): array
    {
        $agirlik = ['bos' => 0, 'planlandi' => 1, 'tamamlandi' => 2];
        $sonuc = [];

        for ($ay = 0; $ay < 12; $ay++) {
            $x = $a[$ay] ?? 'bos';
            $y = $b[$ay] ?? 'bos';
            $sonuc[$ay] = ($agirlik[$y] ?? 0) > ($agirlik[$x] ?? 0) ? $y : $x;
        }

        return $sonuc;
    }

    /**
     * Plan, temel konularda henüz kodlanmamış satır içeriyor mu? (Çevrilmiş
     * planda kullanıcının sildiği konu tekrar eklenmesin diye eksik kod sayılmaz.)
     */
    public static function cevrilmeliMi(array $satirlar): bool
    {
        return collect($satirlar)->contains(fn ($s) => in_array($s['kategori'] ?? null, self::BLOKLAR, true) && ! isset($s['kod']));
    }

    /**
     * Temel İSG eğitimi kaydındaki işlenen (dahil) konuları, firmanın o yılki
     * planında eğitim ayına "tamamlandı" işler: temel konular koddan, işe özgü
     * konular metinden eşleşir. Plan yoksa açılmaz. İşaretlenen hücre sayısı döner.
     */
    public static function planiIsle(EgitimKatilim $kayit): int
    {
        if ($kayit->baslik_anahtari !== 'genel' || ! $kayit->belge_tarihi || ! $kayit->firma) {
            return 0;
        }

        $plan = YillikPlan::query()->where('firma_id', $kayit->firma_id)->where('yil', (int) $kayit->belge_tarihi->year)->first();

        if (! $plan) {
            return 0;
        }

        $plan = YillikPlan::firmaYilIcin($kayit->firma, (int) $kayit->belge_tarihi->year);
        [$satirlar, $sayi] = self::isaretle($plan->egitimler ?? [], $kayit);

        if ($sayi) {
            $plan->update(['egitimler' => $satirlar]);
        }

        return $sayi;
    }

    /**
     * Firmanın plan yılındaki TÜM temel İSG eğitimi kayıtlarını plana işler
     * (geçmiş kayıtlar — 25.10.2026 kullanıcı kararı). Tekrar çalıştırmak
     * güvenlidir; yalnız "tamamlandı" olmayan hücreler işaretlenir. Değişiklik
     * varsa planı kaydeder; işaretlenen hücre sayısı döner.
     */
    public static function kayitlariPlanaIsle(YillikPlan $plan): int
    {
        $satirlar = $plan->egitimler ?? [];
        $toplam = 0;

        $kayitlar = EgitimKatilim::query()
            ->where('firma_id', $plan->firma_id)
            ->where('baslik_anahtari', 'genel')
            ->whereYear('belge_tarihi', $plan->yil)
            ->orderBy('belge_tarihi')
            ->get();

        foreach ($kayitlar as $kayit) {
            [$satirlar, $sayi] = self::isaretle($satirlar, $kayit);
            $toplam += $sayi;
        }

        if ($toplam) {
            $plan->update(['egitimler' => $satirlar]);
        }

        return $toplam;
    }

    /**
     * @param  array<int, array<string, mixed>>  $satirlar
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private static function isaretle(array $satirlar, EgitimKatilim $kayit): array
    {
        $icerik = $kayit->konu_secimleri ?? [];
        $kodlar = [];
        $ozgu = [];

        foreach (array_keys(self::BLOKLAR) as $blok) {
            foreach ($icerik[$blok] ?? [] as $m) {
                if (($m['dahil'] ?? true) && ($kod = self::maddeKodu($m))) {
                    $kodlar[$kod] = true;
                }
            }
        }

        foreach ($icerik['isyerine_ozgu']['maddeler'] ?? [] as $m) {
            if ($m['dahil'] ?? true) {
                $ozgu[self::normalize($m['madde'] ?? '')] = true;
            }
        }

        $ay = (int) $kayit->belge_tarihi->month - 1;
        $sayi = 0;

        foreach ($satirlar as $i => $s) {
            $eslesti = isset($s['kod'])
                ? isset($kodlar[$s['kod']])
                : (($s['kategori'] ?? null) === 'ise_ozgu' && isset($ozgu[self::normalize($s['konu'] ?? '')]));

            if ($eslesti && ($s['aylar'][$ay] ?? 'bos') !== 'tamamlandi') {
                $satirlar[$i]['aylar'][$ay] = 'tamamlandi';
                $sayi++;
            }
        }

        return [$satirlar, $sayi];
    }
}
