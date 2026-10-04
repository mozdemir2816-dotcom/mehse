<?php

namespace App\Support;

use App\Models\ArsivDosya;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Arşive dosya yerleştirme — Arşiv sayfası (Yeni Kayıt / Dosyaya ekle /
 * Düzelt) ile telefondaki tek dokunuşla yükleme (Ziyaret Modu, Arşiv
 * kontrol listesi) aynı kuralları kullanır:
 * birden çok görsel (çok sayfalı belgenin fotoğrafları) sırasıyla tek PDF'te
 * birleşir; görsel dışı karışık çoklu seçim tek ZIP olur; tek dosya özgün
 * adıyla taşınır.
 */
final class ArsivYukleyici
{
    public const GORSEL_UZANTILARI = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @param  array<int, string>  $yollar  public diskteki geçici yollar
     * @param  array<string, ?string>  $adlar  yol → özgün dosya adı
     * @return array{dosya_adi: string, dosya_yolu: string, boyut: int}|null
     */
    public static function birlestir(array $yollar, Firma $firma, string $baslik, array $adlar = []): ?array
    {
        $disk = Storage::disk('public');
        $yollar = array_values(array_filter($yollar, fn ($y) => is_string($y) && $disk->exists($y)));

        if (! $yollar) {
            return null;
        }

        $klasor = 'arsiv/'.$firma->id.'/'.now()->format('Y');
        $ad = Str::slug($baslik ?: 'belge') ?: 'belge';
        $gorselMi = fn (string $y) => in_array(strtolower(pathinfo($y, PATHINFO_EXTENSION)), self::GORSEL_UZANTILARI, true);

        if (count($yollar) > 1 && collect($yollar)->every($gorselMi)) {
            $icerik = Pdf::loadView('pdf.arsiv-gorseller', [
                'baslik' => $baslik,
                'gorseller' => array_map(fn ($y) => $disk->path($y), $yollar),
            ])->setPaper('a4')->output();
            $ad .= '.pdf';
        } elseif (count($yollar) > 1) {
            $zipYolu = tempnam(sys_get_temp_dir(), 'arz').'.zip';
            $zip = new ZipArchive;
            $zip->open($zipYolu, ZipArchive::CREATE);
            foreach ($yollar as $i => $y) {
                $zip->addFile($disk->path($y), ($i + 1).'-'.($adlar[$y] ?? basename($y)));
            }
            $zip->close();
            $icerik = (string) file_get_contents($zipYolu);
            @unlink($zipYolu);
            $ad .= '.zip';
        } else {
            $ozgun = (string) (($adlar[$yollar[0]] ?? null) ?: basename($yollar[0]));
            $yol = $klasor.'/'.Str::random(8).'-'.(Str::slug(pathinfo($ozgun, PATHINFO_FILENAME)) ?: 'belge').'.'.strtolower(pathinfo($yollar[0], PATHINFO_EXTENSION));
            $disk->move($yollar[0], $yol);

            return ['dosya_adi' => $ozgun, 'dosya_yolu' => $yol, 'boyut' => $disk->size($yol)];
        }

        $yol = $klasor.'/'.Str::random(8).'-'.$ad;
        $disk->put($yol, $icerik);
        $disk->delete($yollar);

        return ['dosya_adi' => $ad, 'dosya_yolu' => $yol, 'boyut' => strlen($icerik)];
    }

    /** Kategoriye göre varsayılan başlık: "2026 Çalışma Planı", "İş Güvenliği Sözleşmesi — Ayşe Uzman". */
    public static function varsayilanBaslik(string $kategori, ?int $yil = null, ?string $kisi = null): string
    {
        $k = ArsivKurali::kategori($kategori);

        return $k['kural'] === 'suresiz' && filled($kisi)
            ? $k['ad'].' — '.$kisi
            : trim(($yil ? $yil.' ' : '').ArsivKurali::kisaAd($k));
    }

    /**
     * Telefondan tek dokunuşla yükleme: çekilen sayfalar (ya da seçilen dosya)
     * kategori varsayılanlarıyla doğrudan arşive girer — yıl beklenen dönem,
     * belge tarihi bugün; gerekirse sonradan "Düzelt" ile değiştirilir.
     *
     * @param  array<int, string>  $yollar
     * @param  array<string, ?string>  $adlar
     */
    public static function hizliKaydet(Firma $firma, string $kategori, array $yollar, array $adlar = []): ?ArsivDosya
    {
        $k = ArsivKurali::kategori($kategori);
        $yil = ArsivKurali::beklenenYil($k['anahtar']);
        $baslik = self::varsayilanBaslik($k['anahtar'], $yil);
        $dosya = self::birlestir($yollar, $firma, $baslik, $adlar);

        if (! $dosya) {
            return null;
        }

        return ArsivDosya::create($dosya + [
            'firma_id' => $firma->id,
            'kategori' => $k['anahtar'],
            'baslik' => $baslik,
            'yil' => $yil,
            'baslangic_tarihi' => Carbon::today()->toDateString(),
            'aktif' => true,
            'asama' => ArsivDosya::DOSYADA,
            'kaynak' => 'hizli',
        ]);
    }
}
