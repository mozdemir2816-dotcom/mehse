<?php

namespace App\Support;

/**
 * PHP'nin mb_strtoupper/strtoupper fonksiyonları Türkçe'ye özgü nokta
 * kuralını bilmez ("tekstil" → "TEKSTIL" yapar, "TEKSTİL" olması gerekirken).
 * Küçük "i" harfini önce noktalı büyük "İ"ye çevirip sonra büyütüyoruz;
 * noktasız "ı" zaten doğru şekilde "I"ya döner.
 */
class TurkceMetin
{
    public static function buyuk(string $metin): string
    {
        return mb_strtoupper(str_replace('i', 'İ', $metin), 'UTF-8');
    }

    /** Türkçe "I" → "ı", "İ" → "i" dönüşümünü doğru yapan küçük harf yardımcı metodu. */
    public static function kucuk(string $metin): string
    {
        return mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $metin), 'UTF-8');
    }
}
