<?php

namespace App\Support;

use App\Models\Calisan;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Bir firmaya Excel/CSV'den toplu çalışan (personel) yükleme. Firma bağlamdan
 * gelir (RelationManager'dan çağrılır) — Excel'de firma sütunu yoktur. Yalnız
 * "Ad Soyad" zorunlu. T.C. Kimlik No verilmişse aynı firmada aynı TC ile
 * tekrar yüklenen satır günceller (mükerrer oluşmaz); yoksa ad soyad eşleşmesi
 * kullanılır.
 */
class CalisanExcelIceAktarici
{
    private const ALAN_ESLESME = [
        'adsoyad' => 'ad_soyad',
        'tckimlikno' => 'tc',
        'tc' => 'tc',
        'gorevi' => 'gorev',
        'gorev' => 'gorev',
        'departman' => 'departman',
        'isegiris' => 'ise_giris',
        'istencikis' => 'isten_cikis',
        'dogumtarihi' => 'dogum_tarihi',
        'kangrubu' => 'kan_grubu',
        'telefon' => 'telefon',
        'eposta' => 'eposta',
        'email' => 'eposta',
        'agirvetehlikeliis' => 'agir_tehlikeli_iste',
        'agirtehlikeliis' => 'agir_tehlikeli_iste',
        'agirvetehlikelisi' => 'agir_tehlikeli_iste',
        'aktif' => 'aktif',
        'notlar' => 'notlar',
    ];

    public const SABLON_BASLIKLARI = [
        'Ad Soyad', 'T.C. Kimlik No', 'Görevi', 'Departman', 'İşe Giriş', 'İşten Çıkış',
        'Doğum Tarihi', 'Kan Grubu', 'Telefon', 'E-posta', 'Ağır ve Tehlikeli İş', 'Aktif', 'Notlar',
    ];

    /**
     * @return array{basarili: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, int $firmaId): array
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

        if (! in_array('ad_soyad', $sutunlar, true)) {
            return ['basarili' => 0, 'hatalar' => ['"Ad Soyad" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.']];
        }

        $basarili = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['ad_soyad'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Ad Soyad boş, atlandı.";

                continue;
            }

            $veri['firma_id'] = $firmaId;

            try {
                $anahtar = filled($veri['tc'] ?? null)
                    ? ['firma_id' => $firmaId, 'tc' => $veri['tc']]
                    : ['firma_id' => $firmaId, 'ad_soyad' => $veri['ad_soyad']];

                Calisan::updateOrCreate($anahtar, $veri);
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
        }, 'calisan-yukleme-sablonu.xlsx');
    }

    private static function sablonUret(): Spreadsheet
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->fromArray([
            'Ahmet Yılmaz', '12345678901', 'Şantiye Şefi', 'Üretim', '01.03.2024', '',
            '15.05.1985', 'A Rh+', '5551234567', 'ahmet@ornek.com', 'Evet', 'Evet', '',
        ], null, 'A2');

        foreach (range('A', 'M') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        return $kitap;
    }

    /** @return array<int, string|null> sütun indeksi => Çalışan alan adı */
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
                'ise_giris', 'isten_cikis', 'dogum_tarihi' => static::tarihCoz($deger),
                'agir_tehlikeli_iste', 'aktif' => static::boolCoz($deger),
                'tc' => preg_replace('/\D/', '', (string) $deger) ?: null,
                default => is_string($deger) ? $deger : (string) $deger,
            };
        }

        return $veri;
    }

    private static function tarihCoz(mixed $deger): ?string
    {
        try {
            if (is_numeric($deger)) {
                return Date::excelToDateTimeObject((float) $deger)->format('Y-m-d');
            }

            return Carbon::parse((string) $deger)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function boolCoz(mixed $deger): bool
    {
        if (is_bool($deger)) {
            return $deger;
        }

        $normalize = static::normalize((string) $deger);

        return in_array($normalize, ['evet', 'e', 'var', 'true', '1', 'yes'], true);
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
