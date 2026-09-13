<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Yetkilendirilebilir sayfa/resource kataloğu — hem SinirliErisim trait'i (varsayılan
 * anahtar üretimi) hem de KullaniciYonetimi ekranı (izin verilecek liste) hem de migration
 * seed'i (mevcut kullanıcılara tüm sayfaları tanımlama) AYNI kaynaktan okusun diye.
 */
class SayfaKatalogu
{
    /** @return array<string,string> anahtar => görünen ad */
    public static function tumSayfalar(): array
    {
        $liste = [];

        foreach (File::files(app_path('Filament/Pages')) as $dosya) {
            $sinifAdi = $dosya->getBasename('.php');
            $liste[static::anahtar($sinifAdi)] = static::etiket($sinifAdi);
        }

        foreach (File::directories(app_path('Filament/Resources')) as $klasor) {
            foreach (File::glob($klasor.'/*Resource.php') as $dosyaYolu) {
                $sinifAdi = pathinfo($dosyaYolu, PATHINFO_FILENAME);
                $liste[static::anahtar($sinifAdi)] = static::etiket($sinifAdi);
            }
        }

        ksort($liste);

        return $liste;
    }

    public static function anahtar(string $sinifAdi): string
    {
        return Str::kebab($sinifAdi);
    }

    protected static function etiket(string $sinifAdi): string
    {
        $temiz = preg_replace('/Resource$/', '', $sinifAdi);

        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $temiz));
    }
}
