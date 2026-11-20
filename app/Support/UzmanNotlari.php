<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Uzmanın panel genelinde üst bardan hızlıca erişebildiği etiketli ve firma
 * bağlantılı kişisel notları (users.ayarlar['notlarim'] — migration gerektirmez).
 */
class UzmanNotlari
{
    public const ETIKETLER = [
        'genel' => 'Genel',
        'acil' => 'Acil',
        'gorev' => 'Görev',
        'toplanti' => 'Toplantı',
        'hatirlat' => 'Hatırlat',
    ];

    /** @return array<int, array{id: string, metin: string, etiket: string, firma_id: ?int, firma_adi: ?string, tamam: bool, tarih: string}> */
    public static function liste(User $user): array
    {
        $ayar = (array) ($user->ayarlar ?? []);

        return array_values(is_array($ayar['notlarim'] ?? null) ? $ayar['notlarim'] : []);
    }

    /** @return array<int, array<string, mixed>> */
    public static function ekle(User $user, string $metin, string $etiket = 'genel', ?int $firmaId = null): array
    {
        $temiz = trim($metin);
        if ($temiz === '') {
            return self::liste($user);
        }

        $etiket = array_key_exists($etiket, self::ETIKETLER) ? $etiket : 'genel';
        $firmaAdi = null;
        if ($firmaId) {
            $firma = Firma::where('user_id', $user->id)->find($firmaId);
            $firmaId = $firma?->id;
            $firmaAdi = $firma?->unvan;
        }

        $notlar = self::liste($user);
        array_unshift($notlar, [
            'id' => (string) Str::uuid(),
            'metin' => mb_substr($temiz, 0, 500),
            'etiket' => $etiket,
            'firma_id' => $firmaId,
            'firma_adi' => $firmaAdi,
            'tamam' => false,
            'tarih' => now()->format('d.m.Y H:i'),
        ]);

        // En fazla 100 güncel not tutulur
        $notlar = array_slice($notlar, 0, 100);
        KullaniciAyarlari::kaydet($user, ['notlarim' => $notlar]);

        return $notlar;
    }

    /** @return array<int, array<string, mixed>> */
    public static function tamamlaToggle(User $user, string $id): array
    {
        $notlar = array_map(function (array $n) use ($id): array {
            if (($n['id'] ?? '') === $id) {
                $n['tamam'] = ! ($n['tamam'] ?? false);
            }

            return $n;
        }, self::liste($user));

        KullaniciAyarlari::kaydet($user, ['notlarim' => $notlar]);

        return $notlar;
    }

    /** @return array<int, array<string, mixed>> */
    public static function sil(User $user, string $id): array
    {
        $notlar = array_values(array_filter(
            self::liste($user),
            fn (array $n) => ($n['id'] ?? '') !== $id,
        ));

        KullaniciAyarlari::kaydet($user, ['notlarim' => $notlar]);

        return $notlar;
    }
}
