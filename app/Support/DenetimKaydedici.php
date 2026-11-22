<?php

namespace App\Support;

use App\Models\DenetimKaydi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Merkezi Denetim Kayıtları (Audit Log — F7).
 * Yalnız oturum açık işlemlerde kayıt alır; hata durumunda ana akışı asla kesmez.
 */
class DenetimKaydedici
{
    private static ?bool $tabloVar = null;

    public static function kaydet(
        string $modul,
        string $islem,
        string $ozet,
        ?int $firmaId = null,
        ?string $kayitTipi = null,
        ?int $kayitId = null,
        ?int $userId = null,
        ?string $kullaniciAdi = null,
    ): void {
        try {
            if (self::$tabloVar === null) {
                self::$tabloVar = Schema::hasTable('denetim_kayitlari');
            }
            if (! self::$tabloVar) {
                return;
            }

            $user = Auth::user();
            $uid = $userId ?? ($user?->getAuthIdentifier() ? (int) $user->getAuthIdentifier() : null);
            $ad = $kullaniciAdi ?? ($user?->name ?? $user?->ad_soyad ?? null);

            if ($uid === null && $kullaniciAdi === null) {
                return;
            }

            DenetimKaydi::query()->create([
                'user_id' => $uid,
                'kullanici_adi' => $ad ? Str::limit((string) $ad, 120, '') : null,
                'firma_id' => $firmaId,
                'modul' => Str::limit($modul, 60, ''),
                'islem' => Str::limit($islem, 40, ''),
                'kayit_tipi' => $kayitTipi ? Str::limit($kayitTipi, 80, '') : null,
                'kayit_id' => $kayitId,
                'ozet' => Str::limit($ozet, 255),
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Audit log hatası ana işlemi asla durdurmaz.
        }
    }

    /**
     * Son 7 gün günlük işlem hacmi (Guvenlik sayfasındaki çubuk grafik için).
     *
     * @return array<int, array{tarih: string, etiket: string, sayi: int}>
     */
    public static function son7GunHacim(?int $userId = null): array
    {
        try {
            if (! Schema::hasTable('denetim_kayitlari')) {
                return [];
            }

            $bas = Carbon::today()->subDays(6);
            $q = DenetimKaydi::query()->where('created_at', '>=', $bas->copy()->startOfDay());
            if ($userId !== null) {
                $q->where('user_id', $userId);
            }

            $sayim = $q->get(['created_at'])
                ->groupBy(fn (DenetimKaydi $k) => $k->created_at?->toDateString() ?? '')
                ->map->count();

            $sonuc = [];
            for ($i = 0; $i < 7; $i++) {
                $gun = $bas->copy()->addDays($i);
                $k = $gun->toDateString();
                $sonuc[] = [
                    'tarih' => $k,
                    'etiket' => $gun->format('d.m'),
                    'sayi' => (int) ($sayim[$k] ?? 0),
                ];
            }

            return $sonuc;
        } catch (Throwable) {
            return [];
        }
    }

    public static function onbellekSifirla(): void
    {
        self::$tabloVar = null;
    }
}
