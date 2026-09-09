<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Risk Sihirbazı "Risk Değerlendirmenizden Yükleyin" yöntemi — kullanıcının
 * KENDİ Excel dosyasını, hiçbir sabit şablona uydurmadan okur. Gerçek risk
 * değerlendirme dosyaları genelde: (a) başlık satırı ilk satırda değildir
 * (üstte logo/lejant/ölçek tabloları olur), (b) başlık 1 VEYA 2 satırlık
 * olabilir (ana başlık + "Olasılık (O1) / Frekans (F1) / Şiddet (S1)" alt
 * başlığı), (c) sütun adları kişiden kişiye değişir, (d) her iş kalemi ayrı
 * bir SAYFADA olabilir.
 *
 * Bu yüzden: her sayfada başlık bandı (1-2 satır) aranır, sütunlar ana + alt
 * satırın birleşik metniyle eşlenir, tüm sayfalardan okunan maddeler
 * birleştirilir. Puan sütunları (O/F/Ş) yalnızca adı uyuyorsa HEM de altındaki
 * veri sayısalsa kabul edilir; "Risk Skoru / Risk Seviyesi" gibi HESAPLANAN
 * çıktı sütunları hiç eşlenmez.
 */
class RiskDegerlendirmesiExcelOkuyucu
{
    private const ALAN_ANAHTAR_KELIMELERI = [
        'bolum' => ['anakategori', 'faaliyetalani', 'bolum', 'departman', 'unite'],
        'faaliyet' => ['altfaaliyet', 'altfaaliyetler', 'surec', 'imalat', 'faaliyet', 'proses'],
        'tehlike' => ['tehlikelidurum', 'tehlikelidurumyadadavranis', 'tehliketanimi', 'tehlikekaynagi', 'hazard', 'tehlike'],
        'risk' => ['olasirisk', 'riskevent', 'riskvesonuc', 'sonucharm', 'olasisonuc'],
        'mevcut_onlem' => ['mevcutonlem', 'mevcuttedbir', 'mevcutdurum', 'mevcutkontrol'],
        'oneri' => ['alinmasigereken', 'onleyiciveduzeltici', 'alinacaktedbir', 'alinanonlem', 'duzelticitedbir', 'onlem', 'oneri', 'aksiyon'],
        'sorumlu' => ['sorumluvetermin', 'sorumlubirim', 'sorumlu'],
        'termin' => ['termin'],
        'mevzuat' => ['yasalmevzuat', 'yasaldayanak', 'mevzuat', 'yonetmelik', 'kanun', 'standartdayanak'],
    ];

    /** Yalnızca altındaki veri sayısalsa kabul edilir. */
    private const PUAN_ANAHTAR_KELIMELERI = [
        'olasilik' => ['olasilik', 'ihtimal'],
        'frekans' => ['frekans', 'maruziyet'],
        'siddet' => ['siddet'],
    ];

    /** Bu kelimeleri içeren başlıklar HESAPLANAN çıktı sütunudur — hiç eşlenmez. */
    private const YOKSAY_KELIMELERI = ['riskskoru', 'skorr', 'riskseviyesi', 'seviye', 'riskduzeyi', 'duzey', 'renk'];

    private const BASLIK_TARAMA_SATIR_LIMIT = 20;

    private const BOS_SATIR_TOLERANSI = 25;

    /**
     * @return array{basarili: int, adaylar: array<int, array<string, mixed>>, hatalar: array<int, string>}
     */
    public static function oku(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);

        // 1. Geçiş — her sayfada başlık bandını bul.
        $sayfalar = [];

        foreach ($kitap->getAllSheets() as $sheet) {
            $maxRow = $sheet->getHighestRow();
            $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            [$veriBaslangic, $sutunlar] = static::baslikBandiBul($sheet, $maxRow, $maxCol);

            if ($veriBaslangic !== null && in_array('tehlike', $sutunlar, true)) {
                $sayfalar[] = compact('sheet', 'veriBaslangic', 'sutunlar', 'maxRow', 'maxCol');
            }
        }

        if (! $sayfalar) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Tanıdık bir başlık satırı bulunamadı (en az "Tehlike" sütunu gerekli, dosyanın hiçbir sayfasında).']];
        }

        // Herhangi bir sayfada O/F/Ş puan sütunu varsa, yalnız o sayfaları oku —
        // böylece "içindekiler / özet / arama" gibi yardımcı sayfalar elenir. Hiçbir
        // sayfada puan yoksa (kullanıcı puanları AI'ye bıraktıysa) hepsi okunur.
        $puanliVar = collect($sayfalar)->contains(
            fn ($s) => (bool) array_intersect(['olasilik', 'frekans', 'siddet'], $s['sutunlar']),
        );

        if ($puanliVar) {
            $sayfalar = array_values(array_filter(
                $sayfalar,
                fn ($s) => (bool) array_intersect(['olasilik', 'frekans', 'siddet'], $s['sutunlar']),
            ));
        }

        $adaylar = [];

        foreach ($sayfalar as $s) {
            foreach (static::sayfaAdaylari($s['sheet'], $s['veriBaslangic'], $s['sutunlar'], $s['maxRow'], $s['maxCol']) as $a) {
                $adaylar[] = $a;
            }
        }

        if (! $adaylar) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Başlık satırı bulundu ama altında okunabilir risk maddesi yok.']];
        }

        return ['basarili' => count($adaylar), 'adaylar' => $adaylar, 'hatalar' => []];
    }

    /** @return array<int, array<string, mixed>> */
    private static function sayfaAdaylari($sheet, int $veriBaslangic, array $sutunlar, int $maxRow, int $maxCol): array
    {
        $adaylar = [];
        $bosSayaci = 0;

        for ($r = $veriBaslangic; $r <= $maxRow; $r++) {
            // Yalnız eşlenen sütunlar okunur — tüm satırı taramaktan çok daha hızlı.
            $veri = [];
            $doluMu = false;

            foreach ($sutunlar as $c => $alan) {
                $deger = static::hucreDegeri($sheet, $c, $r);

                if ($deger === '' || $deger === null || (is_string($deger) && str_starts_with($deger, '#'))) {
                    continue;
                }

                $doluMu = true;

                if (in_array($alan, ['olasilik', 'frekans', 'siddet'], true)) {
                    $veri[$alan] = is_numeric($deger) ? (float) $deger : ($veri[$alan] ?? null);

                    continue;
                }

                $yeni = is_string($deger) ? trim($deger) : (string) $deger;
                $veri[$alan] = isset($veri[$alan]) ? $veri[$alan].' — '.$yeni : $yeni;
            }

            if (! $doluMu) {
                if (++$bosSayaci >= self::BOS_SATIR_TOLERANSI) {
                    break;
                }

                continue;
            }

            $bosSayaci = 0;

            if (blank($veri['tehlike'] ?? null)) {
                continue;
            }

            $adaylar[] = [
                'anahtar' => 'exc-'.substr(md5(($veri['tehlike'] ?? '').$r.uniqid('', true)), 0, 10),
                'kaynak' => 'excel',
                'tehlike_id' => null,
                'bolum' => $veri['bolum'] ?? null,
                'faaliyet' => $veri['faaliyet'] ?? null,
                'tehlike' => $veri['tehlike'],
                'risk' => $veri['risk'] ?? null,
                'mevcut_onlem' => $veri['mevcut_onlem'] ?? null,
                'oneri' => $veri['oneri'] ?? null,
                'mevzuat' => $veri['mevzuat'] ?? null,
                'sorumlu' => $veri['sorumlu'] ?? null,
                'termin' => $veri['termin'] ?? null,
                'olasilik' => $veri['olasilik'] ?? null,
                'frekans' => $veri['frekans'] ?? null,
                'siddet' => $veri['siddet'] ?? null,
            ];
        }

        return $adaylar;
    }

    /**
     * @return array{0: int|null, 1: array<int, string>} [veri başlangıç satırı, sütun eşlemesi]
     */
    private static function baslikBandiBul($sheet, int $maxRow, int $maxCol): array
    {
        $tara = min($maxRow, self::BASLIK_TARAMA_SATIR_LIMIT);
        $enIyi = [null, []];
        $enIyiPuan = 0;

        for ($r = 1; $r < $tara; $r++) {
            $altSatirMi = static::altBaslikMi($sheet, $r + 1, $maxCol);
            $veriBaslangic = $altSatirMi ? $r + 2 : $r + 1;

            $sutunlar = static::sutunlariEslestir($sheet, $r, $altSatirMi ? $r + 1 : null, $maxCol, $veriBaslangic);

            if (! in_array('tehlike', $sutunlar, true)) {
                continue;
            }

            $puan = count($sutunlar);

            if ($puan > $enIyiPuan) {
                $enIyiPuan = $puan;
                $enIyi = [$veriBaslangic, $sutunlar];
            }

            // Tehlike + Olasılık + Şiddet birlikte varsa bu kesin başlıktır — devam etme.
            if (in_array('olasilik', $sutunlar, true) && in_array('siddet', $sutunlar, true)) {
                break;
            }
        }

        return $enIyi;
    }

    /** Bir satır "alt başlık" (Olasılık O1 / Frekans F1 / Şiddet S1 …) satırı mı? */
    private static function altBaslikMi($sheet, int $row, int $maxCol): bool
    {
        $isaret = 0;
        $uzunMetin = 0;

        for ($c = 1; $c <= $maxCol; $c++) {
            $deger = $sheet->getCell([$c, $row])->getValue();

            if (! is_string($deger) || trim($deger) === '') {
                continue;
            }

            if (mb_strlen($deger) > 55) {
                $uzunMetin++;
            }

            if (preg_match('/(olasilik|olasılık|frekans|siddet|şiddet|puan|skor|seviye|derece|maruziyet|^\s*[ofsr]\s*\d)/iu', $deger)) {
                $isaret++;
            }
        }

        return $isaret >= 2 && $uzunMetin === 0;
    }

    /**
     * @return array<int, string> sütun indeksi => alan adı
     */
    private static function sutunlariEslestir($sheet, int $anaSatir, ?int $altSatir, int $maxCol, int $veriBaslangic): array
    {
        $sutunlar = [];
        $dolu = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $ana = $sheet->getCell([$c, $anaSatir])->getValue();
            $alt = $altSatir ? $sheet->getCell([$c, $altSatir])->getValue() : null;

            $normalize = static::normalize(trim((string) $ana).' '.trim((string) $alt));

            if ($normalize === '') {
                continue;
            }

            // Hesaplanan çıktı sütunları (Risk Skoru / Risk Seviyesi / Düzey) atlanır.
            foreach (self::YOKSAY_KELIMELERI as $yoksay) {
                if (str_contains($normalize, $yoksay)) {
                    continue 2;
                }
            }

            foreach (self::PUAN_ANAHTAR_KELIMELERI as $alan => $kelimeler) {
                if (isset($dolu[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($normalize, $kelime) && static::sutunSayisalMi($sheet, $c, $veriBaslangic)) {
                        $sutunlar[$c] = $alan;
                        $dolu[$alan] = true;

                        continue 3;
                    }
                }
            }

            foreach (self::ALAN_ANAHTAR_KELIMELERI as $alan => $kelimeler) {
                if (isset($dolu[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($normalize, $kelime)) {
                        $sutunlar[$c] = $alan;
                        $dolu[$alan] = true;

                        continue 3;
                    }
                }
            }
        }

        return $sutunlar;
    }

    /** Verinin başladığı satırdan itibaren ilk birkaç dolu hücre sayısal mı? */
    private static function sutunSayisalMi($sheet, int $col, int $veriBaslangic): bool
    {
        $kontrolEdilen = 0;

        for ($r = $veriBaslangic; $r <= $veriBaslangic + 25 && $kontrolEdilen < 5; $r++) {
            $deger = $sheet->getCell([$col, $r])->getValue();

            if ($deger === null || $deger === '') {
                continue;
            }

            $kontrolEdilen++;

            if (! is_numeric($deger)) {
                return false;
            }
        }

        return $kontrolEdilen > 0;
    }

    /** Tek hücrenin değeri — formül değilse hesap motorunu hiç çalıştırmaz (hız). */
    private static function hucreDegeri($sheet, int $col, int $row)
    {
        $hucre = $sheet->getCell([$col, $row]);
        $ham = $hucre->getValue();

        if (! is_string($ham) || ! str_starts_with($ham, '=')) {
            return $ham;
        }

        try {
            return $hucre->getCalculatedValue();
        } catch (\Throwable $e) {
            // PhpSpreadsheet'in çözemediği formül (ör. _xludf.MAXIFS) — atla.
            return null;
        }
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);
        $metin = mb_strtolower($metin);

        return preg_replace('/[^a-z0-9]/', '', $metin) ?? '';
    }
}
