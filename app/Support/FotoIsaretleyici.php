<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Uygunsuzluğun fotoğraftaki yerini kırmızı çemberle işaretler (14.11.2026).
 * Gemini saha gözlem tablosundaki "İşaret X / İşaret Y / İşaret Çap" (yüzde)
 * değerleriyle fotoğrafın KOPYASINA çizer; orijinal dokunulmadan kalır.
 * Rapor bu işaretli kopyayı kullanır (ör. "merdiven kenarında korkuluk yok"
 * → merdiven kenarı çember içinde).
 */
class FotoIsaretleyici
{
    /**
     * @param  float  $x  merkez, fotoğraf genişliğinin yüzdesi (0–100, soldan)
     * @param  float  $y  merkez, fotoğraf yüksekliğinin yüzdesi (0–100, yukarıdan)
     * @param  float|null  $cap  çemberin çapı, fotoğraf genişliğinin yüzdesi (boşsa 18)
     * @return string|null işaretli kopyanın yolu (aynı disk), çizilemezse null
     */
    public static function cember(string $yol, float $x, float $y, ?float $cap = null, string $disk = 'public'): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! Storage::disk($disk)->exists($yol)) {
            return null;
        }

        $tam = Storage::disk($disk)->path($yol);
        $bilgi = @getimagesize($tam);
        $resim = match ($bilgi[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tam),
            IMAGETYPE_PNG => @imagecreatefrompng($tam),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tam) : false,
            default => false,
        };

        if (! $resim) {
            return null;
        }

        try {
            $g = imagesx($resim);
            $h = imagesy($resim);
            $cx = (int) round(max(0, min(100, $x)) / 100 * $g);
            $cy = (int) round(max(0, min(100, $y)) / 100 * $h);
            $cap = max(5, min(60, $cap ?: 18));
            $r = (int) round($cap / 100 * $g / 2);
            $kalinlik = max(4, (int) round($g * 0.006));

            imagealphablending($resim, true);
            $kirmizi = imagecolorallocate($resim, 225, 29, 46);
            $beyaz = imagecolorallocatealpha($resim, 255, 255, 255, 30);

            // Beyaz dış çizgi + kalın kırmızı halka: açık ve koyu zeminde okunur.
            for ($i = 0; $i < 2; $i++) {
                imageellipse($resim, $cx, $cy, 2 * ($r + $kalinlik + $i), 2 * ($r + $kalinlik + $i), $beyaz);
            }
            for ($i = 0; $i < $kalinlik; $i++) {
                imageellipse($resim, $cx, $cy, 2 * ($r + $i), 2 * ($r + $i), $kirmizi);
            }

            $hedef = pathinfo($yol, PATHINFO_DIRNAME).'/isaretli-'.Str::random(24).'.jpg';
            ob_start();
            imagejpeg($resim, null, 88);
            Storage::disk($disk)->put($hedef, (string) ob_get_clean());

            return $hedef;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
