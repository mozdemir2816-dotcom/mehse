<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Str;

/**
 * Acil Durum Eylem Planında yeni bir plan oluşturulurken, firmanın NACE koduna
 * ve tehlike sınıfına göre hangi acil durum konularının otomatik işaretleneceğini
 * belirler (`config/isg.php → acil_durum.konular[].kosul`). Firmaya uymayan konu
 * seçilmez; kullanıcı elle ekleyip çıkarabilir.
 */
class AcilDurumKonuSecici
{
    /** @return array<int, string> önceden seçilecek konu anahtarları */
    public static function firmaIcin(?Firma $firma): array
    {
        $nace = static::naceKok($firma?->nace_kodu);
        $tehlike = $firma?->tehlike_sinifi;

        return collect(config('isg.acil_durum.konular', []))
            ->filter(fn (array $konu) => static::uygunMu($konu['kosul'] ?? null, $nace, $tehlike))
            ->pluck('anahtar')
            ->all();
    }

    /** Firma yokken kullanılacak genel varsayılan (koşulsuz konular). */
    public static function genelVarsayilan(): array
    {
        return collect(config('isg.acil_durum.konular', []))
            ->whereNull('kosul')
            ->pluck('anahtar')
            ->all();
    }

    private static function uygunMu(?array $kosul, ?string $naceKok, ?string $tehlike): bool
    {
        if ($kosul === null) {
            return true;
        }

        foreach ($kosul['nace'] ?? [] as $onEk) {
            if ($naceKok !== null && str_starts_with($naceKok, (string) $onEk)) {
                return true;
            }
        }

        return in_array($tehlike, $kosul['tehlike'] ?? [], true);
    }

    /** "10.71" / "1071" → "10" gibi ilk iki haneli NACE kökü. */
    private static function naceKok(?string $nace): ?string
    {
        if (blank($nace)) {
            return null;
        }

        $rakamlar = preg_replace('/\D/', '', $nace);

        return $rakamlar === '' ? null : Str::substr($rakamlar, 0, 2);
    }
}
