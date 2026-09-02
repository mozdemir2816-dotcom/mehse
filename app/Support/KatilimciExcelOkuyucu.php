<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu'na Excel'den toplu katılımcı ekleme. Veritabanına
 * yazmaz — yalnız satırları okuyup Ad Soyad/TC/Görev listesi döner; kayıt
 * "elle eklenenleri firmaya da kaydet" seçiliyse Sayfa tarafında yapılır.
 */
class KatilimciExcelOkuyucu
{
    private const ALAN_ESLESME = [
        'adsoyad' => 'ad_soyad',
        'tckimlikno' => 'tc',
        'tc' => 'tc',
        'gorevi' => 'gorev',
        'gorev' => 'gorev',
    ];

    public const SABLON_BASLIKLARI = ['Ad Soyad', 'T.C. Kimlik No', 'Görevi'];

    /**
     * @return array{katilimcilar: array<int, array<string, string>>, hatalar: array<int, string>}
     */
    public static function oku(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        $satirlar = $reader->load($dosyaYolu)
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($satirlar) < 2) {
            return ['katilimcilar' => [], 'hatalar' => ['Dosyada veri satırı bulunamadı.']];
        }

        $sutunlar = static::sutunEslestir(array_shift($satirlar));

        if (! in_array('ad_soyad', $sutunlar, true)) {
            return ['katilimcilar' => [], 'hatalar' => ['"Ad Soyad" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.']];
        }

        $katilimcilar = [];
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (collect($satir)->every(fn ($h) => blank(is_string($h) ? trim($h) : $h))) {
                continue;
            }

            $veri = ['ad_soyad' => null, 'tc' => null, 'gorev' => null];

            foreach ($sutunlar as $idx => $alan) {
                if ($alan === null) {
                    continue;
                }

                $deger = is_string($satir[$idx] ?? null) ? trim($satir[$idx]) : ($satir[$idx] ?? null);

                if ($deger === '' || $deger === null) {
                    continue;
                }

                $veri[$alan] = $alan === 'tc' ? (preg_replace('/\D/', '', (string) $deger) ?: null) : (string) $deger;
            }

            if (blank($veri['ad_soyad'])) {
                $hatalar[] = "Satır {$satirNo}: Ad Soyad boş, atlandı.";

                continue;
            }

            $katilimcilar[] = $veri;
        }

        return ['katilimcilar' => $katilimcilar, 'hatalar' => $hatalar];
    }

    public static function sablonIndir(): StreamedResponse
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->fromArray(['Ahmet Yılmaz', '12345678901', 'Şantiye Şefi'], null, 'A2');

        foreach (range('A', 'C') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'egitim-katilimci-sablonu.xlsx');
    }

    /** @return array<int, string|null> sütun indeksi => alan adı */
    private static function sutunEslestir(array $baslikSatiri): array
    {
        $sutunlar = [];

        foreach ($baslikSatiri as $i => $baslik) {
            $sutunlar[$i] = self::ALAN_ESLESME[static::normalize((string) $baslik)] ?? null;
        }

        return $sutunlar;
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
