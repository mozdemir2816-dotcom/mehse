<?php

namespace App\Support;

/**
 * Firmanın NACE kodunu config/risk_nace.php'deki sabit gruplardan birine çevirir.
 * Eşleşme NACE kodunun ilk iki hanesi (bölüm) üzerinden yapılır.
 */
class NaceGrubu
{
    public const ORTAK = 'ortak';

    /** "43.34.01" → "insaat" (bulunamazsa null). */
    public static function bul(?string $naceKodu): ?string
    {
        $bolum = substr(preg_replace('/\D/', '', (string) $naceKodu), 0, 2);
        if (strlen($bolum) < 2) {
            return null;
        }

        foreach (config('risk_nace.gruplar', []) as $anahtar => $grup) {
            if (in_array($bolum, $grup['bolumler'] ?? [], true)) {
                return (string) $anahtar;
            }
        }

        return null;
    }

    /** Toplu ekleme seçeneklerinde gösterilecek sabit grup listesi (anahtar => ad). */
    public static function secenekler(): array
    {
        $secenekler = [self::ORTAK => 'Ortak (her firmada olabilecek ekipman)'];

        foreach (config('risk_nace.gruplar', []) as $anahtar => $grup) {
            $secenekler[$anahtar] = $grup['ad'];
        }

        return $secenekler;
    }
}
