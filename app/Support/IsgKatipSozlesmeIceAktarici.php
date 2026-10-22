<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsgKatipSozlesmesi;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * İSG-KATİP > Süreç Yönetimi > İSG Hizmet Sözleşmeleri ekranındaki native
 * "Tümünü Dışa aktar" / "Bu Sayfayı Dışa aktar" butonunun ürettiği Excel'i
 * içe aktarır (gerçek export'tan çıkarılan 35 sütun başlığı — bkz.
 * ALAN_ESLESME). Devlet sistemine hiçbir giriş/otomasyon yapılmaz; kullanıcı
 * dosyayı kendi oturumunda indirip burada yükler. "Sözleşme ID" devletin
 * kendi kimliği olduğundan dedup/güncelleme anahtarı olarak kullanılır —
 * aynı dosya (veya daha güncel bir dışa aktarım) tekrar yüklenirse satırlar
 * güncellenir, çoğalmaz. "Hizmet Alan İşyeri SGK/DETSİS No" eşleşirse kayıt
 * ilgili Firma'ya bağlanır (yalnız bilgi amaçlı; eşleşmezse firma_id boş kalır).
 */
class IsgKatipSozlesmeIceAktarici
{
    private const ALAN_ESLESME = [
        'sozlesmeid' => 'katip_sozlesme_id',
        'sozlesmeadi' => 'sozlesme_adi',
        'otoguncellemeiznivarmi' => 'oto_guncelleme_izni',
        'sozlesmemevzuatauygunmu' => 'mevzuata_uygun_mu',
        'gorevlendirilenkisitckimlikno' => 'kisi_tc',
        'gorevlendirilenkisiadsoyad' => 'kisi_ad',
        'gorevlendirilenkisisertifikatipi' => 'sertifika_tipi',
        'gorevlendirilenkisisertifikano' => 'sertifika_no',
        'sozlesmetipi' => 'sozlesme_tipi',
        'calismasuresi' => 'calisma_suresi',
        'hizmetverenisyeriunvani' => 'hizmet_veren_unvan',
        'hizmetverenisyerisgkdetsisno' => 'hizmet_veren_sgk_no',
        'hizmetverenisyeriili' => 'hizmet_veren_ili',
        'hizmetverenisyeriyetkibelgesino' => 'hizmet_veren_yetki_no',
        'hizmetalanisyeriunvani' => 'hizmet_alan_unvan',
        'hizmetalanisyerisgkdetsisno' => 'hizmet_alan_sgk_no',
        'hizmetalanisyeriili' => 'hizmet_alan_ili',
        'hizmetalanisyericalisansayisi' => 'hizmet_alan_calisan_sayisi',
        'hizmetalanisyeritehlikesinifi' => 'hizmet_alan_tehlike_sinifi',
        'sozlesmebaslangictarihi' => 'baslangic_tarihi',
        'sozlesmebitistarihi' => 'bitis_tarihi',
        'sozlesmestatusu' => 'statu',
        'sozlesmeonaydurumu' => 'onay_durumu',
        'sozlesmeonaytarihi' => 'onay_tarihi',
        'onaylayankisiadsoyad' => 'onaylayan_ad',
        'sozlesmeyisonlandiranadsoyad' => 'sonlandiran_ad',
        'sozlesmesonlandirilmanedeni' => 'sonlandirma_nedeni',
    ];

    private const TARIH_ALANLARI = ['baslangic_tarihi', 'bitis_tarihi', 'onay_tarihi'];

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

        $basliklar = array_shift($satirlar);
        $sutunlar = static::sutunEslestir($basliklar);

        if (! in_array('katip_sozlesme_id', $sutunlar, true)) {
            return ['basarili' => 0, 'hatalar' => ['"Sözleşme ID" sütunu bulunamadı. İSG-KATİP\'teki "Tümünü Dışa aktar" çıktısını değiştirmeden yükleyin.']];
        }

        $firmaSgkEslesme = Firma::query()->where('user_id', $userId)->whereNotNull('sgk_sicil_no')
            ->pluck('id', 'sgk_sicil_no');

        $basarili = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $veri = static::satiriEslestir($satir, $sutunlar, $basliklar);

            if (blank($veri['katip_sozlesme_id'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Sözleşme ID boş, atlandı.";

                continue;
            }

            $veri['user_id'] = $userId;
            $veri['firma_id'] = $firmaSgkEslesme[$veri['hizmet_alan_sgk_no'] ?? null] ?? null;
            $veri['son_iceaktarim_at'] = now();

            try {
                IsgKatipSozlesmesi::updateOrCreate(
                    ['user_id' => $userId, 'katip_sozlesme_id' => $veri['katip_sozlesme_id']],
                    $veri,
                );
                $basarili++;
            } catch (Throwable $e) {
                $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
            }
        }

        return ['basarili' => $basarili, 'hatalar' => $hatalar];
    }

    /** @return array<int, string|null> sütun indeksi => IsgKatipSozlesmesi alan adı */
    private static function sutunEslestir(array $baslikSatiri): array
    {
        $sutunlar = [];

        foreach ($baslikSatiri as $i => $baslik) {
            $sutunlar[$i] = self::ALAN_ESLESME[static::normalize((string) $baslik)] ?? null;
        }

        return $sutunlar;
    }

    /** @return array<string, mixed> */
    private static function satiriEslestir(array $satir, array $sutunlar, array $basliklar): array
    {
        $veri = [];
        $hamVeri = [];

        foreach ($satir as $i => $hucre) {
            $deger = is_string($hucre) ? trim($hucre) : $hucre;
            $alan = $sutunlar[$i] ?? null;

            if ($deger === '' || $deger === null) {
                continue;
            }

            if ($alan === null) {
                // Eşleşmeyen sütun (plaka, ilgili tetkikler/ölçümler/ekipmanlar, tanımlayan kişi vb.) ham veride saklanır.
                $hamVeri[(string) ($basliklar[$i] ?? $i)] = $deger;

                continue;
            }

            $veri[$alan] = match (true) {
                in_array($alan, self::TARIH_ALANLARI, true) => static::tarihCoz($deger),
                $alan === 'oto_guncelleme_izni' => static::evetHayirCoz($deger),
                $alan === 'mevzuata_uygun_mu' => static::uygunCoz($deger),
                $alan === 'hizmet_alan_calisan_sayisi' => (int) preg_replace('/\D/', '', (string) $deger) ?: null,
                $alan === 'hizmet_alan_sgk_no', $alan === 'hizmet_veren_sgk_no' => preg_replace('/\s+/', '', (string) $deger),
                default => is_string($deger) ? $deger : (string) $deger,
            };
        }

        $veri['ham_veri'] = $hamVeri ?: null;

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

    private static function evetHayirCoz(mixed $deger): bool
    {
        return static::normalize((string) $deger) === 'evet';
    }

    private static function uygunCoz(mixed $deger): bool
    {
        return static::normalize((string) $deger) === 'uygun';
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
