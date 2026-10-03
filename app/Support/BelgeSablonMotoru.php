<?php

namespace App\Support;

use App\Models\Firma;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use ZipArchive;

/**
 * Kullanıcı şablonlarında {{alan}} yer tutucularını bulur ve doldurur.
 * - .xlsx: PhpSpreadsheet ile hücre metinleri (biçim, birleştirme, görseller korunur).
 * - .docx: document / header / footer XML'i; Word bir yer tutucuyu birden çok
 *   koşuya bölebildiği için paragraf düzeyinde OoxmlMetinYamasi ile yamalanır.
 * Sistem alanları (config arsiv.sistem_alanlari) firmadan doldurulur; diğerleri
 * kullanıcıdan istenir.
 */
final class BelgeSablonMotoru
{
    public const DESEN = '/\{\{\s*([A-Za-z0-9_.]+)\s*\}\}/u';

    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public static function tur(string $dosyaAdi): ?string
    {
        $uzanti = strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION));

        return in_array($uzanti, ['xlsx', 'docx'], true) ? $uzanti : null;
    }

    /** @return array<int, string> şablondaki yer tutucu anahtarları (ilk görülme sırasıyla, tekil) */
    public static function yerTutucular(string $tamYol, string $tur): array
    {
        $metinler = $tur === 'xlsx' ? self::xlsxMetinleri($tamYol) : self::docxMetinleri($tamYol);
        $anahtarlar = [];

        foreach ($metinler as $metin) {
            preg_match_all(self::DESEN, $metin, $m);
            array_push($anahtarlar, ...$m[1]);
        }

        return array_values(array_unique($anahtarlar));
    }

    /**
     * Firma ve kayıttan doldurulabilen sistem alanları (boş olanlar null).
     *
     * @param  array{yil?: ?int, tarih?: ?string}  $kayit
     * @return array<string, ?string>
     */
    public static function sistemDegerleri(?Firma $firma, array $kayit = []): array
    {
        $calisanlar = $firma?->calisanlar()->where('aktif', true)->get(['cinsiyet']) ?? collect();
        $tarih = filled($kayit['tarih'] ?? null) ? Carbon::parse($kayit['tarih'])->format('d.m.Y') : null;
        $yil = filled($kayit['yil'] ?? null) ? (string) $kayit['yil'] : null;
        $bos = fn ($v) => filled($v) ? (string) $v : null;

        return [
            'isyeri.unvan' => $bos($firma?->unvan),
            'isyeri.adres' => $bos(trim(implode(' ', array_filter([$firma?->adres, $firma?->ilce, $firma?->il ?: $firma?->sehir])))),
            'isyeri.telefon' => $bos($firma?->telefon),
            'isyeri.eposta' => $bos($firma?->eposta),
            'isyeri.sgk_sicil' => $bos($firma?->sgk_sicil_no),
            'isyeri.tehlike_sinifi' => $bos(config('isg.tehlike_siniflari.'.$firma?->tehlike_sinifi)),
            'isyeri.nace' => $bos($firma?->nace_kodu),
            'isyeri.iskolu' => $bos($firma?->nace_aciklama),
            'isyeri.calisan_sayisi' => $bos($calisanlar->count() ?: $firma?->calisan_sayisi),
            'isyeri.katip_no' => $bos($firma?->katip_no),
            'erkek_sayisi' => $calisanlar->isNotEmpty() ? (string) $calisanlar->where('cinsiyet', 'erkek')->count() : null,
            'kadin_sayisi' => $calisanlar->isNotEmpty() ? (string) $calisanlar->where('cinsiyet', 'kadin')->count() : null,
            'yetkili' => $bos($firma?->isveren_vekili ?: ($firma?->isveren_ad ?: $firma?->yetkili_ad)),
            'uzman.ad' => $bos($firma?->igu?->ad_soyad ?: $firma?->user?->name),
            'uzman.sertifika_no' => $bos($firma?->igu?->sertifika_no ?: $firma?->user?->sertifika_no),
            'hekim.ad' => $bos($firma?->isyeriHekimi?->ad_soyad),
            'hekim.sertifika_no' => $bos($firma?->isyeriHekimi?->sertifika_no),
            'osgb.unvan' => null,
            'kayit.yil' => $yil,
            'kayit.plan_yili' => $yil,
            'kayit.rapor_yili' => $yil,
            'kayit.tarih' => $tarih,
            'bugun' => now()->format('d.m.Y'),
        ];
    }

    /** Yer tutucu anahtarının formda görünen adı. */
    public static function etiket(string $anahtar): string
    {
        // Anahtar noktalı ("isyeri.unvan") — config() bunu iç içe yol sanar, dizi üzerinden okunur.
        return config('arsiv.sistem_alanlari')[$anahtar]
            ?? collect(explode(' ', str_replace(['.', '_'], ' ', $anahtar)))
                ->filter()
                ->map(fn ($k) => TurkceMetin::buyuk(mb_substr($k, 0, 1)).mb_substr($k, 1))
                ->implode(' ');
    }

    /** Livewire form yolu için noktasız anahtar ("kayit.yil" → "kayit__yil"). */
    public static function formAnahtari(string $anahtar): string
    {
        return str_replace('.', '__', $anahtar);
    }

    /**
     * Şablonu doldurup yeni dosyanın içeriğini döndürür.
     *
     * @param  array<string, ?string>  $degerler  yer tutucu → değer (eksikler boş yazılır)
     */
    public static function doldur(string $tamYol, string $tur, array $degerler): string
    {
        $cevir = fn (string $metin): string => preg_replace_callback(self::DESEN, fn ($m) => (string) ($degerler[$m[1]] ?? ''), $metin);

        return $tur === 'xlsx' ? self::xlsxDoldur($tamYol, $cevir, $degerler) : self::docxDoldur($tamYol, $degerler);
    }

    /** @return array<int, string> */
    private static function xlsxMetinleri(string $tamYol): array
    {
        $metinler = [];

        foreach (IOFactory::load($tamYol)->getAllSheets() as $sayfa) {
            foreach ($sayfa->getCellCollection()->getCoordinates() as $koordinat) {
                $v = $sayfa->getCell($koordinat)->getValue();
                $v = $v instanceof RichText ? $v->getPlainText() : $v;

                if (is_string($v) && str_contains($v, '{{')) {
                    $metinler[] = $v;
                }
            }
        }

        return $metinler;
    }

    private static function xlsxDoldur(string $tamYol, callable $cevir, array $degerler): string
    {
        $kitap = IOFactory::load($tamYol);

        foreach ($kitap->getAllSheets() as $sayfa) {
            foreach ($sayfa->getCellCollection()->getCoordinates() as $koordinat) {
                $hucre = $sayfa->getCell($koordinat);
                $v = $hucre->getValue();
                $v = $v instanceof RichText ? $v->getPlainText() : $v;

                if (! is_string($v) || ! str_contains($v, '{{')) {
                    continue;
                }

                // Hücre yalnız tek bir yer tutucuysa ve değer sayıysa sayı olarak yazılır (formüller çalışsın).
                if (preg_match('/^\s*\{\{\s*([A-Za-z0-9_.]+)\s*\}\}\s*$/u', $v, $m) && is_numeric($degerler[$m[1]] ?? null)) {
                    $hucre->setValue(0 + $degerler[$m[1]]);
                } else {
                    $hucre->setValue($cevir($v));
                }
            }
        }

        $gecici = tempnam(sys_get_temp_dir(), 'bsm').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($gecici);
        $icerik = (string) file_get_contents($gecici);
        @unlink($gecici);

        return $icerik;
    }

    /** @return array<string, string> docx içindeki metin parçaları (dosya adı → xml) */
    private static function docxParcalari(ZipArchive $zip): array
    {
        $parcalar = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $ad = (string) $zip->getNameIndex($i);

            if (preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $ad)) {
                $parcalar[$ad] = (string) $zip->getFromIndex($i);
            }
        }

        return $parcalar;
    }

    /** @return array<int, string> */
    private static function docxMetinleri(string $tamYol): array
    {
        $zip = new ZipArchive;

        if ($zip->open($tamYol) !== true) {
            return [];
        }

        $metinler = [];

        foreach (self::docxParcalari($zip) as $xml) {
            [$dom, $xpath] = self::dom($xml);

            foreach ($xpath->query('//w:p') as $p) {
                $metinler[] = OoxmlMetinYamasi::paragrafMetni($xpath, $p, './/w:t');
            }
        }

        $zip->close();

        return $metinler;
    }

    private static function docxDoldur(string $tamYol, array $degerler): string
    {
        $gecici = tempnam(sys_get_temp_dir(), 'bsm').'.docx';
        copy($tamYol, $gecici);

        $zip = new ZipArchive;
        $zip->open($gecici);

        foreach (self::docxParcalari($zip) as $ad => $xml) {
            [$dom, $xpath] = self::dom($xml);

            foreach ($xpath->query('//w:p') as $p) {
                // Paragraftaki her yer tutucu (aynı paragrafta birden çok olabilir) sırayla.
                for ($tur = 0; $tur < 50; $tur++) {
                    $metin = OoxmlMetinYamasi::paragrafMetni($xpath, $p, './/w:t');

                    if (! preg_match(self::DESEN, $metin, $m, PREG_OFFSET_CAPTURE)) {
                        break;
                    }

                    $baslangic = mb_strlen(substr($metin, 0, $m[0][1]));
                    OoxmlMetinYamasi::paragrafIcindeDegistir($xpath, $p, './/w:t', $baslangic, $baslangic + mb_strlen($m[0][0]), (string) ($degerler[$m[1][0]] ?? ''));
                }
            }

            $zip->addFromString($ad, $dom->saveXML());
        }

        $zip->close();
        $icerik = (string) file_get_contents($gecici);
        @unlink($gecici);

        return $icerik;
    }

    /** @return array{0: DOMDocument, 1: DOMXPath} */
    private static function dom(string $xml): array
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);

        return [$dom, $xpath];
    }
}
