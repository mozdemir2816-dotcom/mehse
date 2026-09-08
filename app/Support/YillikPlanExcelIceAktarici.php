<?php

namespace App\Support;

use App\Models\YillikPlan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Yıllık Çalışma / Eğitim Planı'nın SERBEST BİÇİMLİ Excel ile yüklenmesi.
 * Kullanıcının elindeki kendi planı (ör. "ABDULLETİF YALÇINKAYA YILLIK ÇALIŞMA
 * PLANI.xlsx") okunur: başlık satırı taranarak bulunur, sütunlar ada göre
 * eşlenir, ay hücrelerindeki işaretler (X / herhangi bir dolu hücre) o ayı
 * "Planlandı" yapar. Eğitim planındaki 12 ay × 4 hafta düzeni ay bazına
 * indirilir. Satırlar mevcut plandaki satırlarla İSİMLE eşlenir: varsa
 * güncellenir, yoksa eklenir; plandaki fazladan satırlara dokunulmaz. Atanmış
 * uzman (sözleşme başlangıcı) öncesindeki aylar hiçbir zaman işaretlenmez.
 */
class YillikPlanExcelIceAktarici
{
    private const AYLAR = ['ocak', 'subat', 'mart', 'nisan', 'mayis', 'haziran', 'temmuz', 'agustos', 'eylul', 'ekim', 'kasim', 'aralik'];

    private const KATEGORI_ANAHTARLARI = [
        'genelkonular' => 'genel', 'genel' => 'genel',
        'saglikkonulari' => 'saglik', 'saglik' => 'saglik',
        'teknikkonular' => 'teknik', 'teknik' => 'teknik',
        'digeregitimler' => 'diger', 'diger' => 'diger', 'digerkonular' => 'diger',
    ];

    /**
     * @param  'faaliyetler'|'egitimler'  $tip
     * @return array{eklenen: int, guncellenen: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, YillikPlan $plan, string $tip): array
    {
        ExcelBellek::artir();

        try {
            $reader = IOFactory::createReaderForFile($dosyaYolu);
            $reader->setReadDataOnly(true);
            $sayfalar = $reader->load($dosyaYolu);
        } catch (Throwable $e) {
            return ['eklenen' => 0, 'guncellenen' => 0, 'hatalar' => ['Dosya okunamadı: '.$e->getMessage()]];
        }

        // Tüm sayfaları tara — veri hangisindeyse (bkz. phpspreadsheet-memory-vs-active-sheet).
        $satirlar = [];
        foreach ($sayfalar->getAllSheets() as $sayfa) {
            $s = $sayfa->toArray(null, true, false, false);
            if (count($s) > count($satirlar)) {
                $satirlar = $s;
            }
        }

        $adAnahtari = $tip === 'egitimler' ? 'konu' : 'faaliyet';
        $harita = static::baslikBul($satirlar, $tip);

        if ($harita === null) {
            return ['eklenen' => 0, 'guncellenen' => 0, 'hatalar' => [
                'Başlık satırı bulunamadı. Dosyada "'.($tip === 'egitimler' ? 'EĞİTİMİN ADI' : 'ÖLÇÜM / RAPOR').'" ve ay adları (OCAK…ARALIK) içeren bir satır olmalı.',
            ]];
        }

        $kilitAy = $plan->firma->planKilitAyIndeksi($plan->yil);
        $mevcut = $plan->{$tip} ?? [];
        $indeksMap = [];
        foreach ($mevcut as $i => $m) {
            $indeksMap[static::normalize((string) ($m[$adAnahtari] ?? ''))] = $i;
        }

        $eklenen = 0;
        $guncellenen = 0;
        $hatalar = [];
        $sonKategori = null;

        foreach ($satirlar as $r => $satir) {
            if ($r <= $harita['baslikSatiri']) {
                continue;
            }

            // Kategori sütunu taşındıysa (merged hücre) son değeri taşı.
            if ($harita['kategori'] !== null) {
                $ham = static::normalize((string) ($satir[$harita['kategori']] ?? ''));
                if (isset(self::KATEGORI_ANAHTARLARI[$ham])) {
                    $sonKategori = self::KATEGORI_ANAHTARLARI[$ham];
                }
            }

            $ad = trim((string) ($satir[$harita['ad']] ?? ''));

            if ($ad === '' || static::baslikTekrariMi($ad) || mb_strlen($ad) < 3) {
                continue;
            }

            $aylar = static::aylariOku($satir, $harita['aylar'], $kilitAy);

            $veri = [$adAnahtari => $ad, 'aylar' => $aylar];

            if ($harita['sorumlu'] !== null && filled($satir[$harita['sorumlu']] ?? null)) {
                $veri[$tip === 'egitimler' ? 'egitici' : 'sorumlu'] = trim((string) $satir[$harita['sorumlu']]);
            }
            if ($harita['yardimci'] !== null && filled($satir[$harita['yardimci']] ?? null)) {
                $veri[$tip === 'egitimler' ? 'hedef' : 'yasal_gereklilik'] = trim((string) $satir[$harita['yardimci']]);
            }
            if ($tip === 'egitimler' && $sonKategori !== null) {
                $veri['kategori'] = $sonKategori;
            }

            $anahtar = static::normalize($ad);

            if (isset($indeksMap[$anahtar])) {
                $mevcut[$indeksMap[$anahtar]] = [...$mevcut[$indeksMap[$anahtar]], ...$veri];
                $guncellenen++;
            } else {
                $mevcut[] = $tip === 'egitimler'
                    ? [...['sure_saat' => null, 'egitici' => null, 'hedef' => null, 'hedef_kitle' => null, 'kategori' => $sonKategori], ...$veri]
                    : [...['sorumlu' => null, 'yasal_gereklilik' => null, 'frekans' => null], ...$veri];
                $indeksMap[$anahtar] = array_key_last($mevcut);
                $eklenen++;
            }
        }

        if ($eklenen === 0 && $guncellenen === 0) {
            $hatalar[] = 'Dosyada içe aktarılabilecek satır bulunamadı.';
        }

        $plan->update([$tip => array_values($mevcut)]);

        return ['eklenen' => $eklenen, 'guncellenen' => $guncellenen, 'hatalar' => $hatalar];
    }

    /**
     * @return array{baslikSatiri: int, ad: int, sorumlu: ?int, yardimci: ?int, kategori: ?int, aylar: array<int, list<int>>}|null
     */
    private static function baslikBul(array $satirlar, string $tip): ?array
    {
        foreach ($satirlar as $r => $satir) {
            if ($r > 20) {
                break;
            }

            $normler = array_map(fn ($h) => static::normalize((string) $h), $satir);
            $aylar = static::ayBloklari($normler);

            if (count($aylar) < 6) {
                continue;
            }

            $ad = null;
            $sorumlu = null;
            $yardimci = null;
            $kategori = null;

            foreach ($normler as $c => $n) {
                if ($n === '') {
                    continue;
                }
                if ($ad === null && (str_contains($n, 'olcum') || $n === 'faaliyet' || $n === 'egitiminadi' || $n === 'egitim' || $n === 'egitimkonusu' || $n === 'konu' || str_contains($n, 'olcumrapor'))) {
                    $ad = $c;
                } elseif ($sorumlu === null && ($n === 'sorumlu' || $n === 'egitici')) {
                    $sorumlu = $c;
                } elseif ($yardimci === null && ($n === 'yasalgereklilik' || str_contains($n, 'hedef'))) {
                    $yardimci = $c;
                } elseif ($kategori === null && ($n === 'kategori' || $n === 'grup')) {
                    $kategori = $c;
                }
            }

            if ($ad === null) {
                continue;
            }

            // Eğitim planında kategori genelde etiketsiz ilk sütun (ad'dan önce).
            if ($tip === 'egitimler' && $kategori === null && $ad > 0) {
                $kategori = $ad - 1;
            }

            return ['baslikSatiri' => $r, 'ad' => $ad, 'sorumlu' => $sorumlu, 'yardimci' => $yardimci, 'kategori' => $kategori, 'aylar' => $aylar];
        }

        return null;
    }

    /**
     * Başlık satırındaki normalize edilmiş hücrelerden ay → o aya ait sütun
     * indeksleri eşlemesi. Bir ay adından sonraki (bir sonraki ay adına kadar
     * olan) boş başlıklı sütunlar da o aya sayılır (12 ay × 4 hafta düzeni).
     *
     * @return array<int, list<int>> 0-11 => sütun indeksleri
     */
    private static function ayBloklari(array $normler): array
    {
        $isaretli = [];
        foreach ($normler as $c => $n) {
            $ay = array_search($n, self::AYLAR, true);
            if ($ay !== false) {
                $isaretli[$c] = $ay;
            }
        }

        if (! $isaretli) {
            return [];
        }

        $bloklar = [];
        $siraliIsaret = array_keys($isaretli);

        // Ay başlıkları bitişikse (aralarında 1 sütun) → aylık düzen: her ay tek
        // sütun. Aralarında boşluk varsa → haftalık düzen (12 ay × 4 hafta).
        $enKucukAralik = PHP_INT_MAX;
        for ($i = 1; $i < count($siraliIsaret); $i++) {
            $enKucukAralik = min($enKucukAralik, $siraliIsaret[$i] - $siraliIsaret[$i - 1]);
        }
        $haftalik = $enKucukAralik > 1;

        foreach ($isaretli as $baslangic => $ay) {
            if (! $haftalik) {
                $bloklar[$ay] = [$baslangic];

                continue;
            }

            $sonrakiIndeks = array_search($baslangic, $siraliIsaret, true) + 1;
            $bitis = $siraliIsaret[$sonrakiIndeks] ?? ($baslangic + 4);
            $bitis = min($bitis, $baslangic + 4);
            $bloklar[$ay] = range($baslangic, max($baslangic, $bitis - 1));
        }

        return $bloklar;
    }

    /**
     * @param  array<int, list<int>>  $ayBloklari
     * @return list<string> 12 elemanlı bos|planlandi|tamamlandi
     */
    private static function aylariOku(array $satir, array $ayBloklari, int $kilitAy): array
    {
        $aylar = array_fill(0, 12, 'bos');

        for ($ay = 0; $ay < 12; $ay++) {
            if ($ay < $kilitAy) {
                continue;
            }

            foreach ($ayBloklari[$ay] ?? [] as $c) {
                $deger = trim((string) ($satir[$c] ?? ''));
                if ($deger === '') {
                    continue;
                }

                $n = mb_strtolower($deger);
                $aylar[$ay] = (str_contains($n, 'tamam') || $n === 't' || str_contains($n, 'yapıld')) ? 'tamamlandi' : 'planlandi';
                break;
            }
        }

        return $aylar;
    }

    private static function baslikTekrariMi(string $ad): bool
    {
        $n = static::normalize($ad);

        return in_array($n, ['olcumrapor', 'olcum', 'faaliyet', 'egitiminadi', 'yasalgereklilik', 'sorumlu', 'egitici'], true)
            || str_contains($n, 'firmaadi')
            || str_contains($n, 'yillikcalismaplani')
            || str_contains($n, 'yilliegitimplani');
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($metin)) ?? '';
    }
}
