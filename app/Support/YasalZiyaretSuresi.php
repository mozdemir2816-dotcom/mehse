<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Ziyaret planlamada yasal asgari süre ve önerilen sıklık (29.10.2026).
 * Süre: Firma::iguAylikDk() — İSG-KATİP sözleşme süresi varsa o, yoksa çalışan sayısı × tehlike sınıfı dakikası
 * (az tehlikeli 10, tehlikeli 20, çok tehlikeli 40 dk; config isg.igu_aylik_dk).
 */
class YasalZiyaretSuresi
{
    /** Tehlike sınıfına göre önerilen ziyaret sıklığı: kaç ayda bir + etiket. */
    public const SIKLIK = [
        'az_tehlikeli' => ['aralik' => 2, 'etiket' => '2 ayda bir'],
        'tehlikeli' => ['aralik' => 1, 'etiket' => 'Ayda bir'],
        'cok_tehlikeli' => ['aralik' => 1, 'etiket' => 'Her ay'],
    ];

    /** Firmanın aylık yasal asgari süresi (dakika). */
    public static function dakika(Firma $firma): int
    {
        return $firma->iguAylikDk();
    }

    /** Süre alanı saat cinsinden tutulur (ZiyaretProgrami.sure_saat); 200 dk → 3.33. */
    public static function saat(int $dakika): float
    {
        return round($dakika / 60, 2);
    }

    /** 200 → "3 sa 20 dk", 480 → "8 sa", 45 → "45 dk", 0 → "0 dk". */
    public static function etiket(int $dakika): string
    {
        $dakika = max(0, $dakika);
        $saat = intdiv($dakika, 60);
        $dk = $dakika % 60;

        return match (true) {
            $saat === 0 => $dk.' dk',
            $dk === 0 => $saat.' sa',
            default => $saat.' sa '.$dk.' dk',
        };
    }

    /** Saat cinsinden kayıtlı süreyi en yakın dakikaya çevirir (3.33 → 200). */
    public static function dakikayaCevir(float|int|string|null $saat): int
    {
        return (int) round(((float) $saat) * 60);
    }

    /** @return array{aralik: int, etiket: string} */
    public static function siklik(?string $tehlikeSinifi): array
    {
        return self::SIKLIK[$tehlikeSinifi] ?? self::SIKLIK['tehlikeli'];
    }

    /** Önerilen sıklığa göre o ay (0–11) ziyaret önerilir mi? Aralık 2 ise Ocak, Mart, Mayıs… */
    public static function ayOnerilirMi(?string $tehlikeSinifi, int $ayIndex): bool
    {
        return $ayIndex % self::siklik($tehlikeSinifi)['aralik'] === 0;
    }
}
