<?php

namespace App\Support;

use App\Models\TalimatSablonu;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Talimat Oluştur — uzmanın kendi arşivindeki talimatları Excel'den toplu
 * yükleme. Kullanıcı "talimatlara kendim ekleyeceğim, kendi arşivimden"
 * dedi — her satır bir talimat: Başlık zorunlu, Kategori (varsa isim
 * eşleşmesiyle config kategorisine bağlanır, yoksa serbest metin olarak
 * kalır), KKD'ler virgülle, Maddeler ise HÜCRE İÇİNDE HER SATIRDA BİR MADDE
 * (Excel'de Alt+Enter ile alt satıra geçilerek yazılır).
 */
class TalimatSablonuExcelIceAktarici
{
    private const ALAN_ESLESME = [
        'baslik' => 'baslik',
        'talimatbasligi' => 'baslik',
        'kategori' => 'kategori',
        'aciklama' => 'aciklama',
        'kkdler' => 'kkdler',
        'kkd' => 'kkdler',
        'gereklikkdler' => 'kkdler',
        'kkdlervirgulleayirin' => 'kkdler',
        'maddeler' => 'maddeler',
        'talimatmaddeleri' => 'maddeler',
        'adimlar' => 'maddeler',
        'maddelerhersatirdabirmadde' => 'maddeler',
    ];

    public const SABLON_BASLIKLARI = ['Başlık', 'Kategori', 'Açıklama', "KKD'ler (virgülle ayırın)", 'Maddeler (her satırda bir madde)'];

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

        if (! in_array('baslik', $sutunlar, true)) {
            return ['basarili' => 0, 'hatalar' => ['"Başlık" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.']];
        }

        $kategoriHaritasi = static::kategoriHaritasi();
        $basarili = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar);

            if (blank($veri['baslik'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Başlık boş, atlandı.";

                continue;
            }

            try {
                TalimatSablonu::create([
                    'user_id' => $userId,
                    'baslik' => $veri['baslik'],
                    'kategori' => $kategoriHaritasi[static::normalize($veri['kategori'] ?? '')] ?? null,
                    'aciklama' => $veri['aciklama'] ?? null,
                    'kkdler' => $veri['kkdler'] ?? [],
                    'maddeler' => $veri['maddeler'] ?? [],
                ]);
                $basarili++;
            } catch (Throwable $e) {
                $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
            }
        }

        return ['basarili' => $basarili, 'hatalar' => $hatalar];
    }

    public static function sablonIndir(): StreamedResponse
    {
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(static::SABLON_BASLIKLARI, null, 'A1');
        $sayfa->setCellValue('A2', 'Kompresör Kullanma Talimatı');
        $sayfa->setCellValue('B2', 'İş Makineleri');
        $sayfa->setCellValue('C2', 'Hava kompresörünün güvenli kullanımı için kurallar.');
        $sayfa->setCellValue('D2', 'Kulak tıkacı, Koruyucu gözlük');
        $sayfa->getCell('E2')->setValue("Kompresör günlük olarak basınç göstergesinden kontrol edilir.\nHortum bağlantıları sağlam olmalıdır.\nBakım öncesi enerjisi kesilir.");
        $sayfa->getStyle('E2')->getAlignment()->setWrapText(true);

        foreach (range('A', 'E') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'talimat-sablonu-yukleme.xlsx');
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
                'kkdler' => array_values(array_filter(array_map('trim', explode(',', (string) $deger)))),
                'maddeler' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $deger)))),
                default => (string) $deger,
            };
        }

        return $veri;
    }

    /** @return array<string, string> normalize(kategori adı) => config anahtarı */
    private static function kategoriHaritasi(): array
    {
        return collect(config('isg.talimat.kategoriler'))
            ->mapWithKeys(fn ($ad, $anahtar) => [static::normalize($ad) => $anahtar])
            ->all();
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
