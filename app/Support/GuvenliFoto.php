<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Saha, DÖF, olay ve tutanak fotoğraf yüklemeleri için merkezi sunucu doğrulaması.
 * Yalnız gerçek görsel MIME türüne ve güvenli uzantıya (JPG/PNG/WEBP, ≤ 10 MB) izin verir.
 */
final class GuvenliFoto
{
    public const UZANTILAR = ['jpg', 'jpeg', 'png', 'webp'];

    public const MAX_BAYT = 10 * 1024 * 1024;

    public static function gecerliMi(mixed $dosya): bool
    {
        if (! $dosya instanceof UploadedFile || ! $dosya->isValid()) {
            return false;
        }

        $uzanti = strtolower($dosya->getClientOriginalExtension());
        $mime = strtolower((string) $dosya->getMimeType());

        return in_array($uzanti, self::UZANTILAR, true)
            && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            && $dosya->getSize() <= self::MAX_BAYT;
    }

    public static function yukle(mixed $dosya, string $klasor, string $disk = 'public'): ?string
    {
        if (! self::gecerliMi($dosya)) {
            return null;
        }

        /** @var UploadedFile $dosya */
        return $dosya->store($klasor, $disk);
    }
}
