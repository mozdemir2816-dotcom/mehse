<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Risk Sihirbazı "Risk Değerlendirmenizden Yükleyin" yöntemi — kullanıcının
 * KENDİ Excel dosyasını, hiçbir sabit şablona uydurmadan okur. Gerçek risk
 * değerlendirme dosyaları genelde: (a) başlık satırı ilk satırda değildir
 * (üstte logo/lejant/ölçek tabloları olur), (b) sütun adları kişiden kişiye
 * değişir ("Tehlikeli Durum" / "Tehlike Tanımı" / "Tehlike Kaynağı" gibi).
 *
 * Bu yüzden: önce en çok tanıdık sütun adı içeren satır "başlık satırı" olarak
 * bulunur, sonra her sütun eş anlamlı kelime kümeleriyle eşleştirilir. Puan
 * sütunları (Olasılık/Frekans/Şiddet) yalnızca hem adı uyuyorsa HEM de altındaki
 * veri sayısalsa kabul edilir — metin sütununun yanlışlıkla puan sanılmasını önler.
 */
class RiskDegerlendirmesiExcelOkuyucu
{
    private const ALAN_ANAHTAR_KELIMELERI = [
        'bolum' => ['bolum', 'departman', 'saha', 'birim'],
        'faaliyet' => ['altfaaliyet', 'altfaaliyetler', 'faaliyet', 'proses'],
        'tehlike' => ['tehlikelidurum', 'tehlikelidurumyadadavranis', 'tehliketanimi', 'tehlikekaynagi', 'tehlike'],
        'risk' => ['risk', 'sonuc', 'etki'],
        'mevcut_onlem' => ['mevcutonlem', 'mevcuttedbir', 'mevcutdurum', 'kontrol'],
        'oneri' => ['alinacaktedbir', 'alinanonlem', 'onlem', 'oneri', 'aksiyon'],
        'sorumlu' => ['sorumluvetermin', 'sorumlu'],
        'termin' => ['termin', 'tarih'],
        'mevzuat' => ['mevzuat', 'yasaldayanak', 'yonetmelik', 'kanun'],
    ];

    /** Yalnızca altındaki veri sayısalsa kabul edilir. */
    private const PUAN_ANAHTAR_KELIMELERI = [
        'olasilik' => ['olasilik'],
        'frekans' => ['frekans', 'maruziyet'],
        'siddet' => ['siddet'],
    ];

    private const BASLIK_TARAMA_SATIR_LIMIT = 60;

    private const BOS_SATIR_TOLERANSI = 25;

    /**
     * @return array{basarili: int, adaylar: array<int, array<string, mixed>>, hatalar: array<int, string>}
     */
    public static function oku(string $dosyaYolu): array
    {
        // Gerçek risk analizi dosyaları genelde büyük/çok biçimlendirilmiş olur
        // (lejant tabloları, renkli hücreler, çoklu sayfa) — stil nesnelerini
        // yüklemeden yalnız veriyi okumak bellek kullanımını büyük ölçüde azaltır.
        // NOT: bu modda dosyanın "aktif sayfa" bilgisi güvenilir gelmeyebiliyor
        // (PhpSpreadsheet bir kısıtı) — bu yüzden aktif sayfaya güvenmek yerine
        // TÜM sayfalar taranıp en iyi eşleşen başlık satırı bulunur.
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        $kitap = $reader->load($dosyaYolu);

        $sheet = null;
        $baslikSatiri = null;
        $enIyiPuan = -1;

        foreach ($kitap->getAllSheets() as $adaySheet) {
            $adayMaxRow = $adaySheet->getHighestRow();
            $adayMaxCol = Coordinate::columnIndexFromString($adaySheet->getHighestColumn());

            [$adaySatir, $adayPuan] = static::baslikSatiriniBul($adaySheet, $adayMaxRow, $adayMaxCol);

            if ($adaySatir !== null && $adayPuan > $enIyiPuan) {
                $enIyiPuan = $adayPuan;
                $baslikSatiri = $adaySatir;
                $sheet = $adaySheet;
            }
        }

        if ($sheet === null || $baslikSatiri === null) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Tanıdık bir başlık satırı bulunamadı (en az "Tehlike" sütunu gerekli, dosyanın hiçbir sayfasında).']];
        }

        $maxRow = $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $sutunlar = static::sutunlariEslestir($sheet, $baslikSatiri, $maxCol);

        if (! in_array('tehlike', $sutunlar, true)) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ["Satır {$baslikSatiri} başlık olarak bulundu ama \"Tehlike\" sütunu eşleşmedi."]];
        }

        $adaylar = [];
        $hatalar = [];
        $bosSayaci = 0;

        for ($r = $baslikSatiri + 1; $r <= $maxRow; $r++) {
            $satir = static::satiriOku($sheet, $r, $maxCol);

            if (static::satirBosMu($satir)) {
                $bosSayaci++;

                if ($bosSayaci >= self::BOS_SATIR_TOLERANSI) {
                    break; // tablo bitti kabul edilir
                }

                continue;
            }

            $bosSayaci = 0;
            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['tehlike'] ?? null)) {
                continue; // tehlike alanı boşsa muhtemelen alt başlık / boş satır
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

        if (! $adaylar) {
            $hatalar[] = "Satır {$baslikSatiri} başlık olarak bulundu ama altında okunabilir madde yok.";
        }

        return ['basarili' => count($adaylar), 'adaylar' => $adaylar, 'hatalar' => $hatalar];
    }

    /** @return array{0: int|null, 1: int} [en iyi satır numarası (yoksa null), puanı] */
    private static function baslikSatiriniBul($sheet, int $maxRow, int $maxCol): array
    {
        $enIyiSatir = null;
        $enIyiPuan = 0;
        $tarananSatir = min($maxRow, self::BASLIK_TARAMA_SATIR_LIMIT);

        for ($r = 1; $r <= $tarananSatir; $r++) {
            $puan = 0;
            $tehlikeEslesti = false;

            for ($c = 1; $c <= $maxCol; $c++) {
                $deger = $sheet->getCell([$c, $r])->getValue();

                if (! is_string($deger) || trim($deger) === '') {
                    continue;
                }

                $normalize = static::normalize($deger);

                foreach (self::ALAN_ANAHTAR_KELIMELERI as $alan => $kelimeler) {
                    foreach ($kelimeler as $kelime) {
                        if (str_contains($normalize, $kelime)) {
                            $puan++;

                            if ($alan === 'tehlike') {
                                $tehlikeEslesti = true;
                            }

                            break;
                        }
                    }
                }
            }

            if ($tehlikeEslesti && $puan > $enIyiPuan) {
                $enIyiPuan = $puan;
                $enIyiSatir = $r;
            }
        }

        return [$enIyiSatir, $enIyiPuan];
    }

    /** @return array<int, string> sütun indeksi => alan adı */
    private static function sutunlariEslestir($sheet, int $baslikSatiri, int $maxCol): array
    {
        $sutunlar = [];
        $doluAlanlar = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $baslik = $sheet->getCell([$c, $baslikSatiri])->getValue();

            if (! is_string($baslik) || trim($baslik) === '') {
                continue;
            }

            $normalize = static::normalize($baslik);

            foreach (self::PUAN_ANAHTAR_KELIMELERI as $alan => $kelimeler) {
                if (isset($doluAlanlar[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($normalize, $kelime) && static::sutunSayisalMi($sheet, $c, $baslikSatiri)) {
                        $sutunlar[$c] = $alan;
                        $doluAlanlar[$alan] = true;

                        continue 3;
                    }
                }
            }

            if (isset($sutunlar[$c])) {
                continue;
            }

            foreach (self::ALAN_ANAHTAR_KELIMELERI as $alan => $kelimeler) {
                foreach ($kelimeler as $kelime) {
                    if (str_contains($normalize, $kelime)) {
                        $sutunlar[$c] = $alan;

                        continue 3;
                    }
                }
            }
        }

        return $sutunlar;
    }

    /** Başlığın altındaki ilk birkaç doldurulmuş hücre sayısal mı? */
    private static function sutunSayisalMi($sheet, int $col, int $baslikSatiri): bool
    {
        $kontrolEdilen = 0;

        for ($r = $baslikSatiri + 1; $r <= $baslikSatiri + 15 && $kontrolEdilen < 5; $r++) {
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

    /** @return array<int, mixed> */
    private static function satiriOku($sheet, int $row, int $maxCol): array
    {
        $satir = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $satir[$c] = $sheet->getCell([$c, $row])->getCalculatedValue();
        }

        return $satir;
    }

    /** @return array<string, mixed> */
    private static function satiriEslestir(array $satir, array $sutunlar): array
    {
        $veri = [];

        foreach ($sutunlar as $c => $alan) {
            $deger = is_string($satir[$c] ?? null) ? trim($satir[$c]) : ($satir[$c] ?? null);

            if ($deger === '' || $deger === null) {
                continue;
            }

            if (in_array($alan, ['olasilik', 'frekans', 'siddet'], true)) {
                $veri[$alan] = is_numeric($deger) ? (float) $deger : null;

                continue;
            }

            $yeni = is_string($deger) ? $deger : (string) $deger;

            $veri[$alan] = isset($veri[$alan]) ? $veri[$alan].' — '.$yeni : $yeni;
        }

        return $veri;
    }

    private static function satirBosMu(array $satir): bool
    {
        return collect($satir)->every(fn ($h) => blank(is_string($h) ? trim($h) : $h));
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
