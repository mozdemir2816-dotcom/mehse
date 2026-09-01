<?php

namespace App\Support;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Risk Kütüphanesi'ne (TehlikeKategorisi + Tehlike) Excel/CSV'den toplu tehlike
 * yükleme. Kategori sütunundaki değer mevcut değilse otomatik oluşturulur —
 * böylece "Kazı Çalışmaları", "Kalıp İşleri", "Cam Üretimi" gibi yeni sektör/iş
 * kategorileri, yükleme sırasında kendiliğinden kütüphaneye eklenir. Aynı
 * kategoride aynı tehlike metni tekrar yüklenirse güncellenir (mükerrer oluşmaz).
 */
class TehlikeExcelIceAktarici
{
    private const ALAN_ESLESME = [
        'kategori' => 'kategori',
        'kod' => 'kod',
        'bolum' => 'bolum',
        'faaliyet' => 'faaliyet',
        'tehlike' => 'tehlike',
        'risk' => 'risk',
        'mevcutonlem' => 'mevcut_onlem',
        'onlem' => 'mevcut_onlem',
        'mevzuat' => 'mevzuat',
    ];

    public const SABLON_BASLIKLARI = [
        'Kategori', 'Kod', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut Önlem', 'Mevzuat',
    ];

    /**
     * @return array{basarili: int, yeniKategori: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu): array
    {
        $satirlar = IOFactory::load($dosyaYolu)
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($satirlar) < 2) {
            return ['basarili' => 0, 'yeniKategori' => 0, 'hatalar' => ['Dosyada veri satırı bulunamadı.']];
        }

        $sutunlar = static::sutunEslestir(array_shift($satirlar));

        if (! in_array('kategori', $sutunlar, true) || ! in_array('tehlike', $sutunlar, true)) {
            return ['basarili' => 0, 'yeniKategori' => 0, 'hatalar' => ['"Kategori" ve "Tehlike" sütunları zorunlu. Şablonu indirip kontrol edin.']];
        }

        $basarili = 0;
        $hatalar = [];
        $yeniKategoriAnahtarlari = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['kategori'] ?? null) || blank($veri['tehlike'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Kategori veya Tehlike boş, atlandı.";

                continue;
            }

            try {
                $kategori = static::kategoriBul($veri['kategori']);

                if ($kategori->wasRecentlyCreated) {
                    $yeniKategoriAnahtarlari[$kategori->anahtar] = true;
                }

                Tehlike::updateOrCreate(
                    ['tehlike_kategorisi_id' => $kategori->id, 'tehlike' => $veri['tehlike']],
                    [
                        'kod' => $veri['kod'] ?? null,
                        'bolum' => $veri['bolum'] ?? null,
                        'faaliyet' => $veri['faaliyet'] ?? null,
                        'risk' => $veri['risk'] ?? null,
                        'mevcut_onlem' => $veri['mevcut_onlem'] ?? null,
                        'mevzuat' => $veri['mevzuat'] ?? null,
                    ],
                );
                $basarili++;
            } catch (Throwable $e) {
                $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
            }
        }

        return ['basarili' => $basarili, 'yeniKategori' => count($yeniKategoriAnahtarlari), 'hatalar' => $hatalar];
    }

    public static function sablonIndir(): StreamedResponse
    {
        $kitap = static::sablonUret();
        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'risk-kutuphanesi-yukleme-sablonu.xlsx');
    }

    private static function sablonUret(): Spreadsheet
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->fromArray([
            'Kazı Çalışmaları', 'K1', 'Şantiye', 'Kazı', 'İksasız derin kazı',
            'Göçük altında kalma', 'Şev açısı / iksa hesabı yapılır, kazı kenarında yük yasağı uygulanır.',
            'Yapı İşlerinde İSG Yönetmeliği',
        ], null, 'A2');

        foreach (range('A', 'H') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        return $kitap;
    }

    private static function kategoriBul(string $ad): TehlikeKategorisi
    {
        $anahtar = static::anahtarUret($ad);

        return TehlikeKategorisi::firstOrCreate(
            ['anahtar' => $anahtar],
            ['ad' => $ad, 'sira' => TehlikeKategorisi::max('sira') + 1],
        );
    }

    private static function anahtarUret(string $ad): string
    {
        $anahtar = Str::slug($ad, '_');

        return $anahtar !== '' ? $anahtar : 'kategori_'.substr(md5($ad), 0, 8);
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

    /** @return array<string, string> */
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

            $veri[$alan] = is_string($deger) ? $deger : (string) $deger;
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
