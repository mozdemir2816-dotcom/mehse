<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * KKD türüne göre varsayılan raf ömrü / değişim süresi (30.10.2026): baret ve
 * düşme durdurma/emniyet kemeri 5 yıl; filtreli maske kartuşu 6 ay (üretici
 * talimatı esastır — form alanları elle düzenlenebilir).
 */
class KkdDegisimSuresi
{
    /** Anahtar sözcük (ASCII, küçük harf) → [ay, etiket]; ilk eşleşen kazanır. */
    private const KURALLAR = [
        'kartus' => [6, '6 ay'],
        'filtre' => [6, '6 ay'],
        'baret' => [60, '5 yıl'],
        'emniyet kemeri' => [60, '5 yıl'],
        'dusme' => [60, '5 yıl'],
        'yasam hatti' => [60, '5 yıl'],
    ];

    /** @return array{ay: int, etiket: string}|null */
    public static function sure(?string $tur): ?array
    {
        $sade = Str::of(Str::ascii(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], (string) $tur))))->toString();

        foreach (self::KURALLAR as $anahtar => [$ay, $etiket]) {
            if (str_contains($sade, $anahtar)) {
                return ['ay' => $ay, 'etiket' => $etiket];
            }
        }

        return null;
    }

    /** Teslim tarihinden sonraki değişim tarihi (Y-m-d) ya da tür için kural yoksa null. */
    public static function sonrakiDegisim(?string $tur, ?string $teslimTarihi): ?string
    {
        $sure = static::sure($tur);

        if (! $sure || blank($teslimTarihi)) {
            return null;
        }

        return Carbon::parse($teslimTarihi)->addMonthsNoOverflow($sure['ay'])->toDateString();
    }
}
