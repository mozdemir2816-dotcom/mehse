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
     * @param  int|string|null  $no  çemberin yanına basılan madde numarası — aynı fotoğrafta
     *                               birden çok uygunsuzluk olunca hangisinin hangisi olduğu anlaşılır
     * @return string|null işaretli kopyanın yolu (aynı disk), çizilemezse null
     */
    public static function cember(string $yol, float $x, float $y, ?float $cap = null, string $disk = 'public', int|string|null $no = null): ?string
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

            if ($no !== null && $no !== '') {
                static::numara($resim, (string) $no, $cx, $cy, $r, $kalinlik, $kirmizi);
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

    /**
     * Excel'deki "İşaret X / Y / Çap" hücrelerini okur (virgüllü ondalık ve "%" kabul).
     *
     * @return array{x: float, y: float, cap: float|null}|null
     */
    public static function degerler(mixed $x, mixed $y, mixed $cap = null): ?array
    {
        $sayi = function (mixed $v): ?float {
            $v = str_replace([',', '%', ' '], ['.', '', ''], trim((string) $v));

            return $v !== '' && is_numeric($v) ? (float) $v : null;
        };

        return $sayi($x) !== null && $sayi($y) !== null
            ? ['x' => $sayi($x), 'y' => $sayi($y), 'cap' => $sayi($cap)]
            : null;
    }

    /** Çemberin sol üst çaprazına beyaz kenarlı kırmızı yuvarlak içinde numara; fotoğraftan taşmaz. */
    private static function numara(\GdImage $resim, string $no, int $cx, int $cy, int $r, int $kalinlik, int $kirmizi): void
    {
        $g = imagesx($resim);
        $h = imagesy($resim);
        $yr = max(18, (int) round($g * 0.028));                  // rozet yarıçapı
        $aci = 0.7071;                                          // 45°
        $bx = (int) round($cx - ($r + $kalinlik) * $aci);
        $by = (int) round($cy - ($r + $kalinlik) * $aci);
        $bx = max($yr + 4, min($g - $yr - 4, $bx));
        $by = max($yr + 4, min($h - $yr - 4, $by));

        $beyaz = imagecolorallocate($resim, 255, 255, 255);
        imagefilledellipse($resim, $bx, $by, 2 * $yr + 8, 2 * $yr + 8, $beyaz);
        imagefilledellipse($resim, $bx, $by, 2 * $yr, 2 * $yr, $kirmizi);

        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');

        if (function_exists('imagettftext') && is_file($font)) {
            $boyut = $yr * (strlen($no) > 1 ? 0.8 : 1.0);
            $kutu = imagettfbbox($boyut, 0, $font, $no);
            $tw = $kutu[2] - $kutu[0];
            $th = $kutu[1] - $kutu[7];
            imagettftext($resim, $boyut, 0, (int) round($bx - $tw / 2 - $kutu[0]), (int) round($by + $th / 2), $beyaz, $font, $no);

            return;
        }

        imagestring($resim, 5, $bx - (int) (imagefontwidth(5) * strlen($no) / 2), $by - (int) (imagefontheight(5) / 2), $no, $beyaz);
    }
}
