<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Telefon fotoğrafını PDF için hazırlar (14.11.2026): EXIF yön bilgisine göre
 * döndürür (dompdf EXIF'i yok sayar — dik çekilen fotoğraf yan basılırdı) ve
 * uzun kenarı en fazla 1600 px'e küçültür (PDF boyutu). GD ya da EXIF yoksa ya
 * da bir hata olursa dosyaya dokunmaz.
 */
class FotoYonu
{
    public static function duzelt(string $yol, string $disk = 'public', int $enFazla = 1600): void
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return;
        }

        $tam = Storage::disk($disk)->path($yol);
        $bilgi = @getimagesize($tam);

        if (! $bilgi || ! in_array($bilgi[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return;
        }

        try {
            $yon = $bilgi[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')
                ? (int) (@exif_read_data($tam)['Orientation'] ?? 1)
                : 1;
            $buyuk = max($bilgi[0], $bilgi[1]) > $enFazla;

            if ($yon === 1 && ! $buyuk) {
                return;
            }

            $resim = $bilgi[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($tam) : @imagecreatefrompng($tam);

            if (! $resim) {
                return;
            }

            $resim = match ($yon) {
                3 => imagerotate($resim, 180, 0),
                6 => imagerotate($resim, -90, 0),
                8 => imagerotate($resim, 90, 0),
                default => $resim,
            };

            $g = imagesx($resim);
            $y = imagesy($resim);

            if (max($g, $y) > $enFazla) {
                $oran = $enFazla / max($g, $y);
                $kucuk = imagescale($resim, (int) round($g * $oran), (int) round($y * $oran));
                $resim = $kucuk ?: $resim;
            }

            $bilgi[2] === IMAGETYPE_JPEG ? imagejpeg($resim, $tam, 85) : imagepng($resim, $tam, 6);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
