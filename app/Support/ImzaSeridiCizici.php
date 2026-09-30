<?php

namespace App\Support;

/**
 * Yıllık Eğitim Planı Excel'inin alt bilgi (footer) görseli: şablondaki imza
 * şeridinin (3190×168 px, üç sütun: başlık + kaşe alanı) firma kaşeleriyle
 * GD üzerinde yeniden çizimi. Kaşe yoksa sütun ıslak imza için boş kalır.
 */
class ImzaSeridiCizici
{
    private const GENISLIK = 3190;

    private const YUKSEKLIK = 168;

    private const BASLIK_YUKSEKLIGI = 38;

    /**
     * @param  array<int, array{baslik: string, kase: ?string}>  $sutunlar
     * @return string oluşturulan geçici PNG yolu
     */
    public static function ciz(array $sutunlar): string
    {
        $img = imagecreatetruecolor(self::GENISLIK, self::YUKSEKLIK);
        $beyaz = imagecolorallocate($img, 255, 255, 255);
        $siyah = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $beyaz);
        imagesetthickness($img, 2);

        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        // Şablondaki sütun genişlik oranları (İGU %27 · Hekim %35 · İşveren %38).
        $oranlar = [0.27, 0.35, 0.38];
        $x = 0;

        imagerectangle($img, 1, 1, self::GENISLIK - 2, self::YUKSEKLIK - 2, $siyah);
        imageline($img, 0, self::BASLIK_YUKSEKLIGI, self::GENISLIK, self::BASLIK_YUKSEKLIGI, $siyah);

        foreach ($sutunlar as $i => $sutun) {
            $genislik = (int) round(self::GENISLIK * ($oranlar[$i] ?? (1 / count($sutunlar))));

            if ($i > 0) {
                imageline($img, $x, 0, $x, self::YUKSEKLIK, $siyah);
            }

            $kutu = imagettfbbox(15, 0, $font, $sutun['baslik']);
            $metinGenislik = $kutu[2] - $kutu[0];
            imagettftext($img, 15, 0, (int) ($x + ($genislik - $metinGenislik) / 2), 26, $siyah, $font, $sutun['baslik']);

            self::kaseYerlestir($img, $sutun['kase'] ?? null, $x, $genislik);

            $x += $genislik;
        }

        $yol = tempnam(sys_get_temp_dir(), 'imz').'.png';
        imagepng($img, $yol);
        imagedestroy($img);

        return $yol;
    }

    /** @param \GdImage $img */
    private static function kaseYerlestir($img, ?string $kase, int $x, int $genislik): void
    {
        $tamYol = $kase ? storage_path('app/public/'.$kase) : null;

        if (! $tamYol || ! is_file($tamYol) || ! ($bilgi = @getimagesize($tamYol))) {
            return;
        }

        $kaynak = @imagecreatefromstring((string) file_get_contents($tamYol));

        if (! $kaynak) {
            return;
        }

        $alanY = self::BASLIK_YUKSEKLIGI + 6;
        $alanH = self::YUKSEKLIK - $alanY - 6;
        $olcek = min($alanH / $bilgi[1], ($genislik * 0.8) / $bilgi[0]);
        $w = (int) ($bilgi[0] * $olcek);
        $h = (int) ($bilgi[1] * $olcek);

        imagecopyresampled($img, $kaynak, (int) ($x + ($genislik - $w) / 2), $alanY, 0, 0, $w, $h, $bilgi[0], $bilgi[1]);
        imagedestroy($kaynak);
    }
}
