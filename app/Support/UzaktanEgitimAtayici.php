<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Uzaktan eğitim atama kuralları — İSG uzmanı paneli (UzaktanEgitimAtama) ve işyeri paneli
 * (İşveren: Online Eğitim) aynı kuralı kullanır (13.11.2026'da sayfadan ayrıldı).
 *
 * - Yeni paket atanınca çalışanın önceki paketlerine ait TAMAMLANMAMIŞ atamalar silinir; tamamlanmış
 *   atamalar (yasal eğitim geçmişi, 23.11.2026) korunur.
 * - Aynı paket zaten atanmış ve bitmemişse atlanır; o paketi daha önce bitirmişse ve bitiş tarihi
 *   hedef dönemden (yıl) önceyse yeni dönem için yeniden atanır (yıllık yenileme).
 * - E-postası olmayana portal kullanıcı kodu, şifresi olmayana geçici şifre üretilir.
 */
class UzaktanEgitimAtayici
{
    /**
     * @param  Collection<int, Calisan>  $calisanlar
     * @return array{atanan: int, silinen_eski: int, kod_uretilen: array<int, string>, giris: array<int, array{ad_soyad: string, eposta: ?string, sifre: ?string, telefon: ?string}>}
     */
    public static function ata(EgitimPaketi $paket, Collection $calisanlar, string $egitimTuru, ?string $sonTarih, int $atayanUserId, ?int $donemYili = null): array
    {
        $donemBasi = now()->setDate($donemYili ?? (int) now()->year, 1, 1)->startOfDay();

        $sonuc = ['atanan' => 0, 'silinen_eski' => 0, 'kod_uretilen' => [], 'giris' => []];

        foreach ($calisanlar as $calisan) {
            if (blank($calisan->eposta)) {
                $calisan->forceFill(['eposta' => static::kullaniciKodu($calisan)])->save();
                $sonuc['kod_uretilen'][] = $calisan->ad_soyad;
            }

            EgitimAtamasi::query()
                ->where('calisan_id', $calisan->id)
                ->where('egitim_paketi_id', '!=', $paket->id)
                ->where('durum', '!=', 'tamamlandi')
                ->get()
                ->each(function (EgitimAtamasi $eski) use (&$sonuc): void {
                    $eski->delete();
                    $sonuc['silinen_eski']++;
                });

            $oncekiler = EgitimAtamasi::query()->where('egitim_paketi_id', $paket->id)->where('calisan_id', $calisan->id)->get();
            $suren = $oncekiler->firstWhere(fn (EgitimAtamasi $a) => $a->durum !== 'tamamlandi');
            $buDonemdeBitmis = $oncekiler->contains(fn (EgitimAtamasi $a) => $a->durum === 'tamamlandi'
                && ($a->tamamlandi_at === null || $a->tamamlandi_at->gte($donemBasi)));
            $atama = $suren ?? ($buDonemdeBitmis ? $oncekiler->first() : new EgitimAtamasi(['egitim_paketi_id' => $paket->id, 'calisan_id' => $calisan->id]));
            if (! $atama->exists) {
                $atama->fill([
                    'donem_yili' => $donemYili,
                    'atayan_user_id' => $atayanUserId,
                    'atandi_at' => now(),
                    'son_tarih' => $sonTarih ?: null,
                    'egitim_turu' => $egitimTuru,
                    'durum' => 'atandi',
                ])->save();
                $sonuc['atanan']++;
                \App\Models\EgitimOlayi::yaz($atama->id, 'atama', 'Eğitim atandı', $paket->ad.' · '.($egitimTuru === 'ilk_defa' ? 'ilk eğitim' : 'tekrar eğitimi'), $calisan->id);
            }

            $gecici = null;
            if (blank($calisan->sifre)) {
                $gecici = static::geciciSifre();
                $calisan->forceFill(['sifre' => $gecici, 'sifre_belirlendi_at' => null])->save();
            }

            $sonuc['giris'][] = ['ad_soyad' => $calisan->ad_soyad, 'eposta' => $calisan->eposta, 'sifre' => $gecici, 'telefon' => $calisan->telefon];
        }

        return $sonuc;
    }

    /** Atama henüz başlamadı mı (ders izlenmemiş, ön test / sınav yok) — işveren yalnız bunları kaldırabilir. */
    public static function baslamadiMi(EgitimAtamasi $atama): bool
    {
        return $atama->on_test_at === null
            && $atama->tamamlandi_at === null
            && ! $atama->ilerlemeler()->exists()
            && ! $atama->sinavSonuclari()->exists()
            && ! $atama->girisler()->exists();
    }

    public static function geciciSifre(): string
    {
        return Str::upper(Str::random(3)).random_int(100, 999);
    }

    /**
     * E-postası olmayan çalışan için portala girebileceği benzersiz kullanıcı kodu (ör. "ahmet.yilmaz482");
     * `eposta` sütununa yazılır — PortalLogin bu alanda e-posta biçimi aramaz.
     */
    public static function kullaniciKodu(Calisan $calisan): string
    {
        $taban = (string) Str::of($calisan->ad_soyad)->ascii()->lower()->replace(' ', '.')->replaceMatches('/[^a-z0-9.]/', '');

        do {
            $kod = $taban.random_int(100, 999);
        } while (Calisan::where('eposta', $kod)->exists());

        return $kod;
    }
}
