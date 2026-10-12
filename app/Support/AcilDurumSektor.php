<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Acil durum planının sektör profili (`config/acil_durum_sektorler.php`).
 * NACE kodu yalnız öneridir; plan kaydındaki seçim (uygulama.sektor) önceliklidir.
 */
class AcilDurumSektor
{
    /** @return array<string, string> anahtar => ad */
    public static function liste(): array
    {
        return collect(config('acil_durum_sektorler'))->map(fn ($s) => $s['ad'])->all();
    }

    /** NACE'nin ilk iki hanesine göre önerilen sektör; eşleşme yoksa genel. */
    public static function oner(?Firma $firma): string
    {
        $kok = substr(preg_replace('/\D/', '', (string) $firma?->nace_kodu), 0, 2);

        if (strlen($kok) === 2) {
            foreach (config('acil_durum_sektorler') as $anahtar => $s) {
                if (in_array($kok, $s['nace'] ?? [], true)) {
                    return $anahtar;
                }
            }
        }

        return 'genel';
    }

    /** Plandaki seçim geçerliyse o, değilse NACE önerisi. */
    public static function secili(array $u, ?Firma $firma): string
    {
        $secim = $u['sektor'] ?? null;

        return static::gecerliMi($secim) ? $secim : static::oner($firma);
    }

    public static function gecerliMi(mixed $anahtar): bool
    {
        return is_string($anahtar) && array_key_exists($anahtar, config('acil_durum_sektorler', []));
    }

    /** @return array{anahtar: string, ad: string, onlemler: ?string, senaryo_aciklama: array<string, string>} */
    public static function profil(array $u, ?Firma $firma): array
    {
        $anahtar = static::secili($u, $firma);
        $s = config('acil_durum_sektorler.'.$anahtar);

        return [
            'anahtar' => $anahtar,
            'ad' => $s['ad'],
            'onlemler' => $s['onlemler'] ?? null,
            'senaryo_aciklama' => $s['senaryo_aciklama'] ?? [],
        ];
    }
}
