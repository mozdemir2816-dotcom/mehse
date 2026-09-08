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

    /**
     * Çok sayfalı dosyaları da destekler — her sayfada bir risk tablosu aranır ve
     * hepsinden okunan satırlar birleştirilir (ör. "Beton", "kazı" gibi ayrı ayrı
     * iş kalemi sayfaları). Başlık 1 veya 2 satırlık olabilir (ana başlık + O1/F1/S1
     * alt başlığı).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function excelSatirlari(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);

        $satirlar = [];

        foreach ($kitap->getAllSheets() as $sheet) {
            foreach (static::sayfaSatirlari($sheet) as $s) {
                $satirlar[] = $s;
            }
        }

        return $satirlar;
    }

    /** @return array<int, array<string, mixed>> */
    private static function sayfaSatirlari($sheet): array
    {
        $maxRow = $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        [$veriBaslangic, $sutunlar] = static::baslikBandiBul($sheet, $maxRow, $maxCol);

        if ($veriBaslangic === null || ! in_array('tehlike', $sutunlar, true)) {
            return [];
        }

        // Kategori sütunu yoksa sayfa adını kategori kabul et.
        $sayfaKategorisi = in_array('kategori', $sutunlar, true) ? null : trim((string) $sheet->getTitle());

        $satirlar = [];
        $bos = 0;

        for ($r = $veriBaslangic; $r <= $maxRow; $r++) {
            $veri = [];
            $doluMu = false;

            foreach ($sutunlar as $c => $alan) {
                $deger = $sheet->getCell([$c, $r])->getCalculatedValue();
                $deger = is_string($deger) ? trim($deger) : $deger;

                if ($deger === '' || $deger === null || (is_string($deger) && str_starts_with($deger, '#'))) {
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

            if (blank($veri['tehlike'] ?? null)) {
                continue;
            }

            if ($sayfaKategorisi && blank($veri['kategori'] ?? null)) {
                $veri['kategori'] = $sayfaKategorisi;
            }

            $satirlar[] = $veri;
        }

        return $satirlar;
    }

    /**
     * Başlık bandını (1-2 satır) bulur ve sütunları eşler.
     *
     * @return array{0:int|null, 1:array<int,string>} [veri başlangıç satırı, sütun eşlemesi]
     */
    private static function baslikBandiBul($sheet, int $maxRow, int $maxCol): array
    {
        $tara = min($maxRow, self::BASLIK_TARAMA_LIMIT);
        $enIyi = [null, []];
        $enIyiPuan = 0;

        for ($r = 1; $r < $tara; $r++) {
            $altSatirMi = static::altBaslikMi($sheet, $r + 1, $maxCol);
            $veriBaslangic = $altSatirMi ? $r + 2 : $r + 1;

            $sutunlar = static::sutunlariEsle($sheet, $r, $altSatirMi ? $r + 1 : null, $maxCol, $veriBaslangic);

            if (! in_array('tehlike', $sutunlar, true)) {
                continue;
            }

            $puanAlaniVar = (bool) array_intersect(['olasilik', 'frekans', 'siddet'], $sutunlar);

            if (! $puanAlaniVar) {
                continue;
            }

            $puan = count($sutunlar);

            if ($puan > $enIyiPuan) {
                $enIyiPuan = $puan;
                $enIyi = [$veriBaslangic, $sutunlar];
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

            if (preg_match('/(olasilik|olasılık|frekans|siddet|şiddet|puan|skor|seviye|derece|maruziyet|^[ofsr]\s*\d)/iu', $deger)) {
                $isaret++;
            }
        }

        return $isaret >= 2 && $uzunMetin === 0;
    }

    /**
     * Sütunları eşler — başlık metni, ana başlık satırı ile (varsa) alt başlık
     * satırının birleştirilmiş hâlidir.
     *
     * @return array<int, string> sütun indeksi => alan
     */
    private static function sutunlariEsle($sheet, int $anaSatir, ?int $altSatir, int $maxCol, int $veriBaslangic): array
    {
        $sutunlar = [];
        $dolu = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $ana = $sheet->getCell([$c, $anaSatir])->getValue();
            $alt = $altSatir ? $sheet->getCell([$c, $altSatir])->getValue() : null;

            $n = static::normalize(trim((string) $ana).' '.trim((string) $alt));

            if ($n === '') {
                continue;
            }

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
                    if (str_contains($n, $kelime) && static::sutunSayisalMi($sheet, $c, $veriBaslangic)) {
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

    private static function sutunSayisalMi($sheet, int $col, int $veriBaslangic): bool
    {
        $kontrol = 0;

        for ($r = $veriBaslangic; $r <= $veriBaslangic + 25 && $kontrol < 5; $r++) {
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
