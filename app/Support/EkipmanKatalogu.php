<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use Illuminate\Support\Collection;

/**
 * Ekipman Kataloğu (config/ekipman_katalogu.php) — firmaya eklenen iş ekipmanını
 * risk değerlendirmesi, kontrol formu ve periyodik muayene takibine bağlar.
 *
 * Tekrar önleme (kullanıcı isteği, 13.10.2026): kütüphane maddesi değerlendirmede
 * varsa — tehlike_id ile ya da elle yazılmışsa metin benzerliğiyle — bir daha
 * önerilmez; "gerekmiyor" denen maddeler risk_degerlendirmeleri.haric_tehlikeler'de
 * tutulur ve hiç önerilmez.
 */
class EkipmanKatalogu
{
    /** Elle yazılmış maddede "aynı tehlike" sayılacak metin benzerliği (%). */
    private const BENZERLIK_ESIGI = 85;

    /** tehlike_id eşleşse bile metin bu kadar uzaklaşmışsa (başka DB'den gelen id) sayılmaz. */
    private const ID_BENZERLIK_ESIGI = 40;

    public const ORTAK_ETIKET = 'Ortak makine güvenliği';

    /** @return array{risk_kategorileri: array<int, string>, kontrol_formu: ?string, ortak_riskler?: bool}|null */
    public static function tanim(?string $tip): ?array
    {
        $t = config('ekipman_katalogu.tipler.'.$tip);

        return is_array($t) ? $t + ['risk_kategorileri' => [], 'kontrol_formu' => null] : null;
    }

    /** Kategori periyodik muayene (vize) takibine tabi mi? Bilinmeyen kategori: evet. */
    public static function periyodikMi(?string $kategori): bool
    {
        return config('isg.periyodik_kontrol.kategoriler.'.$kategori.'.periyodik', true) !== false;
    }

    /** @return array<int, string> */
    public static function periyodikOlmayanKategoriler(): array
    {
        return collect(config('isg.periyodik_kontrol.kategoriler', []))
            ->filter(fn (array $k) => ($k['periyodik'] ?? true) === false)
            ->keys()->all();
    }

    /** @return Collection<int, IsEkipmani> */
    public static function ekipmanlar(Firma $firma): Collection
    {
        return IsEkipmani::query()->where('firma_id', $firma->id)->where('aktif', true)->get();
    }

    /**
     * Firmanın ekipmanlarından beklenen kütüphane maddeleri, ekipman tipine göre gruplu.
     * Bir madde birden çok grupta çıkmaz (ilk grup kazanır).
     *
     * @return array<string, Collection<int, Tehlike>>
     */
    public static function beklenenRiskler(Firma $firma): array
    {
        $ekipmanlar = static::ekipmanlar($firma);
        $gruplar = [];

        foreach ($ekipmanlar->pluck('tip')->filter()->unique() as $tip) {
            $kategoriler = static::tanim($tip)['risk_kategorileri'] ?? [];
            if ($kategoriler) {
                $gruplar[$tip] = $kategoriler;
            }
        }

        $ortak = $ekipmanlar
            ->filter(fn (IsEkipmani $e) => (static::tanim($e->tip)['ortak_riskler'] ?? true) !== false)
            ->pluck('kategori')->unique()
            ->flatMap(fn ($k) => config('ekipman_katalogu.kategori_ortak_riskleri.'.$k, []))
            ->unique()->values()->all();
        if ($ortak) {
            $gruplar[self::ORTAK_ETIKET] = $ortak;
        }

        if (! $gruplar) {
            return [];
        }

        $tehlikeler = Tehlike::query()
            ->with('kategori:id,anahtar')
            ->whereHas('kategori', fn ($q) => $q->whereIn('anahtar', collect($gruplar)->flatten()->unique()->all()))
            ->orderBy('kod')->orderBy('id')
            ->get()
            ->groupBy(fn (Tehlike $t) => $t->kategori->anahtar);

        $sonuc = [];
        $gorulen = [];
        foreach ($gruplar as $etiket => $anahtarlar) {
            $liste = collect($anahtarlar)
                ->flatMap(fn ($a) => $tehlikeler->get($a, collect()))
                ->reject(fn (Tehlike $t) => isset($gorulen[$t->id]))
                ->each(function (Tehlike $t) use (&$gorulen) { $gorulen[$t->id] = true; })
                ->values();
            if ($liste->isNotEmpty()) {
                $sonuc[$etiket] = $liste;
            }
        }

        return $sonuc;
    }

    /** Firmanın en güncel risk değerlendirmesi. */
    public static function sonDegerlendirme(Firma $firma): ?RiskDegerlendirmesi
    {
        return RiskDegerlendirmesi::query()->where('firma_id', $firma->id)
            ->orderByDesc('rapor_tarihi')->orderByDesc('id')->first();
    }

    /**
     * Risk değerlendirmesinde olmayan, "gerekmiyor" denmemiş ekipman maddeleri.
     *
     * @return array{rd: ?RiskDegerlendirmesi, gruplar: array<string, Collection<int, Tehlike>>, toplam: int}
     */
    public static function riskEksikleri(Firma $firma, ?RiskDegerlendirmesi $rd = null): array
    {
        $beklenen = static::beklenenRiskler($firma);
        if (! $beklenen) {
            return ['rd' => $rd, 'gruplar' => [], 'toplam' => 0];
        }

        $rd ??= static::sonDegerlendirme($firma);
        if (! $rd) {
            return ['rd' => null, 'gruplar' => $beklenen, 'toplam' => collect($beklenen)->sum->count()];
        }

        $haric = array_map('intval', $rd->haric_tehlikeler ?? []);
        $maddeler = $rd->maddeler()->get(['id', 'tehlike_id', 'tehlike'])
            ->map(fn (RiskMaddesi $m) => ['id' => $m->tehlike_id, 'n' => static::norm($m->tehlike)]);

        $gruplar = [];
        foreach ($beklenen as $etiket => $tehlikeler) {
            $eksik = $tehlikeler
                ->reject(fn (Tehlike $t) => in_array($t->id, $haric, true) || static::kapsaniyor($t, $maddeler))
                ->values();
            if ($eksik->isNotEmpty()) {
                $gruplar[$etiket] = $eksik;
            }
        }

        return ['rd' => $rd, 'gruplar' => $gruplar, 'toplam' => collect($gruplar)->sum->count()];
    }

    /**
     * Seçilen kütüphane maddelerini değerlendirmeye ekler; listelenip seçilmeyenleri
     * "gerekmiyor" olarak kaydeder (bir daha önerilmez). Revizyon no/nedeni güncellenir.
     *
     * @param  array<int, int>  $eklenecekIds
     * @param  array<int, int>  $gerekmeyenIds
     * @param  array<int, string>  $ekipmanlar  revizyon nedeni için
     */
    public static function revizeEt(RiskDegerlendirmesi $rd, array $eklenecekIds, array $gerekmeyenIds, array $ekipmanlar): int
    {
        $fk = RiskSkorlama::ucEksenliMi($rd->yontem);
        $sira = (int) $rd->maddeler()->max('sira');
        $eklenen = 0;

        foreach (Tehlike::query()->whereIn('id', $eklenecekIds)->orderBy('kod')->orderBy('id')->get() as $t) {
            $m = RiskKutuphanesi::maddeyeCevir($t);
            [$o, $s] = $fk ? [$m['olasilik'], $m['siddet']] : RiskYontemDonusturucu::fineKinneyDegeriniMatrise($m['olasilik'], $m['siddet']);

            $rd->maddeler()->create([
                'sira' => ++$sira,
                'tehlike_id' => $t->id,
                'bolum' => $m['bolum'],
                'faaliyet' => $m['faaliyet'],
                'tehlike' => $m['tehlike'],
                'risk' => $m['risk'],
                'mevcut_onlem' => $m['mevcut_onlem'],
                'etkilenen_calisan' => true,
                'etkilenen_diger' => false,
                'olasilik' => $o,
                'frekans' => $fk ? $m['frekans'] : null,
                'siddet' => $s,
                'durum' => 'acik',
            ]);
            $eklenen++;
        }

        $haric = collect($rd->haric_tehlikeler ?? [])->map(fn ($id) => (int) $id)
            ->merge(array_map('intval', $gerekmeyenIds))
            ->diff(array_map('intval', $eklenecekIds))
            ->unique()->values()->all();

        $rd->haric_tehlikeler = $haric ?: null;
        if ($eklenen > 0) {
            $rd->revizyon_no = static::sonrakiRevizyon($rd->revizyon_no);
            $rd->revizyon_nedeni = 'Yeni ekipman: '.implode(', ', $ekipmanlar).' ('.now()->format('d.m.Y').')';
        }
        $rd->save();

        return $eklenen;
    }

    /**
     * Firmanın ekipmanlarına ait kontrol formlarının isg.haftalik_ekipman_kontrol.tipler
     * içindeki indeksleri (HaftalikEkipmanKontrolUretici::pdf girdisi).
     *
     * @return array<int, int>
     */
    public static function kontrolFormuIndeksleri(Firma $firma): array
    {
        $indeks = collect(config('isg.haftalik_ekipman_kontrol.tipler', []))
            ->mapWithKeys(fn (array $f, int $i) => [$f['ad'] => $i]);

        return static::ekipmanlar($firma)->pluck('tip')->filter()->unique()
            ->map(fn ($tip) => static::tanim($tip)['kontrol_formu'] ?? null)
            ->filter(fn ($ad) => $ad !== null && $indeks->has($ad))
            ->map(fn ($ad) => $indeks[$ad])
            ->unique()->sort()->values()->all();
    }

    /**
     * Periyodik muayeneye tabi olup hiç muayene tarihi girilmemiş aktif ekipmanlar.
     *
     * @return Collection<int, IsEkipmani>
     */
    public static function muayeneTarihiEksik(Firma $firma): Collection
    {
        return static::ekipmanlar($firma)
            ->filter(fn (IsEkipmani $e) => static::periyodikMi($e->kategori) && ! $e->son_muayene_tarihi && ! $e->sonraki_vize_tarihi)
            ->values();
    }

    /** @param  Collection<int, array{id: ?int, n: string}>  $maddeler */
    private static function kapsaniyor(Tehlike $t, Collection $maddeler): bool
    {
        $n = static::norm($t->tehlike);

        foreach ($maddeler as $m) {
            if ($m['id'] === $t->id && static::benzerlik($m['n'], $n) >= self::ID_BENZERLIK_ESIGI) {
                return true;
            }
            if (static::benzerlik($m['n'], $n) >= self::BENZERLIK_ESIGI) {
                return true;
            }
        }

        return false;
    }

    private static function benzerlik(string $a, string $b): float
    {
        if ($a === $b) {
            return 100;
        }
        $la = mb_strlen($a);
        $lb = mb_strlen($b);
        if ($la === 0 || $lb === 0 || min($la, $lb) / max($la, $lb) < 0.5) {
            return 0;
        }
        similar_text($a, $b, $yuzde);

        return $yuzde;
    }

    /** Türkçe küçük harf, noktalama/boşluk sadeleştirme. */
    public static function norm(?string $metin): string
    {
        $metin = mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $metin));
        $metin = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $metin);

        return trim(preg_replace('/\s+/u', ' ', $metin));
    }

    private static function sonrakiRevizyon(?string $no): string
    {
        $no = trim((string) $no);

        if ($no === '') {
            return '01';
        }

        // "Rev.02" gibi serbest biçime dokunma — yalnız salt sayıyı artır.
        return ctype_digit($no) ? str_pad((string) ((int) $no + 1), max(2, strlen($no)), '0', STR_PAD_LEFT) : $no;
    }
}
