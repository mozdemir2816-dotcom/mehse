<?php

namespace App\Support;

use App\Models\KontrolKarti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kontrol Kartı PDF'i (A4 dikey, dompdf): künye, numaralı işaretli fotoğraf +
 * kontrol listesi, uygunsuz maddeler için Fine-Kinney önce/sonra tabloları,
 * her sayfada Hazırlayan / Onaylayan şeridi.
 */
class KontrolKartiUretici
{
    /** PDF'te fotoğrafın genişliği (px, dompdf 96 dpi). */
    public const FOTO_GENISLIK = 300;

    /** Fotoğraf durum renkleri (Pano) — PDF şablonundaki $renk ile aynı. */
    private const RENKLER = ['uygun' => [0, 112, 74], 'uygunsuz' => [179, 18, 46], 'kapsam_disi' => [107, 114, 128], '' => [0, 87, 168]];

    public static function pdf(KontrolKarti $k, bool $imzali = true): StreamedResponse
    {
        $k->loadMissing('firma.igu', 'firma.user');

        // İşaretler fotoğrafın üzerine GD ile çizilir: dompdf tablo içindeki
        // position:absolute öğeleri yanlış yere koyuyordu (14.11.2026 önizleme).
        $foto = static::foto($k->foto_yolu);
        $gecici = $foto ? static::isaretliFoto($foto['yol'], $k->maddeler ?? []) : null;

        if ($gecici) {
            $foto['yol'] = $gecici;
        }

        try {
            $pdf = Pdf::loadView('pdf.kontrol-karti', [
                'kart' => $k,
                'firma' => $k->firma,
                'imzali' => $imzali,
                'foto' => $foto,
            ])->setPaper('a4');

            $pdf->render();
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->getCanvas();
            $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
            $canvas->page_script(function (int $sayfa, int $toplam) use ($canvas, $font): void {
                $canvas->text($canvas->get_width() - 70, $canvas->get_height() - 18, "Sayfa {$sayfa}/{$toplam}", $font, 7, [0.39, 0.45, 0.55]);
            });
            $icerik = $pdf->output();
        } finally {
            if ($gecici) {
                @unlink($gecici);
            }
        }

        $ad = 'kontrol-karti-'.Str::slug($k->firma?->unvan ?? 'firma').'-'.Str::slug($k->belge_no ?? '').'.pdf';

        return response()->streamDownload(fn () => print ($icerik), $ad);
    }

    /**
     * Numaralı işaretleri fotoğrafın kopyasına çizer; geçici JPEG yolunu döndürür
     * (GD yoksa ya da işaret yoksa null — PDF işaretsiz fotoğrafı basar).
     * Şekil + renk durumu birlikte anlatır: uygunsuz daire, diğerleri köşeli kare.
     */
    public static function isaretliFoto(string $yol, array $maddeler): ?string
    {
        $isaretli = array_filter($maddeler, fn ($m) => ! empty($m['isaret']));

        if (! $isaretli || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $bilgi = @getimagesize($yol);
        $kaynak = match ($bilgi[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($yol),
            IMAGETYPE_PNG => @imagecreatefrompng($yol),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($yol) : false,
            default => false,
        };

        if (! $kaynak) {
            return null;
        }

        // Baskı için 1000 px genişlik yeterli ve işaret boyutu sabit kalır.
        $g = 1000;
        $y = (int) round($g * imagesy($kaynak) / imagesx($kaynak));
        $resim = imagecreatetruecolor($g, $y);
        imagecopyresampled($resim, $kaynak, 0, 0, 0, 0, $g, $y, imagesx($kaynak), imagesy($kaynak));
        imageantialias($resim, true);

        $r = 26;   // işaret yarıçapı (px)
        $beyaz = imagecolorallocate($resim, 255, 255, 255);
        $golge = imagecolorallocatealpha($resim, 0, 0, 0, 80);
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $ttf = function_exists('imagettftext') && is_file($font);

        foreach ($maddeler as $i => $m) {
            if (empty($m['isaret'])) {
                continue;
            }

            $cx = (int) round($m['isaret']['x'] / 100 * $g);
            $cy = (int) round($m['isaret']['y'] / 100 * $y);
            [$kr, $kg, $kb] = self::RENKLER[$m['durum'] ?? ''] ?? self::RENKLER[''];
            $renk = imagecolorallocate($resim, $kr, $kg, $kb);

            if (($m['durum'] ?? '') === KontrolKarti::UYGUNSUZ || blank($m['durum'] ?? null)) {
                imagefilledellipse($resim, $cx + 2, $cy + 3, 2 * $r + 6, 2 * $r + 6, $golge);
                imagefilledellipse($resim, $cx, $cy, 2 * $r + 6, 2 * $r + 6, $beyaz);
                imagefilledellipse($resim, $cx, $cy, 2 * $r, 2 * $r, $renk);
            } else {
                imagefilledrectangle($resim, $cx - $r - 1, $cy - $r + 1, $cx + $r + 5, $cy + $r + 5, $golge);
                imagefilledrectangle($resim, $cx - $r - 3, $cy - $r - 3, $cx + $r + 3, $cy + $r + 3, $beyaz);
                imagefilledrectangle($resim, $cx - $r, $cy - $r, $cx + $r, $cy + $r, $renk);
            }

            $no = (string) ($i + 1);

            if ($ttf) {
                $boyut = 22;
                $kutu = imagettfbbox($boyut, 0, $font, $no);
                $tw = $kutu[2] - $kutu[0];
                $th = $kutu[1] - $kutu[7];
                imagettftext($resim, $boyut, 0, (int) round($cx - $tw / 2 - $kutu[0]), (int) round($cy + $th / 2), $beyaz, $font, $no);
            } else {
                imagestring($resim, 5, $cx - (int) (imagefontwidth(5) * strlen($no) / 2), $cy - (int) (imagefontheight(5) / 2), $no, $beyaz);
            }
        }

        // dompdf yalnız proje kökü altındaki dosyaları okur (chroot) — sistem temp klasörü olmaz.
        $klasor = storage_path('app/gecici-pdf');
        is_dir($klasor) || @mkdir($klasor, 0775, true);
        $hedef = $klasor.'/kk-'.Str::random(20).'.jpg';
        imagejpeg($resim, $hedef, 85);

        return $hedef;
    }

    /**
     * Fotoğrafın dosya yolu ve PDF'teki boyutu (işaretler yüzdeyle konumlanır).
     *
     * @return array{yol: string, genislik: int, yukseklik: int}|null
     */
    public static function foto(?string $yol): ?array
    {
        $tam = $yol ? storage_path('app/public/'.$yol) : null;
        $boyut = $tam && is_file($tam) ? @getimagesize($tam) : null;

        if (! $boyut || ! $boyut[0]) {
            return null;
        }

        $genislik = static::FOTO_GENISLIK;
        $yukseklik = (int) round($genislik * $boyut[1] / $boyut[0]);

        // Çok uzun (dik) fotoğraf sayfayı taşırmasın: en fazla 420 px yükseklik.
        if ($yukseklik > 420) {
            $genislik = (int) round($genislik * 420 / $yukseklik);
            $yukseklik = 420;
        }

        return ['yol' => $tam, 'genislik' => $genislik, 'yukseklik' => $yukseklik];
    }
}
