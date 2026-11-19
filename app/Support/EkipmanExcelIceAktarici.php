<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Ekipman & Periyodik Kontrol — bir firmaya Excel/CSV'den toplu iş ekipmanı
 * yükleme (isgpratik "Excel Şablonu İndir & Excel ile Toplu Ekipman Ekle").
 * Yalnız "Ekipman Adı / Tanımı" zorunludur; kategori veya tip belirtilmişse
 * config('isg.periyodik_kontrol') kataloguyla eşleştirilir ve yasal standart,
 * deney/test ile varsayılan periyot otomatik doldurulur.
 */
class EkipmanExcelIceAktarici
{
    private const ALAN_ESLESME = [
        'ekipmanaditanimi' => 'ekipman_adi',
        'ekipmanadi' => 'ekipman_adi',
        'ekipmantanimi' => 'ekipman_adi',
        'ekipman' => 'ekipman_adi',
        'makineadi' => 'ekipman_adi',
        'kategori' => 'kategori',
        'ekipmankategorisi' => 'kategori',
        'ekipmantipi' => 'tip',
        'tip' => 'tip',
        'turu' => 'tip',
        'serinoplaka' => 'seri_no',
        'serino' => 'seri_no',
        'serinosasiplaka' => 'seri_no',
        'plaka' => 'seri_no',
        'markamodel' => 'marka_model',
        'marka' => 'marka_model',
        'bulundugubolumkonum' => 'konum',
        'konum' => 'konum',
        'bolum' => 'konum',
        'bulundugukonumbolum' => 'konum',
        'kapasite' => 'kapasite',
        'kapasiteisletmedegeri' => 'kapasite',
        'periyotay' => 'muayene_periyodu_ay',
        'periyot' => 'muayene_periyodu_ay',
        'muayeneperiyoduay' => 'muayene_periyodu_ay',
        'sonmuayenetarihiggaayyyy' => 'son_muayene_tarihi',
        'sonmuayenetarihi' => 'son_muayene_tarihi',
        'kontroltarihi' => 'son_muayene_tarihi',
        'muayeneyapankurumkisi' => 'muayene_yapan',
        'muayeneyapan' => 'muayene_yapan',
        'kontroleden' => 'muayene_yapan',
        'raporno' => 'rapor_no',
    ];

    public const SABLON_BASLIKLARI = [
        'Ekipman Adı / Tanımı',
        'Kategori',
        'Ekipman Tipi',
        'Seri No / Plaka',
        'Marka / Model',
        'Bulunduğu Bölüm / Konum',
        'Kapasite',
        'Periyot (Ay)',
        'Son Muayene Tarihi (GG.AA.YYYY)',
        'Muayene Yapan Kurum/Kişi',
        'Rapor No',
    ];

    /**
     * @return array{basarili: int, guncellenen: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, Firma $firma): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        $satirlar = $reader->load($dosyaYolu)
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($satirlar) < 2) {
            return ['basarili' => 0, 'guncellenen' => 0, 'hatalar' => ['Dosyada veri satırı bulunamadı.']];
        }

        $sutunlar = static::sutunEslestir(array_shift($satirlar));

        if (! in_array('ekipman_adi', $sutunlar, true)) {
            return [
                'basarili' => 0,
                'guncellenen' => 0,
                'hatalar' => ['"Ekipman Adı / Tanımı" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.'],
            ];
        }

        $kategoriHaritasi = static::kategoriHaritasi();
        $tipHaritasi = static::tipHaritasi();
        $basarili = 0;
        $guncellenen = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['ekipman_adi'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Ekipman Adı boş, atlandı.";

                continue;
            }

            try {
                // Tip eşleşmesi (varsa kategoriyi ve yasal standart/periyodu otomatik getirir)
                $tipNorm = static::normalize($veri['tip'] ?? $veri['ekipman_adi']);
                $tanimliTip = $tipHaritasi[$tipNorm] ?? null;

                $kategoriAnahtar = $kategoriHaritasi[static::normalize($veri['kategori'] ?? '')]
                    ?? ($tanimliTip['kategori'] ?? 'kaldirma');

                $kategoriAdi = config("isg.periyodik_kontrol.kategoriler.{$kategoriAnahtar}.ad", 'Kaldırma & İletme Ekipmanları');
                $periyotAy = (int) (($veri['muayene_periyodu_ay'] ?? null) ?: ($tanimliTip['periyot_ay'] ?? 12));
                $sonMuayene = $veri['son_muayene_tarihi'] ?? null;

                $kayitVerisi = [
                    'firma_id' => $firma->id,
                    'kategori' => $kategoriAnahtar,
                    'kategori_adi' => $kategoriAdi,
                    'ekipman_adi' => $veri['ekipman_adi'],
                    'tip' => $tanimliTip['ad'] ?? ($veri['tip'] ?? $veri['ekipman_adi']),
                    'seri_no' => $veri['seri_no'] ?? null,
                    'marka_model' => $veri['marka_model'] ?? null,
                    'konum' => $veri['konum'] ?? null,
                    'kapasite' => $veri['kapasite'] ?? null,
                    'yasal_standart' => $tanimliTip['standart'] ?? null,
                    'deney_test' => $tanimliTip['deney'] ?? null,
                    'muayene_periyodu_ay' => max(1, $periyotAy),
                    'son_muayene_tarihi' => $sonMuayene,
                    'muayene_yapan' => $veri['muayene_yapan'] ?? null,
                    'rapor_no' => $veri['rapor_no'] ?? null,
                    'sonuc' => $sonMuayene ? 'uygun' : 'bekliyor',
                    'aktif' => true,
                ];

                // Seri no doluysa aynı firmada mükerrer kayıt açmak yerine mevcut ekipmanı güncelle
                $mevcut = filled($kayitVerisi['seri_no'])
                    ? IsEkipmani::where('firma_id', $firma->id)->where('seri_no', $kayitVerisi['seri_no'])->first()
                    : null;

                if ($mevcut) {
                    $mevcut->update(array_filter($kayitVerisi, fn ($v) => $v !== null));
                    $guncellenen++;
                } else {
                    IsEkipmani::create($kayitVerisi);
                    $basarili++;
                }
            } catch (Throwable $e) {
                $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
            }
        }

        return ['basarili' => $basarili, 'guncellenen' => $guncellenen, 'hatalar' => $hatalar];
    }

    public static function sablonIndir(): StreamedResponse
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->setTitle('EKİPMAN LİSTESİ');
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');

        $sayfa->fromArray([
            'Dizel Forklift 3 Ton',
            'Kaldırma & İletme Ekipmanları',
            'Forklift',
            'FRK-2024-001',
            'Linde H30D',
            'Ana Depo',
            '3000 kg',
            '12',
            now()->subMonths(2)->format('d.m.Y'),
            'TÜRKAK Akredite Muayene A.Ş.',
            'PK-2026-104',
        ], null, 'A2');

        $sayfa->fromArray([
            'Vidalı Kompresör & Hava Tankı 500 Lt',
            'Basınçlı Kap ve Tesisatlar',
            'Kompresör & Hava Tankı',
            'KMP-2023-088',
            'Atlas Copco GX7',
            'Kompresör Odası',
            '500 Litre / 10 Bar',
            '12',
            now()->subMonths(4)->format('d.m.Y'),
            'TÜRKAK Akredite Muayene A.Ş.',
            'PK-2026-105',
        ], null, 'A3');

        $sayfa->getStyle('A1:K1')->getFont()->setBold(true);
        $sayfa->getStyle('D:D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sayfa->freezePane('A2');

        foreach (range('A', 'K') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'ekipman-toplu-yukleme-sablonu.xlsx');
    }

    /** @return array<int, string|null> */
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
                'son_muayene_tarihi' => static::tarihCoz($deger),
                'muayene_periyodu_ay' => (int) preg_replace('/\D/', '', (string) $deger) ?: 12,
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

    /** @return array<string, string> */
    private static function kategoriHaritasi(): array
    {
        $harita = [];

        foreach (config('isg.periyodik_kontrol.kategoriler', []) as $anahtar => $bilgi) {
            $harita[static::normalize($anahtar)] = $anahtar;
            $harita[static::normalize($bilgi['ad'] ?? '')] = $anahtar;
        }

        return $harita;
    }

    /** @return array<string, array{kategori: string, ad: string, standart: ?string, deney: ?string, periyot_ay: int}> */
    private static function tipHaritasi(): array
    {
        $harita = [];

        foreach (config('isg.periyodik_kontrol.tipler', []) as $kategori => $tipler) {
            foreach ($tipler as $t) {
                $harita[static::normalize($t['ad'])] = [
                    'kategori' => $kategori,
                    'ad' => $t['ad'],
                    'standart' => $t['standart'] ?? null,
                    'deney' => $t['deney'] ?? null,
                    'periyot_ay' => (int) ($t['periyot_ay'] ?? 12),
                ];
            }
        }

        return $harita;
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
