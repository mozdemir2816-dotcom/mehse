<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Str;

/**
 * Ziyaret Programı için harita tabanlı rota planlayıcı (C18).
 * Bursa sanayi bölgeleri (NOSAB, DOSAB, BOSB, Demirtaş, Kayapa, Hasanağa, Nilüfer vb.)
 * ve Türkiye büyükşehirleri için dahili koordinat sözlüğüyle Google API anahtarı
 * gerekmeden firmaları haritada konumlandırır ve en kısa komşu sırasıyla rota çıkarır.
 */
class HaritaRotaPlanlayici
{
    /** @var array<string, array{0: float, 1: float}> */
    private const KOORDINATLAR = [
        'nosab' => [40.2335, 28.9475],
        'dosab' => [40.2640, 29.0620],
        'demirtas' => [40.2665, 29.0750],
        'bosb' => [40.2190, 28.9650],
        'kayapa' => [40.1755, 28.8180],
        'hasanaga' => [40.1680, 28.7790],
        'nilufer' => [40.2140, 28.9400],
        'osmangazi' => [40.2050, 29.0600],
        'yildirim' => [40.1930, 29.1150],
        'gursu' => [40.2130, 29.1880],
        'kestel' => [40.1980, 29.2110],
        'inegol' => [40.0785, 29.5130],
        'gemlik' => [40.4310, 29.1560],
        'mudanya' => [40.3755, 28.8825],
        'orhangazi' => [40.4890, 29.3090],
        'karacabey' => [40.2140, 28.3610],
        'mustafakemalpasa' => [40.0355, 28.4115],
        'bursa' => [40.1885, 29.0610],
        'istanbul' => [41.0082, 28.9784],
        'ankara' => [39.9334, 32.8597],
        'izmir' => [38.4237, 27.1428],
        'kocaeli' => [40.7654, 29.9408],
        'gebze' => [40.8027, 29.4307],
        'sakarya' => [40.7569, 30.3781],
        'eskisehir' => [39.7767, 30.5206],
        'balikesir' => [39.6484, 27.8826],
        'manisa' => [38.6191, 27.4289],
        'antalya' => [36.8969, 30.7133],
        'konya' => [37.8746, 32.4932],
        'adana' => [37.0000, 35.3213],
        'gaziantep' => [37.0662, 37.3833],
        'kayseri' => [38.7312, 35.4787],
        'tekirdag' => [40.9833, 27.5167],
    ];

    /**
     * Firma adres/ilçe/il metninden yaklaşık koordinat çözer (aynı bölgedeki
     * firmalar haritada üst üste binmesin diye deterministik küçük ofset eklenir).
     *
     * @return array{lat: float, lng: float, bolge: string}
     */
    public static function firmaKoordinati(Firma $firma): array
    {
        $metin = Str::lower(Str::ascii(implode(' ', array_filter([
            $firma->adres,
            $firma->ilce,
            $firma->il,
            $firma->unvan,
        ]))));

        $bulunan = null;
        $bolge = 'Bursa';

        foreach (self::KOORDINATLAR as $anahtar => $k) {
            if (str_contains($metin, $anahtar)) {
                $bulunan = $k;
                $bolge = strtoupper($anahtar);
                break;
            }
        }

        [$lat, $lng] = $bulunan ?? self::KOORDINATLAR['bursa'];
        // Aynı OSB/ilçedeki firmaların iğneleri çakışmasın diye id tabanlı ~300m dağıtma
        $aci = (($firma->id * 137.5) % 360) * M_PI / 180;
        $yaricap = 0.0025 + (($firma->id % 4) * 0.0012);

        return [
            'lat' => round($lat + sin($aci) * $yaricap, 5),
            'lng' => round($lng + cos($aci) * $yaricap, 5),
            'bolge' => $bolge,
        ];
    }

    /**
     * Durakları en yakın komşu (nearest-neighbor) sırasına dizer.
     *
     * @param  array<int, array{firma_id: int, unvan: string, adres: string, lat: float, lng: float, bolge: string, tarih?: ?string, durum?: string}>  $duraklar
     * @return array<int, array<string, mixed>>
     */
    public static function rotayiSirala(array $duraklar): array
    {
        if (count($duraklar) <= 2) {
            return array_values($duraklar);
        }

        $kalan = array_values($duraklar);
        $sirali = [array_shift($kalan)];

        while ($kalan !== []) {
            $son = end($sirali);
            $enYakinIndex = 0;
            $enKisa = PHP_FLOAT_MAX;

            foreach ($kalan as $i => $aday) {
                $mesafe = hypot($son['lat'] - $aday['lat'], $son['lng'] - $aday['lng']);
                if ($mesafe < $enKisa) {
                    $enKisa = $mesafe;
                    $enYakinIndex = $i;
                }
            }

            $sirali[] = $kalan[$enYakinIndex];
            array_splice($kalan, $enYakinIndex, 1);
        }

        return $sirali;
    }

    /**
     * Seçili duraklar için tek tıkla Google Maps çoklu durak rota adresi üretir.
     *
     * @param  array<int, array{lat: float, lng: float, unvan?: string, adres?: string}>  $duraklar
     */
    public static function googleMapsRotaUrl(array $duraklar): ?string
    {
        if ($duraklar === []) {
            return null;
        }

        $noktalar = array_map(
            fn (array $d) => filled($d['adres'] ?? null)
                ? rawurlencode(trim($d['adres']))
                : $d['lat'].','.$d['lng'],
            array_slice(array_values($duraklar), 0, 10),
        );

        return 'https://www.google.com/maps/dir/'.implode('/', $noktalar);
    }
}
