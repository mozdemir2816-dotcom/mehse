<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use Throwable;

/**
 * İSG-KATİP "Hizmet Sözleşme Süreci → Dışa Aktar" Excel'inden (ISG_HIZMET_SOZLESME_SURECI_DISA_AKTAR_*.xlsx)
 * firma ekleme / güncelleme. Her satır bir sözleşmedir; firma "Hizmet Alan İşyeri" sütunlarından,
 * işveren / işveren vekili "Onaylayan Kişi Ad Soyad"dan alınır.
 *
 * - Yüklenen liste kullanıcı başına saklanır (listeKaydet): firma formunda SGK / KATİP no yazınca
 *   bilgiler buradan doldurulur, "KATİP Listesinden Seç" ile istenen firmalar toplu aktarılır.
 * - Aynı işyeri (SGK no) birden çok satırda geçerse en son başlayan sözleşme esas alınır;
 *   aynı unvanlı farklı SGK no'lu işyerleri (şubeler) ayrı firmadır.
 * - Mevcut firma SGK no → KATİP işyeri ID → unvan sırasıyla eşleştirilir; mükerrer firma açılmaz.
 * - Sonlandırılmış / iptal sözleşmeler: yeni firma AÇILMAZ; mevcut firmada bitiş tarihi boşsa işlenir.
 * - Kayıtlı firmada yalnız BOŞ alanlar doldurulur; dolu alanlara (elle girilenlere) hiç dokunulmaz.
 *   İstisna: İGU aylık çalışma süresi (katip_aylik_dk) — KATİP güncel çalışan sayısı ve tehlike
 *   sınıfına göre hesapladığı için her yüklemede en son dosyadaki değerle güncellenir.
 */
class KatipSozlesmeIceAktarici
{
    /** Normalize edilmiş KATİP sütun başlığı => iç alan adı. */
    private const SUTUNLAR = [
        'hizmetalanisyeriid' => 'katip_no',
        'hizmetalanisyeriunvani' => 'unvan',
        'hizmetalanisyerisgkdetsisno' => 'sgk_sicil_no',
        'hizmetalanisyeriili' => 'il',
        'hizmetalanisyericalisansayisi' => 'calisan_sayisi',
        'hizmetalanisyeritehlikesinifi' => 'tehlike_sinifi',
        'hizmetalanisyerinacekodu' => 'nace_kodu',
        'sozlesmebaslangictarihi' => 'sozlesme_baslangic',
        'sozlesmebitistarihi' => 'sozlesme_bitis',
        'sozlesmestatu' => 'statu',
        'onaylayankisiadsoyad' => 'onaylayan',
        'calismasuresi' => 'sure',
        'calismaperiyodu' => 'periyot',
    ];

    /** Dosyanın ilk satırı KATİP sözleşme dışa aktarımına mı ait? */
    public static function katipDosyasiMi(string $dosyaYolu): bool
    {
        try {
            $baslik = static::satirlariOku($dosyaYolu)[0] ?? [];
        } catch (Throwable) {
            return false;
        }

        return static::baslikKatipMi($baslik);
    }

    /** @param  array<int, mixed>  $baslik */
    public static function baslikKatipMi(array $baslik): bool
    {
        $normalize = array_map(fn ($b) => FirmaExcelIceAktarici::normalize((string) $b), $baslik);

        return in_array('hizmetalanisyeriunvani', $normalize, true);
    }

    /**
     * Dosyadaki tüm firmaları ekler/günceller (toplu aktarım).
     *
     * @return array{eklenen: array<int, string>, guncellenen: array<int, string>, atlanan: array<int, string>, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, int $userId): array
    {
        try {
            $kayitlar = static::dosyaOku($dosyaYolu);
        } catch (\InvalidArgumentException $e) {
            return ['eklenen' => [], 'guncellenen' => [], 'atlanan' => [], 'hatalar' => [$e->getMessage()]];
        }

        static::listeKaydet($userId, $kayitlar);

        return static::aktar($kayitlar, $userId);
    }

    /**
     * Dosyayı okuyup işyeri bazında tekilleştirilmiş kayıt listesi döner (anahtar: SGK no).
     *
     * @return array<string, array<string, mixed>>
     *
     * @throws \InvalidArgumentException KATİP dosyası değilse
     */
    public static function dosyaOku(string $dosyaYolu): array
    {
        $satirlar = static::satirlariOku($dosyaYolu);
        $baslik = array_shift($satirlar) ?? [];

        if (! static::baslikKatipMi($baslik)) {
            throw new \InvalidArgumentException('Bu dosya İSG-KATİP sözleşme dışa aktarımı değil ("Hizmet Alan İşyeri Unvanı" sütunu yok).');
        }

        $sutunlar = [];
        foreach ($baslik as $i => $b) {
            if ($alan = self::SUTUNLAR[FirmaExcelIceAktarici::normalize((string) $b)] ?? null) {
                $sutunlar[$alan] ??= $i;
            }
        }

        return static::isyerleriniTopla($satirlar, $sutunlar);
    }

    /**
     * Verilen kayıtları firmalara işler.
     *
     * @param  array<array-key, array<string, mixed>>  $kayitlar
     * @return array{eklenen: array<int, string>, guncellenen: array<int, string>, atlanan: array<int, string>, hatalar: array<int, string>}
     */
    public static function aktar(array $kayitlar, int $userId): array
    {
        $sonuc = ['eklenen' => [], 'guncellenen' => [], 'atlanan' => [], 'hatalar' => []];

        foreach ($kayitlar as $kayit) {
            try {
                static::isle($kayit, $userId, $sonuc);
            } catch (Throwable $e) {
                $sonuc['hatalar'][] = $kayit['unvan'].': '.$e->getMessage();
            }
        }

        return $sonuc;
    }

    // ── Kullanıcı başına saklanan son KATİP listesi ──────────────────────────────

    private static function listeYolu(int $userId): string
    {
        return "katip-listesi/{$userId}.json";
    }

    /** @param  array<string, array<string, mixed>>  $kayitlar */
    public static function listeKaydet(int $userId, array $kayitlar): void
    {
        Storage::disk('local')->put(static::listeYolu($userId), json_encode([
            'yuklenme' => now()->toDateTimeString(),
            'kayitlar' => $kayitlar,
        ], JSON_UNESCAPED_UNICODE));
    }

    /** @return array{yuklenme: string, kayitlar: array<string, array<string, mixed>>}|null */
    public static function liste(int $userId): ?array
    {
        $yol = static::listeYolu($userId);

        if (! Storage::disk('local')->exists($yol)) {
            return null;
        }

        $veri = json_decode((string) Storage::disk('local')->get($yol), true);

        return is_array($veri['kayitlar'] ?? null) ? $veri : null;
    }

    /** SGK sicil no veya KATİP işyeri no ile saklanan listeden işyeri bulur (boşluk/nokta farkı önemsiz). */
    public static function numarayaGoreBul(int $userId, ?string $no): ?array
    {
        $aranan = preg_replace('/\D/', '', (string) $no);

        if ($aranan === '') {
            return null;
        }

        foreach (static::liste($userId)['kayitlar'] ?? [] as $kayit) {
            if (preg_replace('/\D/', '', (string) $kayit['sgk_sicil_no']) === $aranan
                || (string) $kayit['katip_no'] === $aranan) {
                return $kayit;
            }
        }

        return null;
    }

    /** Firma formuna doldurulacak alanlar (yalnız dolu olanlar). */
    public static function formVerisi(array $kayit): array
    {
        return array_filter([
            'unvan' => $kayit['unvan'],
            'sgk_sicil_no' => $kayit['sgk_sicil_no'],
            'katip_no' => $kayit['katip_no'],
            'il' => $kayit['il'],
            'calisan_sayisi' => $kayit['calisan_sayisi'],
            'tehlike_sinifi' => $kayit['tehlike_sinifi'],
            'nace_kodu' => $kayit['nace_kodu'],
            'isveren_ad' => $kayit['onaylayan'] ?? null,
            'katip_aylik_dk' => $kayit['katip_aylik_dk'] ?? null,
            'sozlesme_baslangic' => $kayit['sozlesme_baslangic'],
            'sozlesme_bitis' => $kayit['bitti'] ? $kayit['sozlesme_bitis'] : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    // ── İç işlemler ──────────────────────────────────────────────────────────────

    /**
     * Satırları işyeri bazında tekilleştirir: aynı işyerinin en son başlayan sözleşmesi kalır
     * (eşit tarihte devam eden sözleşme sonlanmış olanın önüne geçer).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function isyerleriniTopla(array $satirlar, array $sutunlar): array
    {
        $isyerleri = [];

        foreach ($satirlar as $satir) {
            $al = fn (string $alan) => isset($sutunlar[$alan]) ? trim((string) ($satir[$sutunlar[$alan]] ?? '')) : '';

            $unvan = static::unvanTemizle($al('unvan'));
            if ($unvan === '') {
                continue;
            }

            $statu = FirmaExcelIceAktarici::normalize($al('statu'));
            $kayit = [
                'unvan' => $unvan,
                'sgk_sicil_no' => preg_replace('/\s+/', '', $al('sgk_sicil_no')) ?: null,
                'katip_no' => $al('katip_no') ?: null,
                'il' => $al('il') ?: null,
                'calisan_sayisi' => is_numeric($al('calisan_sayisi')) ? (int) $al('calisan_sayisi') : null,
                'tehlike_sinifi' => $al('tehlike_sinifi') !== '' ? FirmaExcelIceAktarici::tehlikeSinifiCoz($al('tehlike_sinifi')) : null,
                'nace_kodu' => $al('nace_kodu') ?: null,
                'onaylayan' => static::unvanTemizle($al('onaylayan')) ?: null,
                // "Çalışma Süresi" dk; yalnız aylık periyotta (KATİP İGU sözleşmeleri aylık dakika verir).
                'katip_aylik_dk' => is_numeric($al('sure')) && in_array(FirmaExcelIceAktarici::normalize($al('periyot')), ['', 'aylik'], true)
                    ? (int) $al('sure') : null,
                'sozlesme_baslangic' => static::tarih($al('sozlesme_baslangic')),
                'sozlesme_bitis' => static::tarih($al('sozlesme_bitis')),
                'bitti' => str_contains($statu, 'sonlandir') || str_contains($statu, 'iptal'),
            ];

            $anahtar = $kayit['sgk_sicil_no'] ?? $kayit['katip_no'] ?? FirmaExcelIceAktarici::normalize($unvan);
            $onceki = $isyerleri[$anahtar] ?? null;

            if ($onceki === null || static::sira($kayit) > static::sira($onceki)) {
                $isyerleri[$anahtar] = $kayit;
            }
        }

        return $isyerleri;
    }

    private static function sira(array $kayit): string
    {
        return ($kayit['sozlesme_baslangic'] ?? '0000-00-00').($kayit['bitti'] ? '0' : '1');
    }

    private static function isle(array $kayit, int $userId, array &$sonuc): void
    {
        $firma = static::mevcutFirma($kayit);

        if ($firma === null) {
            if ($kayit['bitti']) {
                $sonuc['atlanan'][] = $kayit['unvan'].' (sözleşme sona ermiş)';

                return;
            }

            Firma::create(['user_id' => $userId] + static::formVerisi($kayit));

            $sonuc['eklenen'][] = $kayit['unvan'];

            return;
        }

        // Kayıtlı firmada yalnız boş bilgiler KATİP'ten doldurulur; dolu alanlar kullanıcınındır.
        foreach (['katip_no', 'sgk_sicil_no', 'il', 'calisan_sayisi', 'tehlike_sinifi', 'nace_kodu', 'sozlesme_baslangic'] as $alan) {
            if (blank($firma->{$alan}) && $kayit[$alan] !== null) {
                $firma->{$alan} = $kayit[$alan];
            }
        }

        // Sözleşmeyi onaylayan kişi: işveren boşsa işveren; işveren başka biriyse ve vekil boşsa vekil.
        $onaylayan = $kayit['onaylayan'] ?? null;
        if ($onaylayan) {
            if (blank($firma->isveren_ad)) {
                $firma->isveren_ad = $onaylayan;
            } elseif (blank($firma->isveren_vekili)
                && FirmaExcelIceAktarici::normalize($firma->isveren_ad) !== FirmaExcelIceAktarici::normalize($onaylayan)) {
                $firma->isveren_vekili = $onaylayan;
            }
        }

        // Aylık süre: devam eden sözleşmede her zaman en son dosyadaki değer.
        if (! $kayit['bitti'] && ($kayit['katip_aylik_dk'] ?? null) !== null) {
            $firma->katip_aylik_dk = $kayit['katip_aylik_dk'];
        }

        if ($kayit['bitti'] && blank($firma->sozlesme_bitis)) {
            $firma->sozlesme_bitis = $kayit['sozlesme_bitis'];
        }

        if ($firma->isDirty()) {
            $firma->save();
            $sonuc['guncellenen'][] = $firma->unvan;
        }
    }

    /** Kayıt sistemde hangi firmaya karşılık geliyor (yoksa null). */
    public static function mevcutFirma(array $kayit): ?Firma
    {
        $firmalar = Firma::query()->get();
        $rakam = fn (?string $s) => preg_replace('/\D/', '', (string) $s);

        if ($kayit['sgk_sicil_no']
            && $firma = $firmalar->first(fn (Firma $f) => filled($f->sgk_sicil_no) && $rakam($f->sgk_sicil_no) === $rakam($kayit['sgk_sicil_no']))) {
            return $firma;
        }

        if ($kayit['katip_no'] && $firma = $firmalar->firstWhere('katip_no', $kayit['katip_no'])) {
            return $firma;
        }

        // Unvan eşleşmesi yalnız SGK no'su boş firmalarda: aynı unvanlı farklı şube (farklı SGK) birleşmesin.
        $hedef = FirmaExcelIceAktarici::normalize($kayit['unvan']);

        return $firmalar->first(fn (Firma $f) => blank($f->sgk_sicil_no) && FirmaExcelIceAktarici::normalize((string) $f->unvan) === $hedef);
    }

    /** KATİP unvanlarda boşluk yerine "__" kullanabiliyor ("FOM MAKİNA__OTOMOTİV"). */
    private static function unvanTemizle(string $unvan): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace('_', ' ', $unvan)) ?? '');
    }

    private static function tarih(string $deger): ?string
    {
        if ($deger === '') {
            return null;
        }

        try {
            if (is_numeric($deger)) {
                return ExcelTarih::excelToDateTimeObject((float) $deger)->format('Y-m-d');
            }

            if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $deger)) {
                return Carbon::createFromFormat('!d.m.Y', $deger)->toDateString();
            }

            return Carbon::parse($deger)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<int, array<int, mixed>> */
    private static function satirlariOku(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        return $reader->load($dosyaYolu)->getSheet(0)->toArray(null, true, false, false);
    }
}
