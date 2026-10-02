<?php

namespace App\Support;

use App\Models\KkdStokKarti;
use App\Models\KkdZimmet;
use Illuminate\Support\Facades\DB;

/**
 * KKD stok hareketleri — kartın `mevcut` sayısı ile hareket geçmişini birlikte
 * günceller. Zimmet çıkışı stoğu eksiye düşürebilir (fiili teslim engellenmez,
 * kart "Tükendi" görünür); giriş ve fire miktarı pozitif olmalıdır.
 */
class KkdStok
{
    public static function giris(KkdStokKarti $kart, int $miktar, ?string $aciklama = null, ?string $tarih = null): void
    {
        self::hareket($kart, 'giris', abs($miktar), $aciklama, $tarih);
    }

    public static function fire(KkdStokKarti $kart, int $miktar, ?string $aciklama = null, ?string $tarih = null): void
    {
        self::hareket($kart, 'fire', -abs($miktar), $aciklama, $tarih);
    }

    /** Yeni zimmet stok kartından düşülür. */
    public static function zimmetCikisi(KkdZimmet $z): void
    {
        if ($z->stokKarti) {
            self::hareket($z->stokKarti, 'zimmet', -$z->adet, $z->personel_ad_soyad, $z->teslim_tarihi?->toDateString(), $z->id);
        }
    }

    /** İade edilen (veya hatalı girilip silinen) zimmet stoğa geri eklenir. */
    public static function iade(KkdZimmet $z, ?string $aciklama = null): void
    {
        if ($z->stokKarti) {
            self::hareket($z->stokKarti, 'iade', $z->adet, $aciklama ?? $z->personel_ad_soyad, now()->toDateString(), $z->id);
        }
    }

    private static function hareket(KkdStokKarti $kart, string $tip, int $miktar, ?string $aciklama, ?string $tarih, ?int $zimmetId = null): void
    {
        DB::transaction(function () use ($kart, $tip, $miktar, $aciklama, $tarih, $zimmetId): void {
            $kart->hareketler()->create([
                'tip' => $tip,
                'miktar' => $miktar,
                'tarih' => $tarih ?? now()->toDateString(),
                'aciklama' => $aciklama,
                'kkd_zimmet_id' => $zimmetId,
            ]);
            $kart->increment('mevcut', $miktar);
        });
    }
}
