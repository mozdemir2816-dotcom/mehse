<?php

namespace App\Support;

use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\AtamaYazisi;
use App\Models\Calisan;

/**
 * Atama Yazıları'nda yazılan acil durum ekibi görevlendirmesini Acil Durum Ekipleri
 * kaydına aktarır; Acil Durum Planı ekip listesini oradan okuduğu için plan da
 * güncellenir (kullanıcı 15.10.2026: "atama yazılarından otomatik acil durumdaki
 * listeye eklensin, senkronize gitsin, mükerrer olmasın").
 *
 * Yalnız ekler/günceller, silmez — ekipteki elle eklenmiş üyeler ve belge bilgileri
 * korunur. Aynı kişi çalışan kaydı ya da ad-soyad eşleşmesiyle tanınır, ikinci kez
 * eklenmez.
 */
class AcilEkipAtamaSenkron
{
    /** Atama rolü => Acil Durum Ekipleri türü (config isg.acil_durum.ekip_turleri). */
    public const ROL_EKIP = [
        'sondurme_ekibi' => 'sondurme',
        'kurtarma_ekibi' => 'kurtarma',
        'koruma_ekibi' => 'koruma',
        'ilkyardim_ekibi' => 'ilk_yardim',
    ];

    public static function ilgiliMi(string $rolAnahtari): bool
    {
        return isset(self::ROL_EKIP[$rolAnahtari]);
    }

    /** @return int eklenen yeni üye sayısı */
    public static function aktar(AtamaYazisi $atama): int
    {
        $tur = self::ROL_EKIP[$atama->rol_anahtari] ?? null;

        if (! $tur || ! $atama->firma_id) {
            return 0;
        }

        $ekip = AcilEkip::query()->where('firma_id', $atama->firma_id)->where('tur', $tur)->orderBy('id')->first()
            ?? AcilEkip::create(['firma_id' => $atama->firma_id, 'tur' => $tur, 'ad' => config('isg.acil_durum.ekip_turleri.'.$tur.'.ad')]);

        $mevcut = $ekip->uyeler()->get();
        $calisanlar = Calisan::query()->where('firma_id', $atama->firma_id)->get();
        $eklenen = 0;

        foreach ((array) $atama->uyeler as $u) {
            $ad = trim((string) ($u['ad_soyad'] ?? ''));

            if ($ad === '') {
                continue;
            }

            $calisan = $calisanlar->firstWhere('id', $u['calisan_id'] ?? null)
                ?? $calisanlar->first(fn (Calisan $c) => self::anahtar($c->ad_soyad) === self::anahtar($ad));

            $uye = $mevcut->first(fn (AcilEkipUyesi $m) => ($calisan && $m->calisan_id === $calisan->id) || self::anahtar($m->ad_soyad) === self::anahtar($ad));

            if (! $uye) {
                $uye = $ekip->uyeler()->create([
                    'calisan_id' => $calisan?->id,
                    'ad_soyad' => $calisan?->ad_soyad ?: $ad,
                    'gorev' => $calisan?->gorev ?: ($u['gorev'] ?? null),
                    'telefon' => $calisan?->telefon,
                    'uyelik' => 'asil',
                    'lider' => false,
                ]);
                $mevcut->push($uye);
                $eklenen++;
            } elseif (! $uye->calisan_id && $calisan) {
                $uye->update(['calisan_id' => $calisan->id]);
            }

            // Atama yazısındaki baş üye ekibin lideri olur (tek lider).
            if (! empty($u['bas_uye']) && ! $uye->lider) {
                $ekip->uyeler()->whereKeyNot($uye->id)->where('lider', true)->update(['lider' => false]);
                $uye->update(['lider' => true]);
            }
        }

        return $eklenen;
    }

    /** Büyük/küçük harf ve boşluk farkını yok sayan ad karşılaştırma anahtarı (Türkçe I/İ dahil). */
    private static function anahtar(?string $ad): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $ad))));
    }
}
