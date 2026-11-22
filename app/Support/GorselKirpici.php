<?php

namespace App\Support;

/**
 * Yüklenen kaşe ve imza görsellerinin etrafındaki boş beyaz / şeffaf kenarları
 * PHP GD ile otomatik kırpar (C22 Kaşe Kütüphanesi). GD eklentisi yoksa veya
 * dosya okunamazsa orijinal dosyaya dokunmaz.
 */
class GorselKirpici
{
    public static function kenarlariKirp(string $dosyaYolu, int $boslukPx = 6): bool
    {
        if (! is_file($dosyaYolu) || ! function_exists('imagecreatefromstring')) {
            return false;
        }

        $ham = @file_get_contents($dosyaYolu);
        if (! is_string($ham) || $ham === '') {
            return false;
        }

        $img = @imagecreatefromstring($ham);
        if (! $img) {
            return false;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 10 || $h < 10) {
            imagedestroy($img);

            return false;
        }

        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;
        $bulundu = false;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // Şeffaf (alpha >= 115) veya neredeyse tamamen beyaz (RGB > 245) pikselleri atla
                if ($alpha >= 115 || ($r > 245 && $g > 245 && $b > 245)) {
                    continue;
                }

                $bulundu = true;
                if ($x < $minX) {
                    $minX = $x;
                }
                if ($y < $minY) {
                    $minY = $y;
                }
                if ($x > $maxX) {
                    $maxX = $x;
                }
                if ($y > $maxY) {
                    $maxY = $y;
                }
            }
        }

        if (! $bulundu) {
            imagedestroy($img);

            return false;
        }

        $x0 = max(0, $minX - $boslukPx);
        $y0 = max(0, $minY - $boslukPx);
        $nw = min($w - $x0, ($maxX - $x0) + 1 + $boslukPx);
        $nh = min($h - $y0, ($maxY - $y0) + 1 + $boslukPx);

        if ($nw >= $w - 4 && $nh >= $h - 4) {
            imagedestroy($img);

            return false;
        }

        $kirpilmis = imagecreatetruecolor($nw, $nh);
        imagealphablending($kirpilmis, false);
        imagesavealpha($kirpilmis, true);
        $seffaf = imagecolorallocatealpha($kirpilmis, 255, 255, 255, 127);
        imagefilledrectangle($kirpilmis, 0, 0, $nw, $nh, $seffaf);
        imagecopy($kirpilmis, $img, 0, 0, $x0, $y0, $nw, $nh);

        $uzanti = strtolower(pathinfo($dosyaYolu, PATHINFO_EXTENSION));
        $basarili = match ($uzanti) {
            'jpg', 'jpeg' => imagejpeg($kirpilmis, $dosyaYolu, 92),
            default => imagepng($kirpilmis, $dosyaYolu, 6),
        };

        imagedestroy($img);
        imagedestroy($kirpilmis);

        return (bool) $basarili;
    }
}
