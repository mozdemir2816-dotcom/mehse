<?php

namespace App\Support;

/** wa.me bağlantıları — serbest yazılmış Türk cep numarasından hazır mesajlı bağlantı. */
class WhatsApp
{
    /**
     * 0532 111 22 33 / +90 532... / 0090 532... / 532... → 905321112233.
     * Türk cep numarası çıkarılamazsa (sabit hat, eksik) null — düğme gizlenir.
     */
    public static function numara(?string $telefon): ?string
    {
        $rakam = preg_replace('/\D/', '', (string) $telefon);

        $rakam = match (true) {
            str_starts_with($rakam, '0090') => substr($rakam, 2),
            str_starts_with($rakam, '90') && strlen($rakam) === 12 => $rakam,
            str_starts_with($rakam, '0') && strlen($rakam) === 11 => '9'.$rakam,
            strlen($rakam) === 10 => '90'.$rakam,
            default => $rakam,
        };

        return preg_match('/^905\d{9}$/', $rakam) ? $rakam : null;
    }

    public static function url(?string $telefon, string $mesaj): ?string
    {
        $no = self::numara($telefon);

        return $no ? 'https://wa.me/'.$no.'?text='.rawurlencode($mesaj) : null;
    }
}
