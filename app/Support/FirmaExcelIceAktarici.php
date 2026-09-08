<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Firmalar listesine Excel/CSV'den toplu firma yükleme. İlk satır başlık kabul
 * edilir; sütun adları Türkçe karakter/boşluk farkı gözetmeksizin eşleştirilir
 * (bkz. ALAN_ESLESME). Yalnızca "Unvan" zorunludur, diğer sütunlar isteğe bağlı
 * ve sırasız olabilir.
 */
class FirmaExcelIceAktarici
{
    /** Normalize edilmiş sütun başlığı => Firma alanı. */
    private const ALAN_ESLESME = [
        'unvan' => 'unvan',
        'ticariunvan' => 'unvan',
        'kisaad' => 'kisa_ad',
        'sgksicilno' => 'sgk_sicil_no',
        'sgkno' => 'sgk_sicil_no',
        'vergino' => 'vergi_no',
        'katipno' => 'katip_no',
        'isgkatipno' => 'katip_no',
        'nacekodu' => 'nace_kodu',
        'naceaciklama' => 'nace_aciklama',
        'naceaciklamasi' => 'nace_aciklama',
        'tehlikesinifi' => 'tehlike_sinifi',
        'isveren' => 'isveren_ad',
        'isverenadi' => 'isveren_ad',
        'isverenvekili' => 'isveren_vekili',
        'telefon' => 'telefon',
        'eposta' => 'eposta',
        'email' => 'eposta',
        'adres' => 'adres',
        'il' => 'il',
        'ilce' => 'ilce',
        'calisansayisi' => 'calisan_sayisi',
        'sozlesmebaslangic' => 'sozlesme_baslangic',
        'sozlesmebaslangici' => 'sozlesme_baslangic',
        'sozlesmebitis' => 'sozlesme_bitis',
        'sozlesmebitisi' => 'sozlesme_bitis',
        'notlar' => 'notlar',
    ];

    /** Şablon dosyasında gösterilecek başlık sırası (Türkçe). */
    public const SABLON_BASLIKLARI = [
        'Unvan', 'Kısa Ad', 'SGK Sicil No', 'Vergi No', 'İSG-KATİP No',
        'NACE Kodu', 'NACE Açıklama', 'Tehlike Sınıfı', 'İşveren',
        'İşveren Vekili', 'Telefon', 'E-posta', 'Adres', 'İl', 'İlçe',
        'Çalışan Sayısı', 'Sözleşme Başlangıç', 'Sözleşme Bitiş', 'Notlar',
    ];

    /**
     * @return array{basarili: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, int $userId): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        $satirlar = $reader->load($dosyaYolu)
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($satirlar) < 2) {
            return ['basarili' => 0, 'hatalar' => ['Dosyada veri satırı bulunamadı.']];
        }

        $sutunlar = static::sutunEslestir(array_shift($satirlar));

        if (! in_array('unvan', $sutunlar, true)) {
            return ['basarili' => 0, 'hatalar' => ['"Unvan" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.']];
        }

        $basarili = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2; // başlık satırı + 1-index

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['unvan'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Unvan boş, atlandı.";

                continue;
            }

            $veri['user_id'] = $userId;

            try {
                Firma::create($veri);
                $basarili++;
            } catch (Throwable $e) {
                $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
            }
        }

        return ['basarili' => $basarili, 'hatalar' => $hatalar];
    }

    public static function sablonIndir(): StreamedResponse
    {
        $kitap = static::sablonUret();
        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'firma-yukleme-sablonu.xlsx');
    }

    private static function sablonUret(): Spreadsheet
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->fromArray([
            'Örnek A.Ş.', 'Örnek', '1234567', '1234567890', '',
            '25.12', 'İplik büküm', 'Az Tehlikeli', 'Ahmet Yılmaz', '',
            '5551234567', 'info@ornek.com', 'Örnek Mah. No:1', 'İstanbul', 'Kadıköy',
            25, '2026-01-01', '', '',
        ], null, 'A2');

        foreach (range('A', 'S') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        return $kitap;
    }

    /** @return array<int, string|null> sütun indeksi => Firma alan adı */
    private static function sutunEslestir(array $baslikSatiri): array
    {
        $sutunlar = [];

        foreach ($baslikSatiri as $i => $baslik) {
            $sutunlar[$i] = self::ALAN_ESLESME[static::normalize((string) $baslik)] ?? null;
        }

        return $sutunlar;
    }

    /** @return array<string, mixed> */
    private static function satiriEslestir(array $satir, array $sutunlar): array
    {
        $veri = [];

        foreach ($sutunlar as $i => $alan) {
            if ($alan === null) {
                continue;
            }

            $deger = is_string($satir[$i] ?? null) ? trim($satir[$i]) : ($satir[$i] ?? null);

            if ($deger === '' || $deger === null) {
                continue;
            }

            $veri[$alan] = match ($alan) {
                'tehlike_sinifi' => static::tehlikeSinifiCoz((string) $deger),
                'calisan_sayisi' => (int) $deger,
                'sozlesme_baslangic', 'sozlesme_bitis' => static::tarihCoz($deger),
                default => is_string($deger) ? $deger : (string) $deger,
            };
        }

        return $veri;
    }

    private static function tehlikeSinifiCoz(string $deger): string
    {
        $normalize = static::normalize($deger);

        foreach (config('isg.tehlike_siniflari', []) as $anahtar => $etiket) {
            if ($normalize === static::normalize($anahtar) || $normalize === static::normalize($etiket)) {
                return $anahtar;
            }
        }

        return 'az_tehlikeli';
    }

    private static function tarihCoz(mixed $deger): ?string
    {
        try {
            if (is_numeric($deger)) {
                return ExcelTarih::excelToDateTimeObject((float) $deger)->format('Y-m-d');
            }

            return Carbon::parse((string) $deger)->toDateString();
        } catch (Throwable) {
            return null;
        }
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
