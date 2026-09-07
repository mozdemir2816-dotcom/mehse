<?php

namespace App\Support;

use App\Models\JsaSablonu;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "İş Güvenliği Analizi (JSA)" Excel dosyasını okur — referans biçim: kullanıcının
 * DUVAR ÖRME şablonu (`C:\Users\mozde\Desktop\yeni firma\risk değerlendirme\
 * duvar örme\IS_GUVENLIGI_ANALIZI_DUVAR_ORME.xlsx`).
 *
 * Yapı:
 *   1. satır  → başlık ("İŞ GÜVENLİĞİ ANALİZİ (JSA) - ...")
 *   2. satır  → "Doküman Ref: ..."  +  "Rev: 00 | Tarih: 31 Temmuz 2026"
 *   (ops.)    → "Kapsam: ..."
 *   Tablo     → Sıra No | İş Adımı | Olası Tehlikeler | Olası Sonuçlar | Başlangıç
 *               Risk | Kontrol Tedbirleri | Kalıntı Risk | Sorumlu
 *   "Notlar ve Ek Gereksinimler:" → numaralı not listesi
 *   "Onay ve İmza" → Görevi/Rolü | Adı Soyadı | İmza | Tarih  +  3 rol satırı
 *
 * Şablon BÖLÜNMEZ: tüm adımlar/notlar/imza bloğu tek `JsaSablonu` kaydına yazılır.
 */
class JsaExcelOkuyucu
{
    /** Başlık satırındaki 8 sütunun eş anlamlı anahtar kelimeleri (normalize edilmiş). */
    private const SUTUN_ANAHTARLARI = [
        'sira' => ['sirano', 'sira', 'no'],
        'is_adimi' => ['isadimi', 'isadimifaaliyet', 'faaliyet', 'adim'],
        'tehlikeler' => ['olasitehlikeler', 'potansiyeltehlikeler', 'tehlikeler', 'tehlike'],
        'sonuclar' => ['olasisonuclar', 'olasisonuclarriskler', 'sonuclar', 'riskler', 'olasisonuc'],
        'baslangic_risk' => ['baslangicriskseviyesi', 'ilkriskseviyesi', 'baslangicriskdegeri', 'baslangicrisk', 'ilkrisk'],
        'kontrol_tedbirleri' => ['kontroltedbirleri', 'kontroltedbirlerigereklikkdlerdahil', 'kontrol', 'tedbirler', 'onlemler'],
        'kalinti_risk' => ['kalintiriskseviyesi', 'kalintiriskdegeri', 'kalintirisk', 'artikrisk'],
        'sorumlu' => ['sorumlu', 'sorumluluk'],
    ];

    private const TARAMA_LIMIT = 200;

    /**
     * @return array{
     *     ok: bool,
     *     hata: ?string,
     *     baslik: string,
     *     dokuman_ref: ?string,
     *     revizyon: ?string,
     *     belge_tarihi: ?string,
     *     kapsam: ?string,
     *     adimlar: array<int, array<string, string>>,
     *     notlar: array<int, string>,
     *     imza_rolleri: array<int, array{rol: string, ad: ?string, imza: ?string, tarih: ?string}>,
     * }
     */
    public static function oku(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);

        $bos = [
            'ok' => false, 'hata' => null, 'baslik' => '', 'dokuman_ref' => null,
            'revizyon' => null, 'belge_tarihi' => null, 'kapsam' => null,
            'adimlar' => [], 'notlar' => [], 'imza_rolleri' => JsaSablonu::VARSAYILAN_IMZA_ROLLERI,
        ];

        // JSA tablosunu içeren sayfayı bul (başlık satırı olan ilk sayfa).
        foreach ($kitap->getAllSheets() as $sheet) {
            $maxRow = min($sheet->getHighestRow(), self::TARAMA_LIMIT);
            $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            $baslikSatiri = static::baslikSatiriniBul($sheet, $maxRow, $maxCol);

            if ($baslikSatiri === null) {
                continue;
            }

            $sutunlar = static::sutunlariEslestir($sheet, $baslikSatiri, $maxCol);
            [$adimlar, $sonVeriSatiri] = static::adimlariOku($sheet, $baslikSatiri, $sutunlar, $maxRow);

            if (! $adimlar) {
                return [...$bos, 'hata' => 'Başlık satırı bulundu ama altında okunabilir iş adımı yok.'];
            }

            $kunye = static::kunyeOku($sheet, $baslikSatiri, $maxCol);
            $notlar = static::notlariOku($sheet, $sonVeriSatiri, $maxRow);
            $imza = static::imzaRolleriniOku($sheet, $sonVeriSatiri, $maxRow, $maxCol);

            return [
                'ok' => true,
                'hata' => null,
                'baslik' => $kunye['baslik'] ?: 'İş Güvenliği Analizi (JSA)',
                'dokuman_ref' => $kunye['dokuman_ref'],
                'revizyon' => $kunye['revizyon'],
                'belge_tarihi' => $kunye['belge_tarihi'],
                'kapsam' => $kunye['kapsam'],
                'adimlar' => $adimlar,
                'notlar' => $notlar,
                'imza_rolleri' => $imza ?: JsaSablonu::VARSAYILAN_IMZA_ROLLERI,
            ];
        }

        return [...$bos, 'hata' => 'JSA tablosu bulunamadı — dosyada "Sıra No" ve "İş Adımı" başlıklı bir satır olmalı (örnek şablonu indirin).'];
    }

    private static function baslikSatiriniBul($sheet, int $maxRow, int $maxCol): ?int
    {
        for ($r = 1; $r <= $maxRow; $r++) {
            $siraVar = false;
            $adimVar = false;

            for ($c = 1; $c <= $maxCol; $c++) {
                $n = static::normalize((string) $sheet->getCell([$c, $r])->getValue());

                if ($n === '') {
                    continue;
                }

                if (in_array($n, ['sirano', 'sira', 'no'], true)) {
                    $siraVar = true;
                }

                if (str_contains($n, 'isadimi') || $n === 'faaliyet') {
                    $adimVar = true;
                }
            }

            if ($siraVar && $adimVar) {
                return $r;
            }
        }

        return null;
    }

    /** @return array<int, string> sütun indeksi => alan adı */
    private static function sutunlariEslestir($sheet, int $baslikSatiri, int $maxCol): array
    {
        $sutunlar = [];
        $kullanilan = [];

        for ($c = 1; $c <= $maxCol; $c++) {
            $n = static::normalize((string) $sheet->getCell([$c, $baslikSatiri])->getValue());

            if ($n === '') {
                continue;
            }

            foreach (self::SUTUN_ANAHTARLARI as $alan => $kelimeler) {
                if (isset($kullanilan[$alan])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if ($n === $kelime || str_contains($n, $kelime)) {
                        $sutunlar[$c] = $alan;
                        $kullanilan[$alan] = true;
                        continue 3;
                    }
                }
            }
        }

        return $sutunlar;
    }

    /**
     * @param  array<int, string>  $sutunlar
     * @return array{0: array<int, array<string, string>>, 1: int} [adımlar, son veri satırı]
     */
    private static function adimlariOku($sheet, int $baslikSatiri, array $sutunlar, int $maxRow): array
    {
        $siraSutun = array_search('sira', $sutunlar, true) ?: 1;
        $adimlar = [];
        $sonSatir = $baslikSatiri;
        $bosSayaci = 0;

        for ($r = $baslikSatiri + 1; $r <= $maxRow; $r++) {
            $sira = trim((string) $sheet->getCell([$siraSutun, $r])->getValue());

            $nA = static::normalize((string) $sheet->getCell([1, $r])->getValue());
            $nB = static::normalize((string) $sheet->getCell([2, $r])->getValue());

            // "Notlar", "Onay ve İmza", doküman altbilgisi ("Doküman No: ... | Sayfa 1/1")
            // gibi bir bölüm başlığı/altbilgisine gelindiyse tablo bitmiştir.
            $bitisIsareti = static function (string $n): bool {
                return $n !== '' && (
                    str_contains($n, 'notlar')
                    || (str_contains($n, 'onay') && (str_contains($n, 'imza') || str_contains($n, 'kabul')))
                    || str_contains($n, 'signoff')
                    || str_contains($n, 'acknowledgement')
                    || str_contains($n, 'dokumanno')
                    || str_contains($n, 'dokumanref')
                    || str_contains($n, 'sayfa1')
                    || str_starts_with($n, 'hazirlayan')
                );
            };

            if ($bitisIsareti($nA) || $bitisIsareti($nB)) {
                break;
            }

            $satirVeri = [];

            foreach ($sutunlar as $c => $alan) {
                $deger = trim((string) $sheet->getCell([$c, $r])->getValue());

                if ($deger !== '') {
                    $satirVeri[$alan] = $deger;
                }
            }

            if (! $satirVeri) {
                if (++$bosSayaci >= 5) {
                    break;
                }

                continue;
            }

            $bosSayaci = 0;

            // Geçerli bir iş adımı satırı: ya sayısal sıra no'su vardır, ya da sıra
            // no boş/kısa ama "İş Adımı" hücresi doludur. Uzun serbest metinli bir
            // "sıra" hücresi (örn. altbilgi) adım sayılmaz.
            $siraNumerik = (bool) preg_match('/^\d+/', $sira);

            if (! $siraNumerik && (mb_strlen($sira) > 6 || blank($satirVeri['is_adimi'] ?? null))) {
                continue;
            }

            $adimlar[] = [
                'sira' => $sira !== '' ? $sira : (string) (count($adimlar) + 1),
                'is_adimi' => $satirVeri['is_adimi'] ?? '',
                'tehlikeler' => $satirVeri['tehlikeler'] ?? '',
                'sonuclar' => $satirVeri['sonuclar'] ?? '',
                'baslangic_risk' => $satirVeri['baslangic_risk'] ?? '',
                'kontrol_tedbirleri' => $satirVeri['kontrol_tedbirleri'] ?? '',
                'kalinti_risk' => $satirVeri['kalinti_risk'] ?? '',
                'sorumlu' => $satirVeri['sorumlu'] ?? '',
            ];
            $sonSatir = $r;
        }

        return [$adimlar, $sonSatir];
    }

    /** @return array{baslik: string, dokuman_ref: ?string, revizyon: ?string, belge_tarihi: ?string, kapsam: ?string} */
    private static function kunyeOku($sheet, int $baslikSatiri, int $maxCol): array
    {
        $baslik = '';
        $ref = null;
        $rev = null;
        $tarih = null;
        $kapsam = null;

        for ($r = 1; $r < $baslikSatiri; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $ham = trim((string) $sheet->getCell([$c, $r])->getValue());

                if ($ham === '') {
                    continue;
                }

                $n = static::normalize($ham);

                if ($baslik === '' && (str_contains($n, 'jsa') || str_contains($n, 'isguvenligianalizi'))) {
                    $baslik = $ham;

                    continue;
                }

                if ($ref === null && (str_contains($n, 'dokumanref') || str_contains($n, 'dokumanno') || str_contains($n, 'dokumankodu'))) {
                    $ref = static::ikinoktadanSonra($ham);

                    continue;
                }

                if (str_contains($n, 'kapsam')) {
                    $kapsam = static::ikinoktadanSonra($ham);

                    continue;
                }

                // "Rev: 00 | Tarih: 31 Temmuz 2026" gibi birleşik hücre.
                if ($rev === null && preg_match('/rev[\.:\s]*([0-9]{1,3}[A-Za-z]?)/iu', $ham, $m)) {
                    $rev = $m[1];
                }

                if ($tarih === null && preg_match('/tarih[\.:\s]*(.+)$/iu', $ham, $m)) {
                    $tarih = trim(rtrim(trim($m[1]), '|'));
                }
            }
        }

        return ['baslik' => $baslik, 'dokuman_ref' => $ref, 'revizyon' => $rev, 'belge_tarihi' => $tarih, 'kapsam' => $kapsam];
    }

    /** @return array<int, string> */
    private static function notlariOku($sheet, int $sonVeriSatiri, int $maxRow): array
    {
        $notlar = [];
        $notBolumu = false;

        for ($r = $sonVeriSatiri + 1; $r <= $maxRow; $r++) {
            $hucre = trim((string) $sheet->getCell([1, $r])->getValue());
            $n = static::normalize($hucre);

            if ((str_contains($n, 'onay') && (str_contains($n, 'imza') || str_contains($n, 'kabul')))
                || str_contains($n, 'signoff') || str_contains($n, 'acknowledgement')
                || str_contains($n, 'gorev')) {
                break;
            }

            if (str_contains($n, 'notlar')) {
                $notBolumu = true;

                continue;
            }

            if ($notBolumu && $hucre !== '') {
                // "1. ..." önekini koru — kullanıcının orijinal numaralandırmasıdır.
                $notlar[] = $hucre;
            }
        }

        return $notlar;
    }

    /** @return array<int, array{rol: string, ad: ?string, imza: ?string, tarih: ?string}> */
    private static function imzaRolleriniOku($sheet, int $sonVeriSatiri, int $maxRow, int $maxCol): array
    {
        $roller = [];
        $imzaBolumu = false;

        for ($r = $sonVeriSatiri + 1; $r <= $maxRow; $r++) {
            $ilk = trim((string) $sheet->getCell([1, $r])->getValue());
            $n = static::normalize($ilk);

            if ((str_contains($n, 'onay') && (str_contains($n, 'imza') || str_contains($n, 'kabul')))
                || str_contains($n, 'signoff') || str_contains($n, 'acknowledgement')) {
                $imzaBolumu = true;

                continue;
            }

            if (! $imzaBolumu || $ilk === '') {
                continue;
            }

            // Doküman altbilgisi geldiyse dur.
            if (str_contains($n, 'dokumanno') || str_contains($n, 'sayfa1')) {
                break;
            }

            // Sütun başlığı satırı ("Görevi / Rolü | Adı Soyadı | İmza | Tarih") atlanır.
            if (str_contains($n, 'gorev') || $n === 'adisoyadi' || str_contains($n, 'rolgorev')) {
                continue;
            }

            $roller[] = ['rol' => $ilk, 'ad' => null, 'imza' => null, 'tarih' => null];
        }

        return $roller;
    }

    private static function ikinoktadanSonra(string $metin): string
    {
        $parcalar = explode(':', $metin, 2);

        return trim($parcalar[1] ?? $parcalar[0]);
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

    /** Kullanıcının doğru biçimi görmesi için boş örnek şablon (.xlsx). */
    public static function sablonIndir(): StreamedResponse
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('İş Güvenliği Analizi');

        $s->setCellValue('A1', 'İŞ GÜVENLİĞİ ANALİZİ (JSA) - ÖRNEK İŞ ADI');
        $s->mergeCells('A1:H1');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $s->setCellValue('A2', 'Doküman Ref: JSA-XXX-001');
        $s->setCellValue('F2', 'Rev: 00 | Tarih: 1 Ocak 2026');

        $basliklar = array_values(JsaSablonu::ADIM_SUTUNLARI);
        $s->fromArray($basliklar, null, 'A4');
        $s->getStyle('A4:H4')->getFont()->setBold(true);
        $s->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');

        $s->fromArray([
            ['1', 'İş öncesi planlama ve çalışma izni', 'Yetersiz planlama; eksik izin', 'Kontrolsüz çalışma, ciddi kaza', 'Yüksek', "• TBT yapılır\n• PTW alınır\n• KKD: baret, gözlük, eldiven", 'Düşük', 'Süpervizör / İSG Uzmanı'],
            ['2', 'Saha hazırlığı ve barikatlama', 'Düzensiz zemin; yetkisiz giriş', 'Kayma-düşme, çarpma', 'Orta', '• Zemin düzeltilir\n• Uyarı bantları çekilir', 'Düşük', 'Süpervizör'],
        ], null, 'A5');

        $s->setCellValue('A8', 'Notlar ve Ek Gereksinimler:');
        $s->mergeCells('A8:H8');
        $s->getStyle('A8')->getFont()->setBold(true);
        $s->setCellValue('A9', '1. Bu JSA yalnızca onaylı Yöntem Bildirimi ile birlikte geçerlidir.');
        $s->mergeCells('A9:H9');
        $s->setCellValue('A10', '2. Tüm personel çalışmaya başlamadan önce bilgilendirilmeli ve katılım kaydını imzalamalıdır.');
        $s->mergeCells('A10:H10');

        $s->setCellValue('A12', 'Onay ve İmza (Acknowledgement / Sign-off)');
        $s->mergeCells('A12:H12');
        $s->getStyle('A12')->getFont()->setBold(true);
        $s->fromArray(['Görevi / Rolü', 'Adı Soyadı', 'İmza', 'Tarih'], null, 'A13');
        $s->setCellValue('A14', 'Hazırlayan (İSG / HSE)');
        $s->setCellValue('A15', 'Gözden Geçiren (Süpervizör / Saha Mühendisi)');
        $s->setCellValue('A16', 'Onaylayan (Proje Müdürü / İSG Müdürü)');

        foreach (range('A', 'H') as $harf) {
            $s->getColumnDimension($harf)->setWidth(22);
        }

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'jsa-ise-ozgu-risk-sablonu.xlsx');
    }
}
