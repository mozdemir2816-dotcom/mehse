<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;

/**
 * Eğitim katılım formunun FİRMAYA özgü içeriği (işyerine özgü / 4. eğitim konuları). Boş form çıktılarında
 * (Evrakları İndir, Boş Form) sektörün genel şablonu yerine firmanın GÜNCEL konuları basılsın diye:
 * sektör şablonunun konularına firmanın kendi konuları (yıllık plan 4. bölümü, son kayıtlı form, iş kalemleri, kütüphane) EKLENİR.
 */
class EgitimFirmaIcerigi
{
    /** Yıllık planın 4. bölümünden kurulan işyerine özgü bölüm; plan yok/boşsa null. */
    public static function planOzguBolumu(Firma $firma, string $egitimTuru, ?int $yil = null): ?array
    {
        $yil ??= (int) now()->year;
        $konular = collect(YillikPlan::query()->where('firma_id', $firma->id)->where('yil', $yil)->value('egitimler') ?? [])
            ->where('kategori', 'ise_ozgu')->pluck('konu')->filter()->unique()->values();

        if ($konular->isEmpty()) {
            return null;
        }

        $sinif = $firma->tehlike_sinifi ?: 'az_tehlikeli';
        // 4. bölüm fiili dk: Az Tehlikeli 2s (90 dk), Tehlikeli 4s (180 dk), Çok Tehlikeli 4s (180 dk).
        $blokDk = in_array($sinif, ['tehlikeli', 'cok_tehlikeli'], true) ? 180 : 90;
        $maddeler = $konular->map(fn (string $k) => ['madde' => $k, 'dakika' => 15, 'dahil' => true])->all();

        return [
            'sektor' => 'Firmaya özgü ('.$yil.' Yıllık Eğitim Planı)',
            'maddeler' => EgitimIcerikOlusturucu::bolumDakikalariniDagit($maddeler, $blokDk),
        ];
    }

    /**
     * Boş form için içerik: sektör şablonunun üzerine firmanın kendi güncel konularını (yıllık plan 4. bölümü,
     * önceki kayıt, iş kalemleri, kütüphane) ekler — şablon konuları korunur, plan konuları eksiksiz dahil olur.
     *
     * @return array<string, mixed>
     */
    public static function bosForm(Firma $firma, string $baslikAnahtari, ?string $sektorAnahtari, string $egitimTuru): array
    {
        $icerik = EgitimIcerikOlusturucu::olustur($baslikAnahtari, $sektorAnahtari, $firma->tehlike_sinifi ?: 'az_tehlikeli', $egitimTuru);

        return self::firmaKonulariniEkle($icerik, $firma, $baslikAnahtari, $sektorAnahtari, $egitimTuru);
    }

    /**
     * Verilen içeriğin (şablon ya da ekrandaki form) işe özgü bölümüne firmanın konularını EKLER; mevcut konulara dokunmaz.
     *
     * @param  array<string, mixed>  $icerik
     * @return array<string, mixed>
     */
    public static function firmaKonulariniEkle(array $icerik, Firma $firma, string $baslikAnahtari, ?string $sektorAnahtari, string $egitimTuru): array
    {
        if ($baslikAnahtari !== 'genel' || ($icerik['tip'] ?? null) !== 'genel' || ! isset($icerik['isyerine_ozgu']['maddeler'])) {
            return $icerik;
        }

        $firmaKonulari = collect(self::planOzguBolumu($firma, $egitimTuru)['maddeler'] ?? [])->pluck('madde');

        $son = $firma->egitimKatilimlari()
            ->where('baslik_anahtari', 'genel')
            ->where('sektor_anahtari', $sektorAnahtari)
            ->where('egitim_turu', $egitimTuru)
            ->where(fn ($q) => $q->whereNull('is_kolu')->orWhere('is_kolu', ''))
            ->latest('belge_tarihi')->latest()->first();

        $firmaKonulari = $firmaKonulari
            ->merge(collect($son?->konu_secimleri['isyerine_ozgu']['maddeler'] ?? [])->where('dahil', true)->pluck('madde'))
            ->merge($firma->isKalemiEgitimKonulari())
            ->merge(IseOzguEgitimKutuphanesi::firmaIcin($firma)->pluck('ad'))
            ->map(fn ($k) => trim((string) $k))->filter();

        $mevcut = collect($icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->map(fn ($m) => mb_strtolower(trim((string) $m)))->all();

        $eklendi = false;
        foreach ($firmaKonulari as $konu) {
            if (! in_array(mb_strtolower($konu), $mevcut, true)) {
                $icerik['isyerine_ozgu']['maddeler'][] = ['madde' => $konu, 'dakika' => 10, 'dahil' => true];
                $mevcut[] = mb_strtolower($konu);
                $eklendi = true;
            }
        }

        if ($eklendi) {
            $sinif = $firma->tehlike_sinifi ?: 'az_tehlikeli';
            $blokDk = in_array($sinif, ['tehlikeli', 'cok_tehlikeli'], true) ? 180 : 90;
            $icerik['isyerine_ozgu']['maddeler'] = EgitimIcerikOlusturucu::bolumDakikalariniDagit(
                $icerik['isyerine_ozgu']['maddeler'],
                $blokDk,
            );
        }

        return $icerik;
    }
}
