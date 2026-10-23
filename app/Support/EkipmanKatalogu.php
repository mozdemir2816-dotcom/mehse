<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Talimat;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
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

    /** Ekipmandan bağımsız, her firmanın değerlendirmesinde beklenen set (config her_firma_riskleri). */
    public const HER_FIRMA_ETIKET = 'Ortak eğitim ve acil durum';

    /** Ekipman olmayan (revizyon nedeninde "Yeni ekipman" sayılmayan) grup etiketleri. */
    public const ORTAK_GRUPLAR = [self::ORTAK_ETIKET, self::HER_FIRMA_ETIKET];

    /** @return array{risk_kategorileri: array<int, string>, kontrol_formu: ?string, talimat: ?string, ortak_riskler?: bool}|null */
    public static function tanim(?string $tip): ?array
    {
        $t = config('ekipman_katalogu.tipler.'.$tip);
        $bagli = $tip ? (static::kutuphaneBaglari()[$tip] ?? []) : [];

        if (! is_array($t) && ! $bagli) {
            return null;
        }

        $t = (is_array($t) ? $t : []) + ['risk_kategorileri' => [], 'kontrol_formu' => null, 'talimat' => null];
        $t['risk_kategorileri'] = array_values(array_unique([...$t['risk_kategorileri'], ...$bagli]));

        return $t;
    }

    /**
     * Panelden yüklenen ekipman risk analizlerinin bağı (tehlike_kategorileri.ekipman_tipleri).
     *
     * @return array<string, array<int, string>> tip => kategori anahtarları
     */
    public static function kutuphaneBaglari(): array
    {
        $baglar = [];

        foreach (TehlikeKategorisi::query()->whereNotNull('ekipman_tipleri')->get(['anahtar', 'ekipman_tipleri']) as $k) {
            foreach ($k->ekipman_tipleri ?? [] as $tip) {
                $baglar[$tip][] = $k->anahtar;
            }
        }

        return $baglar;
    }

    /**
     * Ekipman tipi seçenekleri, periyodik kontrol kategorisine göre gruplu.
     *
     * @return array<string, array<string, string>>
     */
    public static function tipSecenekleri(): array
    {
        $kategoriler = config('isg.periyodik_kontrol.kategoriler', []);

        return collect(config('isg.periyodik_kontrol.tipler', []))
            ->mapWithKeys(fn (array $tipler, string $k) => [
                $kategoriler[$k]['ad'] ?? $k => collect($tipler)->pluck('ad', 'ad')->all(),
            ])->all();
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

    private const IS_KALEMI_GRUBU = 'İnşaat / Şantiye';

    /**
     * Firmanın seçili iş kalemlerine (isg.kkd_matris.is_kalemleri[grup][].tehlike_kategorileri,
     * kategori ADI ile) karşılık gelen kütüphane kategori anahtarları — "İş Kalemi: <ad>" etiketiyle.
     *
     * @return array<string, array<int, string>>
     */
    public static function isKalemiRiskGruplari(Firma $firma): array
    {
        $anahtarlar = $firma->is_kalemleri ?? [];
        if (! $anahtarlar) {
            return [];
        }

        $maddeler = collect(config('isg.kkd_matris.is_kalemleri.'.self::IS_KALEMI_GRUBU, []))
            ->whereIn('anahtar', $anahtarlar);
        if ($maddeler->isEmpty()) {
            return [];
        }

        $adToAnahtar = TehlikeKategorisi::query()->pluck('anahtar', 'ad');
        $gruplar = [];

        foreach ($maddeler as $m) {
            $kategoriAnahtarlari = collect($m['tehlike_kategorileri'] ?? [])
                ->map(fn (string $ad) => $adToAnahtar[$ad] ?? null)
                ->filter()->values()->all();

            if ($kategoriAnahtarlari) {
                $gruplar['İş Kalemi: '.$m['ad']] = $kategoriAnahtarlari;
            }
        }

        return $gruplar;
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

        // Ekipmandan bağımsız, her firmada beklenen evrensel set (eğitim/sağlık + bina) +
        // NACE grubuna göre acil durum seti — kullanıcı kararı 14.10.2026 / 23.10.2026.
        $herFirma = config('ekipman_katalogu.her_firma_riskleri', []);
        $naceGrubu = NaceGrubu::bul($firma->nace_kodu);
        $acilDurum = config('ekipman_katalogu.her_firma_acil_durum.'.$naceGrubu)
            ?? config('ekipman_katalogu.her_firma_acil_durum.varsayilan', []);
        $herFirma = [...$herFirma, ...$acilDurum];
        if ($herFirma) {
            $gruplar[self::HER_FIRMA_ETIKET] = $herFirma;
        }

        // Firmanın seçili iş kalemlerine (bkz. isg.kkd_matris.is_kalemleri, Firma::$is_kalemleri)
        // göre beklenen risk kategorileri — aynı NACE kodundaki farklı kapsamlı firmaları ayırt
        // eder (ör. kalıp/kazı/beton/demir yapan şantiye ile +sıva/çatı/kaplama/mobilya da yapan
        // şantiye aynı öneriyi almaz). Kullanıcı kararı 23.10.2026.
        foreach (static::isKalemiRiskGruplari($firma) as $etiket => $anahtarlar) {
            $gruplar[$etiket] = $anahtarlar;
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
                ->each(function (Tehlike $t) use (&$gorulen) {
                    $gorulen[$t->id] = true;
                })
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
            $m = RiskKutuphanesi::maddeyeCevir($t, $rd->yontem);
            [$o, $s, $so, $ss] = [$m['olasilik'], $m['siddet'], $m['son_olasilik'], $m['son_siddet']];

            $rd->maddeler()->create([
                'sira' => ++$sira,
                'tehlike_id' => $t->id,
                'bolum' => $m['bolum'],
                'faaliyet' => $m['faaliyet'],
                'tehlike' => $m['tehlike'],
                'risk' => $m['risk'],
                'mevcut_onlem' => $m['mevcut_onlem'],
                'oneri' => $m['oneri'],
                'sorumlu' => $m['sorumlu'],
                'termin' => $m['termin'],
                'etkilenen_calisan' => true,
                'etkilenen_diger' => false,
                'olasilik' => $o,
                'frekans' => $fk ? $m['frekans'] : null,
                'siddet' => $s,
                // Hedef puan kütüphanede yoksa model varsayımı devreye girer.
                'son_olasilik' => $so,
                'son_frekans' => $fk ? $m['son_frekans'] : null,
                'son_siddet' => $ss,
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
            $rd->revizyon_nedeni = ($ekipmanlar ? 'Yeni ekipman: '.implode(', ', $ekipmanlar) : 'Ortak eğitim / acil durum maddeleri eklendi').' ('.now()->format('d.m.Y').')';
        }
        $rd->save();

        return $eklenen;
    }

    /** Kriter verilmemiş formların sütunları (kod => açıklama). */
    public const VARSAYILAN_KRITERLER = ['Uygun' => 'Uygun', 'Uygun Değil' => 'Uygun Değil', 'Uygulanamaz' => 'Uygulanamaz'];

    /**
     * Kontrol formu tanımları (isg.haftalik_ekipman_kontrol.tipler), indeksleri korunarak
     * normalleştirilmiş: gruplu formlar (kullanıcının kendi formu, ör. İSG-FR-CNC-01) için de
     * düz 'sorular' + numaralı 'satirlar' (grup başlığı / 1.1 madde) ve 'kriterler' gelir.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function kontrolFormlari(): array
    {
        return array_map([static::class, 'kontrolFormuNormal'], config('isg.haftalik_ekipman_kontrol.tipler', []));
    }

    /** @return array<string, mixed> */
    public static function kontrolFormuNormal(array $form): array
    {
        $satirlar = [];
        $sorular = [];

        if (! empty($form['satirlar'])) {
            // Hazır satırlı form (✓/X/- düzeni): numara ve sıra belgedeki gibi; 'kabul' = kabul kriteri.
            $satirlar = array_values($form['satirlar']);
            foreach ($satirlar as $s) {
                if (isset($s['soru'])) {
                    $sorular[] = $s['soru'].(filled($s['kabul'] ?? null) ? ' — '.$s['kabul'] : '');
                }
            }
        } elseif (! empty($form['gruplar'])) {
            foreach (array_values($form['gruplar']) as $g => $grup) {
                $grupNo = preg_match('/^(\d+)\./u', $grup['baslik'], $m) ? $m[1] : (string) ($g + 1);
                $satirlar[] = ['grup' => $grup['baslik']];
                foreach (array_values($grup['sorular']) as $k => $soru) {
                    $satirlar[] = ['no' => $grupNo.'.'.($k + 1), 'soru' => $soru];
                    $sorular[] = $soru;
                }
            }
        } else {
            $sorular = array_values($form['sorular'] ?? []);
            foreach ($sorular as $k => $soru) {
                $satirlar[] = ['no' => (string) ($k + 1), 'soru' => $soru];
            }
        }

        return [
            ...$form,
            'sorular' => $sorular,
            'satirlar' => $satirlar,
            'kriterler' => $form['kriterler'] ?? self::VARSAYILAN_KRITERLER,
        ];
    }

    /**
     * Ekipmanın katalogdaki talimat şablonu (TalimatKutuphanesi::hazirSablonlar içinden başlıkla).
     *
     * @return array<string, mixed>|null
     */
    public static function talimatSablonu(?string $tip): ?array
    {
        $baslik = static::tanim($tip)['talimat'] ?? null;

        return $baslik
            ? collect(TalimatKutuphanesi::hazirSablonlar())->first(fn (array $s) => static::norm($s['baslik']) === static::norm($baslik))
            : null;
    }

    /**
     * Firmanın ekipmanları için katalogda talimatı olup firmada bulunmayanlar
     * (başlık eşleşmesi, büyük/küçük harf duyarsız).
     *
     * @return array<string, array<string, mixed>> talimat başlığı => şablon
     */
    public static function eksikTalimatlar(Firma $firma): array
    {
        $mevcut = Talimat::query()->where('firma_id', $firma->id)->pluck('baslik')->map(fn ($b) => static::norm($b))->all();

        return static::ekipmanlar($firma)->pluck('tip')->filter()->unique()
            ->map(fn ($tip) => static::talimatSablonu($tip))
            ->filter()
            ->reject(fn (array $s) => in_array(static::norm($s['baslik']), $mevcut, true))
            ->keyBy('baslik')
            ->all();
    }

    /**
     * Eksik ekipman talimatlarını firmaya ekler (şablondan birebir).
     *
     * @return array<int, string> eklenen talimat başlıkları
     */
    public static function talimatlariEkle(Firma $firma): array
    {
        $eklenen = [];
        foreach (static::eksikTalimatlar($firma) as $baslik => $sablon) {
            Talimat::create(['firma_id' => $firma->id, ...TalimatKutuphanesi::talimatAlanlari($sablon)]);
            $eklenen[] = $baslik;
        }

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
     * Firmanın ekipmanlarından, katalogda (veya panelden yüklenmiş kütüphane bağından) hiç
     * risk kategorisi tanımlı olmayan tipler — kullanıcı kararı 23.10.2026: böyle bir ekipman
     * eklenince "kütüphanede yok, Excel yükleyin" uyarısı verilsin, sessizce atlanmasın.
     *
     * @return array<int, string> ekipman tipi adları
     */
    public static function kutuphanesizTipler(Firma $firma): array
    {
        return static::ekipmanlar($firma)->pluck('tip')->filter()->unique()
            ->reject(fn (string $tip) => filled(static::tanim($tip)['risk_kategorileri'] ?? []))
            ->values()->all();
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
