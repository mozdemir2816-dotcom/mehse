<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Örnek formunu yükle, birebir aynısını üretsin" (kullanıcı isteği
 * 04.10.2026): yer tutucusu ({{...}}) olmayan, başka bir firmanın verisiyle
 * dolu örnek Excel formlarında künye alanları ETİKETİNDEN tanınır —
 * "İşyeri Ünvanı", "Adresi", "SGK Sicil No", "Çalışma Yılı", "Tarih :"… —
 * ve etiketin sağındaki (birleştirilmiş hücre sonrası) ilk hücre seçili
 * firmanın bilgisiyle değiştirilir. Formun geri kalanı (plan satırları,
 * aylar, sonuç metinleri, biçim) olduğu gibi kalır.
 *
 * - Yalnız üst künye bölgesi taranır (ilk 14 satır); tablo başlıkları (8+
 *   dolu hücreli satırlar) genel etiketlerde ("Tarih", "Yıl", "Toplam") atlanır.
 * - Görevli adı alanları (uzman / hekim / işveren / yetkili) boşaltılır —
 *   kaşeli evrakta ad basılmaz kuralı.
 * - Başlıktaki eski yıl ("2026 - YILLIK ÇALIŞMA PLANI", "2025 YILI ...") seçilen
 *   yılla, örnekteki eski firma unvanının geçtiği başlık hücreleri yeni unvanla
 *   değiştirilir.
 */
final class EtiketliSablon
{
    private const BOLGE_SATIR = 14;

    private const TABLO_HUCRE = 8;

    /** Normalleştirilmiş etiket → sistem alanı (null: görevli adı, boşaltılır). */
    private const ETIKETLER = [
        'İŞYERİ ÜNVANI' => 'isyeri.unvan', 'İŞ YERİ ÜNVANI' => 'isyeri.unvan', 'İŞYERİNİN ÜNVANI' => 'isyeri.unvan',
        'İŞYERİ UNVANI' => 'isyeri.unvan', 'İŞ YERİ UNVANI' => 'isyeri.unvan', 'FİRMA ADI' => 'isyeri.unvan',
        'FİRMA ÜNVANI' => 'isyeri.unvan', 'FİRMA UNVANI' => 'isyeri.unvan', 'ÜNVANI' => 'isyeri.unvan', 'İŞYERİ ADI' => 'isyeri.unvan',
        'ADRES' => 'isyeri.adres', 'ADRESİ' => 'isyeri.adres', 'İŞYERİ ADRESİ' => 'isyeri.adres', 'İŞ YERİ ADRESİ' => 'isyeri.adres',
        'SGK SİCİL NO' => 'isyeri.sgk_sicil', 'SGK SİCİL NUMARASI' => 'isyeri.sgk_sicil', 'İŞYERİ SGK SİCİL NO' => 'isyeri.sgk_sicil', 'SİCİL NO' => 'isyeri.sgk_sicil',
        'TEHLİKE SINIFI' => 'isyeri.tehlike_sinifi',
        'İŞKOLU' => 'isyeri.iskolu', 'İŞ KOLU' => 'isyeri.iskolu', 'FAALİYET KONUSU' => 'isyeri.iskolu',
        'NACE KODU' => 'isyeri.nace', 'NACE' => 'isyeri.nace',
        'TELEFON' => 'isyeri.telefon', 'TEL' => 'isyeri.telefon', 'FAX' => 'isyeri.faks', 'FAKS' => 'isyeri.faks',
        'MAİL' => 'isyeri.eposta', 'E-POSTA' => 'isyeri.eposta', 'E-MAİL' => 'isyeri.eposta', 'EPOSTA' => 'isyeri.eposta',
        'ÇALIŞAN SAYISI' => 'isyeri.calisan_sayisi',
        'ÇALIŞMA YILI' => 'kayit.yil', 'PLAN YILI' => 'kayit.yil', 'RAPOR YILI' => 'kayit.yil', 'YIL' => 'kayit.yil',
        'TARİH' => 'kayit.tarih', 'RAPOR TARİHİ' => 'kayit.tarih', 'HAZIRLAMA TARİHİ' => 'kayit.tarih', 'HAZIRLANMA TARİHİ' => 'kayit.tarih',
        'ERKEK' => 'erkek_sayisi', 'KADIN' => 'kadin_sayisi', 'GENÇ' => 'genc_sayisi', 'ÇOCUK' => 'cocuk_sayisi', 'TOPLAM' => 'isyeri.calisan_sayisi',
        // görevli adları — kaşeli evrakta ad basılmaz
        'İŞ GÜVENLİĞİ UZMANI' => null, 'İSG UZMANI' => null, 'UZMAN' => null,
        'İŞYERİ HEKİMİ' => null, 'İŞ YERİ HEKİMİ' => null, 'HEKİM' => null,
        'İŞYERİ YETKİLİSİ' => null, 'İŞ YERİ YETKİLİSİ' => null, 'YETKİLİSİ' => null, 'YETKİLİ' => null,
        'İŞVEREN' => null, 'İŞVEREN VEKİLİ' => null, 'İŞVEREN / VEKİLİ' => null, 'İŞVEREN/VEKİLİ' => null,
        'SAĞLIK MEMURU' => null, 'DİĞER SAĞLIK PERSONELİ' => null,
    ];

    /** Tablo başlıklarında da geçebilen genel etiketler (dolu satırlarda atlanır). */
    private const GENEL = ['TARİH', 'YIL', 'ADRES', 'TELEFON', 'TEL', 'UZMAN', 'HEKİM', 'YETKİLİ', 'İŞVEREN'];

    public static function normalize(string $metin): string
    {
        $m = TurkceMetin::buyuk(trim(preg_replace('/\s+/u', ' ', $metin)));

        return trim(rtrim($m, ': '));
    }

    /**
     * Örnek dosyada tanınan alanlar (yükleme bildiriminde gösterilir).
     *
     * @return array<int, string> sistem alanı anahtarları + 'gorevli_adi'
     */
    public static function bul(string $tamYol): array
    {
        $kitap = IOFactory::load($tamYol);
        $bulunan = [];

        foreach ($kitap->getAllSheets() as $s) {
            foreach (self::eslesmeler($s) as $e) {
                $bulunan[] = $e['alan'] ?? 'gorevli_adi';
            }
        }

        return array_values(array_unique($bulunan));
    }

    /**
     * @param  array<string, ?string>  $degerler  BelgeSablonMotoru::sistemDegerleri (+ kullanıcı girdileri)
     */
    public static function uygula(Spreadsheet $kitap, array $degerler, ?int $yil = null): void
    {
        foreach ($kitap->getAllSheets() as $s) {
            $eskiUnvan = null;
            $eskiYil = null;

            $bosaltilan = [];

            foreach (self::eslesmeler($s) as $e) {
                $hucre = $s->getCell($e['hedef']);

                if ($e['alan'] === null) {
                    $bosaltilan[$e['hedef']] = true;
                }

                if ($e['alan'] === 'isyeri.unvan' && filled($e['eski'])) {
                    $eskiUnvan = $e['eski'];
                }

                if ($e['alan'] === 'kayit.yil' && preg_match('/^\s*(20\d{2})\s*$/', (string) $e['eski'], $m)) {
                    $eskiYil = (int) $m[1];
                }

                $yeni = $e['alan'] ? ($degerler[$e['alan']] ?? null) : null;

                if ($e['alan'] && ! filled($yeni)) {
                    // Sistemde karşılığı boş: eski firmanın verisi kalmasın.
                    $hucre->setValue(null);

                    continue;
                }

                // Yalnız küçük sayılar (çalışan sayıları) sayı olur; sicil no / telefon metin kalır.
                is_numeric($yeni) && ctype_digit((string) $yeni) && strlen((string) $yeni) <= 6 && ! str_starts_with((string) $yeni, '0')
                    ? $hucre->setValue(0 + $yeni)
                    : $hucre->setValueExplicit((string) $yeni, DataType::TYPE_STRING);
            }

            self::bagliFormulleriBosalt($s, $bosaltilan);
            $baslikYili = self::basliklariGuncelle($s, $eskiUnvan, $degerler['isyeri.unvan'] ?? null, $yil);
            $eskiYil ??= $baslikYili;

            if ($yil && $eskiYil && $eskiYil !== $yil) {
                self::yillariKaydir($s, $eskiYil, $yil);
            }
        }
    }

    /**
     * Boşaltılan görevli adı hücresine doğrudan (=M5) ya da zincirle (=C45 → =M5)
     * bağlı formüller de boşaltılır; yoksa imza satırlarında "0" görünür.
     *
     * @param  array<string, true>  $bosaltilan
     */
    private static function bagliFormulleriBosalt(Worksheet $s, array $bosaltilan): void
    {
        $formuller = [];

        foreach ($s->getCellCollection()->getCoordinates() as $koordinat) {
            $v = $s->getCell($koordinat)->getValue();

            if (is_string($v) && preg_match('/^=\s*\$?([A-Z]{1,3})\$?(\d+)\s*$/', $v, $m)) {
                $formuller[$koordinat] = $m[1].$m[2];
            }
        }

        do {
            $degisti = false;

            foreach ($formuller as $koordinat => $hedef) {
                if (isset($bosaltilan[$hedef]) && ! isset($bosaltilan[$koordinat])) {
                    $s->getCell($koordinat)->setValue(null);
                    $bosaltilan[$koordinat] = true;
                    $degisti = true;
                }
            }
        } while ($degisti);
    }

    /**
     * Örnekteki eski yıl tablo gövdesinde de yeni yıla taşınır: yalnız yıl
     * içeren sayı hücreleri (eğitim planındaki "1 / 8 / 2026" parçaları) ve
     * metin tarihler ("01.08.2026", "1/8/2026"). Formüllere dokunulmaz.
     */
    private static function yillariKaydir(Worksheet $s, int $eskiYil, int $yil): void
    {
        foreach ($s->getCellCollection()->getCoordinates() as $koordinat) {
            $c = $s->getCell($koordinat);
            $v = $c->getValue();

            if ((is_int($v) || is_float($v)) && (int) $v === $eskiYil && (float) $v === (float) $eskiYil) {
                $c->setValue($yil);
            } elseif (is_string($v) && ! str_starts_with($v, '=') && str_contains($v, (string) $eskiYil)) {
                $yeni = preg_replace('/\b(\d{1,2}[.\/-]\d{1,2}[.\/-])'.$eskiYil.'\b/u', '${1}'.$yil, $v);

                if ($yeni !== $v) {
                    $c->setValueExplicit($yeni, DataType::TYPE_STRING);
                }
            }
        }
    }

    /** @return array<int, array{etiket: string, hedef: string, alan: ?string, eski: ?string}> */
    private static function eslesmeler(Worksheet $s): array
    {
        $sonSatir = min(self::BOLGE_SATIR, $s->getHighestRow());
        $birlesimler = $s->getMergeCells();
        $eslesmeler = [];
        $hedefler = [];

        for ($r = 1; $r <= $sonSatir; $r++) {
            $doluSayisi = 0;
            $satir = [];

            foreach ($s->getRowIterator($r, $r)->current()->getCellIterator() as $c) {
                $v = $c->getValue();
                $v = $v instanceof RichText ? $v->getPlainText() : $v;

                if ($v !== null && $v !== '') {
                    $doluSayisi++;
                    $satir[$c->getCoordinate()] = (string) $v;
                }
            }

            foreach ($satir as $koordinat => $metin) {
                $etiket = self::normalize($metin);

                if (! array_key_exists($etiket, self::ETIKETLER) || str_starts_with($metin, '=')) {
                    continue;
                }

                if (in_array($etiket, self::GENEL, true) && $doluSayisi >= self::TABLO_HUCRE) {
                    continue;
                }

                $hedef = self::sagHucre($koordinat, $birlesimler);

                // Hedef kendisi bir etiketse (boş değer alanı) atla.
                $hedefMetin = $satir[$hedef] ?? null;
                if ($hedef === null || isset($hedefler[$hedef]) || ($hedefMetin !== null && array_key_exists(self::normalize($hedefMetin), self::ETIKETLER))) {
                    continue;
                }

                $hedefler[$hedef] = true;
                $eslesmeler[] = ['etiket' => $etiket, 'hedef' => $hedef, 'alan' => self::ETIKETLER[$etiket], 'eski' => $hedefMetin];
            }
        }

        return $eslesmeler;
    }

    /** Etiket hücresinin (birleştirilmişse aralığın) sağındaki ilk hücre. */
    private static function sagHucre(string $koordinat, array $birlesimler): ?string
    {
        [$sutun, $satir] = Coordinate::coordinateFromString($koordinat);
        $sonSutun = Coordinate::columnIndexFromString($sutun);

        foreach ($birlesimler as $aralik) {
            [$bas, $son] = explode(':', $aralik);
            if ($bas === $koordinat) {
                $sonSutun = Coordinate::columnIndexFromString(Coordinate::coordinateFromString($son)[0]);
                break;
            }
        }

        return $sonSutun >= 16384 ? null : Coordinate::stringFromColumnIndex($sonSutun + 1).$satir;
    }

    /**
     * Başlık bölgesinde eski yıl ve eski firma unvanı geçen hücreler güncellenir.
     *
     * @return ?int başlıktaki eski yıl (ör. "2025 YILI …" → 2025)
     */
    private static function basliklariGuncelle(Worksheet $s, ?string $eskiUnvan, ?string $yeniUnvan, ?int $yil): ?int
    {
        $sonSatir = min(self::BOLGE_SATIR, $s->getHighestRow());
        $eskiYil = null;

        for ($r = 1; $r <= $sonSatir; $r++) {
            foreach ($s->getRowIterator($r, $r)->current()->getCellIterator() as $c) {
                $v = $c->getValue();
                $v = $v instanceof RichText ? $v->getPlainText() : $v;

                if (! is_string($v) || $v === '' || str_starts_with($v, '=')) {
                    continue;
                }

                $yeni = $v;

                if ($yil && preg_match('/\b(20\d{2})\b/u', $yeni, $m) && preg_match('/YIL|PLAN|RAPOR|DEĞERLENDİRME|DEGERLENDIRME/u', TurkceMetin::buyuk($yeni))) {
                    $eskiYil ??= (int) $m[1];
                    $yeni = preg_replace('/\b20\d{2}\b/u', (string) $yil, $yeni, 1);
                }

                if ($eskiUnvan && $yeniUnvan && trim($yeni) === trim($eskiUnvan)) {
                    $yeni = $yeniUnvan;
                }

                if ($yeni !== $v) {
                    $c->setValueExplicit($yeni, DataType::TYPE_STRING);
                }
            }
        }

        return $eskiYil;
    }
}
