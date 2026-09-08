<?php

namespace App\Support;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Sektörel bir Fine-Kinney risk analizi Excel'ini (ör. kullanıcının "İnşaat İSG
 * Risk Analizi ve Fine-Kinney Programı.xlsx" dosyası) Risk Kütüphanesine aktarır.
 *
 * Her satır bir `Tehlike` olur; "Faaliyet Alanı / Ana Kategori" sütunundaki değer
 * `TehlikeKategorisi` olarak açılır (yoksa oluşturulur). Olasılık / Frekans / Şiddet
 * kütüphaneye yazılır — Risk Sihirbazı manuel seçimde forma önceden dolar, böylece
 * kullanıcı 1.671 maddeyi tek risk değerlendirmesine yığmak yerine iş kalemine göre
 * süzüp yalnız ilgili olanları seçer.
 *
 * Başlık satırı ve sütunlar esnek algılanır (RiskDegerlendirmesiExcelOkuyucu ile
 * aynı yaklaşım): en çok tanıdık başlık içeren satır bulunur, sütunlar eş anlamlı
 * kelimelerle eşlenir; O/F/Ş yalnız altındaki veri sayısalsa kabul edilir.
 */
class FineKinneyKutuphaneIceAktarici
{
    private const KATEGORI_KELIMELERI = ['anakategori', 'faaliyetalani', 'faaliyetalan', 'bolumana', 'kategori'];

    private const METIN_ALANLARI = [
        'faaliyet' => ['altfaaliyet', 'altfaaliyetbolum', 'surec', 'imalat', 'proses', 'faaliyet'],
        'tehlike' => ['tehlikekaynagi', 'tehlikelidurum', 'tehliketanimi', 'hazard', 'tehlike'],
        'risk' => ['olasirisk', 'riskevent', 'olasisonuc', 'riskvesonuc', 'sonucharm'],
        'mevcut_onlem' => ['alinmasigereken', 'onleyiciveduzeltici', 'duzelticitedbir', 'alinacaktedbir', 'onlem', 'tedbir'],
        'mevzuat' => ['yasalmevzuat', 'yasaldayanak', 'mevzuat', 'yonetmelik', 'standartdayanak'],
    ];

    private const PUAN_ALANLARI = [
        'olasilik' => ['olasilik', 'ihtimal'],
        'frekans' => ['frekans', 'maruziyet'],
        'siddet' => ['siddet'],
    ];

    private const BASLIK_TARAMA_LIMIT = 60;

    private const BOS_SATIR_TOLERANSI = 25;

    /**
     * @return array{basarili:int, yeniKategori:int, hatalar:array<int,string>}
     */
    public static function iceAktar(string $dosyaYolu): array
    {
        $satirlar = static::excelSatirlari($dosyaYolu);

        if (! $satirlar) {
            return ['basarili' => 0, 'yeniKategori' => 0, 'hatalar' => ['Dosyada Fine-Kinney risk tablosu bulunamadı ("Tehlike" ve "Olasılık" sütunları gerekli).']];
        }

        return static::kaydet($satirlar);
    }

    /**
     * Hazır satır dizisini kütüphaneye yazar — seeder de bunu kullanır.
     *
     * @param  array<int, array<string, mixed>>  $satirlar  her biri: kategori, faaliyet, tehlike, risk, mevcut_onlem, mevzuat, olasilik, frekans, siddet
     * @return array{basarili:int, yeniKategori:int, hatalar:array<int,string>}
     */
    public static function kaydet(array $satirlar): array
    {
        $basarili = 0;
        $yeniKategori = 0;
        $kategoriOnbellek = [];

        foreach ($satirlar as $s) {
            $tehlikeMetni = trim((string) ($s['tehlike'] ?? ''));

            if ($tehlikeMetni === '') {
                continue;
            }

            $kategoriAdi = trim((string) ($s['kategori'] ?? '')) ?: 'Genel / Sınıflandırılmamış';
            $anahtar = Str::slug($kategoriAdi, '_') ?: 'genel';

            if (! isset($kategoriOnbellek[$anahtar])) {
                $kategori = TehlikeKategorisi::firstOrNew(['anahtar' => $anahtar]);

                if (! $kategori->exists) {
                    $kategori->fill(['ad' => $kategoriAdi, 'sira' => (int) TehlikeKategorisi::max('sira') + 1])->save();
                    $yeniKategori++;
                }

                $kategoriOnbellek[$anahtar] = $kategori;
            }

            Tehlike::updateOrCreate(
                [
                    'tehlike_kategorisi_id' => $kategoriOnbellek[$anahtar]->id,
                    'faaliyet' => static::kisalt($s['faaliyet'] ?? null),
                    'tehlike' => $tehlikeMetni,
                ],
                [
                    'risk' => static::bosla($s['risk'] ?? null),
                    'mevcut_onlem' => static::bosla($s['mevcut_onlem'] ?? null),
                    'mevzuat' => static::kisalt($s['mevzuat'] ?? null),
                    'olasilik' => static::sayi($s['olasilik'] ?? null),
                    'frekans' => static::sayi($s['frekans'] ?? null),
                    'siddet' => static::sayi($s['siddet'] ?? null),
                ],
            );

            $basarili++;
        }

        return ['basarili' => $basarili, 'yeniKategori' => $yeniKategori, 'hatalar' => []];
    }

    /** @return array<int, array<string, mixed>> */
    private static function excelSatirlari(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);

        $sheet = null;
        $baslikSatiri = null;
        $enIyi = -1;

        foreach ($kitap->getAllSheets() as $aday) {
            $maxRow = $aday->getHighestRow();
            $maxCol = Coordinate::columnIndexFromString($aday->getHighestColumn());
            [$satir, $puan] = static::baslikBul($aday, $maxRow, $maxCol);

            if ($satir !== null && $puan > $enIyi) {
                $enIyi = $puan;
                $baslikSatiri = $satir;
                $sheet = $aday;
            }
        }

        if ($sheet === null || $baslikSatiri === null) {
            return [];
        }

        $maxRow = $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $sutunlar = static::sutunlariEsle($sheet, $baslikSatiri, $maxCol);

        if (! in_array('tehlike', $sutunlar, true)) {
            return [];
        }

        $satirlar = [];
        $bos = 0;

        for ($r = $baslikSatiri + 1; $r <= $maxRow; $r++) {
            $veri = [];
            $doluMu = false;

            foreach ($sutunlar as $c => $alan) {
                $deger = $sheet->getCell([$c, $r])->getCalculatedValue();
                $deger = is_string($deger) ? trim($deger) : $deger;

                if ($deger === '' || $deger === null) {
                    continue;
                }

                $doluMu = true;
                $veri[$alan] = in_array($alan, ['olasilik', 'frekans', 'siddet'], true)
                    ? (is_numeric($deger) ? (float) $deger : null)
                    : (string) $deger;
            }

            if (! $doluMu) {
                if (++$bos >= self::BOS_SATIR_TOLERANSI) {
                    break;
                }

                continue;
            }

            $bos = 0;

            if (! blank($veri['tehlike'] ?? null)) {
                $satirlar[] = $veri;
            }
        }

        return $satirlar;
    }

    /** @return array{0:int|null, 1:int} */
    private static function baslikBul($sheet, int $maxRow, int $maxCol): array
    {
        $enIyiSatir = null;
        $enIyiPuan = 0;
        $tara = min($maxRow, self::BASLIK_TARAMA_LIMIT);

        for ($r = 1; $r <= $tara; $r++) {
            $puan = 0;
            $tehlike = false;
            $olasilik = false;

            for ($c = 1; $c <= $maxCol; $c++) {
                $deger = $sheet->getCell([$c, $r])->getValue();

                if (! is_string($deger) || trim($deger) === '') {
                    continue;
                }

                $n = static::normalize($deger);

                foreach ([...self::KATEGORI_KELIMELERI, 'tehlike', 'olasilik', 'siddet', 'faaliyet', 'mevzuat', 'onlem', 'tedbir'] as $kelime) {
                    if (str_contains($n, $kelime)) {
                        $puan++;

                        if ($kelime === 'tehlike') {
                            $tehlike = true;
                        }

                        if ($kelime === 'olasilik') {
                            $olasilik = true;
                        }

                        break;
                    }
                }
            }

            if ($tehlike && $olasilik && $puan > $enIyiPuan) {
                $enIyiPuan = $puan;
                $enIyiSatir = $r;
            }
        }

        return [$enIyiSatir, $enIyiPuan];
    }

    /** @return array<int, string> sütun indeksi => alan */
    private static function sutunlariEsle($sheet, int $baslikSatiri, int $maxCol): array
    {
        $sutunlar = [];
        $dolu = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $baslik = $sheet->getCell([$c, $baslikSatiri])->getValue();

            if (! is_string($baslik) || trim($baslik) === '') {
                continue;
            }

            $n = static::normalize($baslik);

            if (! isset($dolu['kategori'])) {
                foreach (self::KATEGORI_KELIMELERI as $kelime) {
                    if (str_contains($n, $kelime)) {
                        $sutunlar[$c] = 'kategori';
                        $dolu['kategori'] = true;

                        continue 2;
                    }
                }
            }

            foreach (self::PUAN_ALANLARI as $alan => $kelimeler) {
                if (isset($dolu[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($n, $kelime) && static::sutunSayisalMi($sheet, $c, $baslikSatiri)) {
                        $sutunlar[$c] = $alan;
                        $dolu[$alan] = true;

                        continue 3;
                    }
                }
            }

            foreach (self::METIN_ALANLARI as $alan => $kelimeler) {
                if (isset($dolu[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($n, $kelime)) {
                        $sutunlar[$c] = $alan;
                        $dolu[$alan] = true;

                        continue 3;
                    }
                }
            }
        }

        return $sutunlar;
    }

    private static function sutunSayisalMi($sheet, int $col, int $baslikSatiri): bool
    {
        $kontrol = 0;

        for ($r = $baslikSatiri + 1; $r <= $baslikSatiri + 20 && $kontrol < 5; $r++) {
            $deger = $sheet->getCell([$col, $r])->getValue();

            if ($deger === null || $deger === '') {
                continue;
            }

            $kontrol++;

            if (! is_numeric($deger)) {
                return false;
            }
        }

        return $kontrol > 0;
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($metin)) ?? '';
    }

    private static function sayi($deger): ?float
    {
        return is_numeric($deger) ? (float) $deger : null;
    }

    private static function bosla($deger): ?string
    {
        $deger = is_string($deger) ? trim($deger) : $deger;

        return ($deger === '' || $deger === null) ? null : (string) $deger;
    }

    private static function kisalt($deger): ?string
    {
        $deger = static::bosla($deger);

        return $deger === null ? null : Str::limit($deger, 250, '');
    }
}
