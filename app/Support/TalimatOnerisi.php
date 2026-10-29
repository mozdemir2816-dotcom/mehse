<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Str;

/**
 * Talimat kütüphanesinde firmanın iş ekipmanı envanterine uyan talimatları
 * "önerilen" olarak işaretler (30.10.2026). Eşleştirme: ekipman adındaki anlamlı
 * sözcük (4+ harf, ASCII'ye indirilmiş) talimat başlığında geçiyorsa.
 */
class TalimatOnerisi
{
    /** Eşleşmeyi anlamsızlaştıran genel sözcükler. */
    private const GENEL = ['makine', 'makinesi', 'ekipman', 'ekipmani', 'sistem', 'sistemi', 'tezgahi', 'cihazi', 'cihaz', 'genel', 'aracı', 'araci'];

    /** @return array<int, string> firmanın aktif ekipmanlarından çıkan arama sözcükleri */
    public static function anahtarlar(Firma $firma): array
    {
        return $firma->isEkipmanlari()->where('aktif', true)->pluck('ekipman_adi')
            ->flatMap(fn (string $ad) => preg_split('/[\s\/,\-()]+/u', static::sade($ad)) ?: [])
            ->filter(fn (string $k) => mb_strlen($k) >= 4 && ! in_array($k, self::GENEL, true))
            ->unique()->values()->all();
    }

    /** @param array<int, string> $anahtarlar */
    public static function onerilenMi(string $baslik, array $anahtarlar): bool
    {
        $baslik = static::sade($baslik);

        foreach ($anahtarlar as $k) {
            // "forklift" ↔ "forkliftler" gibi ek almış biçimleri de yakalar
            if (str_contains($baslik, $k) || (mb_strlen($k) >= 6 && str_contains($baslik, mb_substr($k, 0, mb_strlen($k) - 2)))) {
                return true;
            }
        }

        return false;
    }

    private static function sade(string $metin): string
    {
        return Str::of(Str::ascii(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $metin))))->toString();
    }
}
