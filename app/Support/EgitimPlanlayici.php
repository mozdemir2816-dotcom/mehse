<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;

/**
 * Kurul / acil ekip / çalışan temsilcisi üyeleri atanınca ya da değişince, eğitimi olmayan üyeler için Eğitim Katılım
 * modülünde otomatik PLANLI kayıt açılır (kullanıcı 03.11.2026: atamalar ve eğitimler doğrudan kendi modülünde olsun).
 *
 *  - Planlı kayıt: katılımcıların hepsi `katildi = false` → belge/sertifika almaz, eğitimi alınmış SAYILMAZ (belgeAlacakKatilimcilar).
 *    Eğitim yapılınca mevcut "Katılım / Sonuç" (Eğitim Yenileme Takibi > Yüz Yüze Kayıtlar) ile `katildi = true` işaretlenir.
 *  - Aynı kaynak için açık (bekleyeni olan) planlı kayıt varsa yenisi açılmaz: yeni eksik üyeler eklenir, artık eksik olmayan
 *    (gruptan çıkan ya da eğitimi başka kayıtta alınan) bekleyen üyeler çıkarılır; sonuçlanmış katılımcılara dokunulmaz.
 *  - Kaynak anahtarı konu_secimleri['planli_kaynak'] içinde tutulur (yeni sütun gerekmez): kurul | acil_<tür> | temsilci.
 */
class EgitimPlanlayici
{
    /** Bu kaynak için açık planlı kayıt: otomatik açılmış ve hâlâ sonuçlanmamış (katildi=false) katılımcısı olan son kayıt. */
    public static function acikKayit(Firma $firma, string $baslik, string $kaynak): ?EgitimKatilim
    {
        return EgitimKatilim::query()->where('firma_id', $firma->id)->where('baslik_anahtari', $baslik)
            ->latest('id')->limit(30)->get()
            ->first(fn (EgitimKatilim $k) => ($k->konu_secimleri['planli_kaynak'] ?? null) === $kaynak && self::bekleyenler($k)->isNotEmpty());
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> sonucu girilmemiş (katıldı=false) katılımcılar */
    private static function bekleyenler(EgitimKatilim $k): \Illuminate\Support\Collection
    {
        return collect($k->katilimcilar ?? [])->filter(fn ($x) => is_array($x) && array_key_exists('katildi', $x) && $x['katildi'] === false);
    }

    /**
     * Eğitimi eksik üyeleri Eğitim Katılım'da planlar.
     *
     * @param  array<int, array{ad_soyad: string, tc?: ?string, gorev?: ?string}>  $eksik
     * @return EgitimKatilim|null güncel açık planlı kayıt (eksik yoksa ve kayıt boşaldıysa null)
     */
    public static function planla(Firma $firma, string $baslik, array $eksik, string $kaynak): ?EgitimKatilim
    {
        $anahtar = fn (?string $ad) => EgitimTakibi::adAnahtari($ad);
        $eksik = collect($eksik)->filter(fn ($e) => filled($e['ad_soyad'] ?? null))->unique(fn ($e) => $anahtar($e['ad_soyad']))->values();
        $eksikAnahtarlar = $eksik->map(fn ($e) => $anahtar($e['ad_soyad']))->flip();
        $acik = self::acikKayit($firma, $baslik, $kaynak);

        if ($acik) {
            // Sonuçlanmışlar korunur; bekleyenlerden artık eksik olmayanlar çıkar.
            $liste = collect($acik->katilimcilar)->reject(fn ($x) => ($x['katildi'] ?? true) === false && ! $eksikAnahtarlar->has($anahtar($x['ad_soyad'] ?? null)))->values();
            $mevcut = $liste->map(fn ($x) => $anahtar($x['ad_soyad'] ?? null))->flip();

            foreach ($eksik as $e) {
                if (! $mevcut->has($anahtar($e['ad_soyad']))) {
                    $liste->push(self::katilimci($e));
                }
            }

            if ($liste->isEmpty()) {
                $acik->delete();   // kimse kalmadı (grup boşaldı): boş planlı kayıt tutulmaz

                return null;
            }

            if ($liste->all() != $acik->katilimcilar) {
                $acik->update(['katilimcilar' => $liste->all()]);
            }

            return self::bekleyenler($acik->refresh())->isNotEmpty() ? $acik : null;
        }

        if ($eksik->isEmpty()) {
            return null;
        }

        $icerik = EgitimIcerikOlusturucu::olustur($baslik, null, $firma->tehlike_sinifi ?: 'az_tehlikeli', 'ilk');
        $icerik['planli_kaynak'] = $kaynak;
        $igu = $firma->igu;

        return EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => $baslik,
            'egitim_turu' => 'ilk',
            'belge_tarihi' => today(),
            'sure_gun' => EgitimIcerikOlusturucu::planlananGun($icerik),
            'isg_uzmani_var' => true,
            'isg_uzmani_adi' => $igu?->ad_soyad,
            'isg_uzmani_kase' => $igu?->kase_gorseli,
            'isyeri_hekimi_var' => false,
            'konu_secimleri' => $icerik,
            'katilimcilar' => $eksik->map(fn ($e) => self::katilimci($e))->all(),
        ]);
    }

    /**
     * Gerçek bir eğitim katılım formu kaydedildiğinde: aynı firma + başlıktaki açık planlı kayıtlardan, eğitimi artık başka bir
     * kayıtta (belge alacak olarak) bulunan bekleyen katılımcıları çıkarır; kimse kalmayan planlı kayıt silinir → aynı eğitim için
     * mükerrer (planlı + gerçek) kayıt / evrak kalmaz.
     *
     * @return int silinen / güncellenen planlı kayıt sayısı
     */
    public static function uzlastir(Firma $firma, string $baslik): int
    {
        $kayitlar = EgitimKatilim::query()->where('firma_id', $firma->id)->where('baslik_anahtari', $baslik)->latest('id')->limit(60)->get();
        $planlilar = $kayitlar->filter(fn (EgitimKatilim $k) => isset($k->konu_secimleri['planli_kaynak']) && self::bekleyenler($k)->isNotEmpty());
        $sayi = 0;

        foreach ($planlilar as $planli) {
            // Eğitimi alanlar: BU planlı kayıt dışındaki kayıtlarda katılımı onaylı olanlar.
            $alanlar = $kayitlar->reject(fn (EgitimKatilim $k) => $k->id === $planli->id)
                ->flatMap(fn (EgitimKatilim $k) => collect($k->belgeAlacakKatilimcilar())->pluck('ad_soyad'))
                ->map(fn ($ad) => EgitimTakibi::adAnahtari($ad))->filter()->flip();

            $liste = collect($planli->katilimcilar)->reject(fn ($x) => ($x['katildi'] ?? true) === false && $alanlar->has(EgitimTakibi::adAnahtari($x['ad_soyad'] ?? null)))->values();

            if ($liste->count() === count($planli->katilimcilar)) {
                continue;
            }

            $liste->isEmpty() ? $planli->delete() : $planli->update(['katilimcilar' => $liste->all()]);
            $sayi++;
        }

        return $sayi;
    }

    /** @param  array{ad_soyad: string, tc?: ?string, gorev?: ?string}  $e */
    private static function katilimci(array $e): array
    {
        return ['ad_soyad' => $e['ad_soyad'], 'tc' => $e['tc'] ?? null, 'gorev' => $e['gorev'] ?? null, 'katildi' => false];
    }
}
