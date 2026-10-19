<?php

namespace App\Support;

/**
 * Saha Denetimi "Sektöre Özgü Kontrol" bölümü — "İSG Saha Gözlem – Kurul Tespit ve Öneri Defteri
 * (Master)" dosyasındaki 40 sektör × 40 madde. Veri resources/data/saha-sektor-defteri.json'dadır
 * (config'e konmaz: büyük dosya her istekte yüklenmesin). Her madde: ifade (gözlem sorusu),
 * dayanak (mevzuat), oneri (düzeltici faaliyet).
 */
class SahaSektorDefteri
{
    /** Denetim maddelerinde bu önekle kodlanır: SD1, SD2 … (nokta yok → Livewire dizi anahtarı güvenli). */
    public const KOD_ONEKI = 'SD';

    /** @var array<int, array{anahtar: string, ad: string, ikon: string, maddeler: array<int, array{ifade: string, dayanak: string, oneri: string}>}>|null */
    private static ?array $veri = null;

    /** @return array<int, array{anahtar: string, ad: string, ikon: string, maddeler: array<int, array<string, string>>}> */
    public static function tumu(): array
    {
        if (self::$veri === null) {
            $yol = resource_path('data/saha-sektor-defteri.json');
            self::$veri = is_file($yol) ? (array) json_decode((string) file_get_contents($yol), true) : [];
        }

        return self::$veri;
    }

    /** @return array<string, string> anahtar => "🏗️ İnşaat" (açılır liste için) */
    public static function sektorler(): array
    {
        return collect(self::tumu())->mapWithKeys(fn (array $s) => [$s['anahtar'] => trim(($s['ikon'] ?? '').' '.$s['ad'])])->all();
    }

    public static function sektorAdi(?string $anahtar): ?string
    {
        return collect(self::tumu())->firstWhere('anahtar', $anahtar)['ad'] ?? null;
    }

    /**
     * Saha Denetimi madde yapısında (kod, ifade, kritik, uygulanamaz_izni + dayanak, oneri) liste.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function maddeler(?string $anahtar): array
    {
        $sektor = collect(self::tumu())->firstWhere('anahtar', $anahtar);

        if (! $sektor) {
            return [];
        }

        return collect($sektor['maddeler'])->map(fn (array $m, int $i) => [
            'kod' => self::KOD_ONEKI.($i + 1),
            'ifade' => $m['ifade'],
            'kritik' => false,
            'uygulanamaz_izni' => true,
            'dayanak' => $m['dayanak'],
            'oneri' => $m['oneri'],
        ])->all();
    }

    /** @return array{ad: string, maddeler: array<int, array<string, mixed>>}|null */
    public static function kategori(?string $anahtar): ?array
    {
        $ad = self::sektorAdi($anahtar);

        return $ad ? ['ad' => 'Sektöre Özgü Kontrol — '.$ad, 'maddeler' => self::maddeler($anahtar)] : null;
    }
}
