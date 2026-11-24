<?php

namespace App\Support;

use App\Models\User;

/**
 * OSGB canlı saha konumu paylaşımını (users.ayarlar['canli_konum']) okuyan yardımcı.
 * Yalnızca sahip (OSGB yöneticisi) ekranlarında gösterilir.
 */
class CanliKonumTakibi
{
    /**
     * Canlı konum paylaşımı açık olan aktif ekip üyelerini döndürür.
     *
     * @return array<int, array{id: int, name: string, unvan: string, enlem: ?float, boylam: ?float, guncelleme: ?string, harita_url: ?string}>
     */
    public static function aktifKonumlar(): array
    {
        return User::query()
            ->where('aktif', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unvan', 'ayarlar'])
            ->map(function (User $u): ?array {
                $konum = (array) (($u->ayarlar ?? [])['canli_konum'] ?? []);
                if (! ($konum['aktif'] ?? false)) {
                    return null;
                }

                $enlem = isset($konum['enlem']) && is_numeric($konum['enlem']) ? (float) $konum['enlem'] : null;
                $boylam = isset($konum['boylam']) && is_numeric($konum['boylam']) ? (float) $konum['boylam'] : null;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'unvan' => $u->unvanEtiketi(),
                    'enlem' => $enlem,
                    'boylam' => $boylam,
                    'guncelleme' => isset($konum['guncelleme']) ? (string) $konum['guncelleme'] : null,
                    'harita_url' => ($enlem !== null && $boylam !== null)
                        ? sprintf('https://www.google.com/maps?q=%.6f,%.6f', $enlem, $boylam)
                        : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
