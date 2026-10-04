<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use Throwable;

/**
 * İSG-KATİP "Hizmet Sözleşme Süreci → Dışa Aktar" Excel'inden (ISG_HIZMET_SOZLESME_SURECI_DISA_AKTAR_*.xlsx)
 * firma ekleme / güncelleme. Her satır bir sözleşmedir; firma "Hizmet Alan İşyeri" sütunlarından alınır.
 *
 * - Aynı işyeri (SGK no) birden çok satırda geçerse en son başlayan sözleşme esas alınır.
 * - Mevcut firma SGK no → KATİP işyeri ID → unvan sırasıyla eşleştirilir; mükerrer firma açılmaz.
 * - Devam eden / teklif aşamasındaki sözleşmeler: firma yoksa eklenir, varsa güncellenir.
 * - Sonlandırılmış / iptal sözleşmeler: yeni firma AÇILMAZ, yalnız mevcut firmanın bitiş tarihi işlenir.
 * - Unvan, kısa ad, iletişim gibi elle girilmiş alanlar ezilmez; SGK no ve il yalnız boşsa doldurulur.
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
     * @return array{eklenen: array<int, string>, guncellenen: array<int, string>, atlanan: array<int, string>, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, int $userId): array
    {
        $sonuc = ['eklenen' => [], 'guncellenen' => [], 'atlanan' => [], 'hatalar' => []];

        $satirlar = static::satirlariOku($dosyaYolu);
        $baslik = array_shift($satirlar) ?? [];

        if (! static::baslikKatipMi($baslik)) {
            $sonuc['hatalar'][] = 'Bu dosya İSG-KATİP sözleşme dışa aktarımı değil ("Hizmet Alan İşyeri Unvanı" sütunu yok).';

            return $sonuc;
        }

        $sutunlar = [];
        foreach ($baslik as $i => $b) {
            if ($alan = self::SUTUNLAR[FirmaExcelIceAktarici::normalize((string) $b)] ?? null) {
                $sutunlar[$alan] ??= $i;
            }
        }

        foreach (static::isyerleriniTopla($satirlar, $sutunlar) as $kayit) {
            try {
                static::isle($kayit, $userId, $sonuc);
            } catch (Throwable $e) {
                $sonuc['hatalar'][] = $kayit['unvan'].': '.$e->getMessage();
            }
        }

        return $sonuc;
    }

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

            Firma::create(array_filter([
                'user_id' => $userId,
                'unvan' => $kayit['unvan'],
                'sgk_sicil_no' => $kayit['sgk_sicil_no'],
                'katip_no' => $kayit['katip_no'],
                'il' => $kayit['il'],
                'calisan_sayisi' => $kayit['calisan_sayisi'],
                'tehlike_sinifi' => $kayit['tehlike_sinifi'],
                'nace_kodu' => $kayit['nace_kodu'],
                'sozlesme_baslangic' => $kayit['sozlesme_baslangic'],
            ], fn ($v) => $v !== null));

            $sonuc['eklenen'][] = $kayit['unvan'];

            return;
        }

        // Eski tarihli bir dışa aktarım (veya tarihsiz teklif satırı) firmadaki daha güncel bilgiyi geri almasın.
        if ($firma->sozlesme_baslangic
            && ($kayit['sozlesme_baslangic'] ?? '') < $firma->sozlesme_baslangic->toDateString()) {
            $sonuc['atlanan'][] = $firma->unvan.' (dosyadaki sözleşme kayıtlıdan eski)';

            return;
        }

        foreach (['katip_no', 'calisan_sayisi', 'tehlike_sinifi', 'nace_kodu', 'sozlesme_baslangic'] as $alan) {
            if ($kayit[$alan] !== null) {
                $firma->{$alan} = $kayit[$alan];
            }
        }

        foreach (['sgk_sicil_no', 'il'] as $alan) {
            if (blank($firma->{$alan}) && $kayit[$alan] !== null) {
                $firma->{$alan} = $kayit[$alan];
            }
        }

        $firma->sozlesme_bitis = $kayit['bitti'] ? $kayit['sozlesme_bitis'] : null;

        if ($firma->isDirty()) {
            $firma->save();
            $sonuc['guncellenen'][] = $firma->unvan;
        }
    }

    private static function mevcutFirma(array $kayit): ?Firma
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
