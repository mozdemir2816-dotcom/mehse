<?php

namespace App\Support;

use App\Models\KurulToplantisi;
use App\Models\SahaBulgusu;

/**
 * İSG Kurulu kararını Saha Bulgu & DÖF Takip Havuzu'na görev olarak aktarır
 * (30.10.2026). Aktarılan karar kendi kararlar[] satırında `bulgu_id` taşır:
 * aynı karar ikinci kez aktarılmaz, mevcut bulgu döner (BulguHavuzu ile aynı kural).
 */
class KararDofAktarici
{
    public static function aktar(KurulToplantisi $toplanti, int $index): ?SahaBulgusu
    {
        $kararlar = $toplanti->kararlar ?? [];
        $karar = $kararlar[$index] ?? null;

        if (! is_array($karar) || blank($karar['karar_metni'] ?? null)) {
            return null;
        }

        $mevcut = filled($karar['bulgu_id'] ?? null) ? SahaBulgusu::find($karar['bulgu_id']) : null;

        if ($mevcut) {
            return $mevcut;
        }

        $bulgu = SahaBulgusu::create([
            'firma_id' => $toplanti->firma_id,
            'kaynak_tablo' => $toplanti->getTable(),
            'kaynak_kayit_id' => $toplanti->getKey(),
            'kaynak_sira' => $index,
            'kategori' => 'İSG Kurulu Kararı',
            'bolum' => filled($karar['gundem_maddesi'] ?? null) ? $karar['gundem_maddesi'] : null,
            'uygunsuzluk' => trim((string) $karar['karar_metni']),
            'aksiyon' => trim((string) $karar['karar_metni']),
            'yasal_gerekce' => 'İSG Kurulları Hakkında Yönetmelik — kurul kararlarının takibi',
            'sorumlu' => filled($karar['sorumlu'] ?? null) ? $karar['sorumlu'] : null,
            'termin' => filled($karar['termin'] ?? null) ? $karar['termin'] : null,
            'durum' => ($karar['durum'] ?? '') === 'tamamlandi' ? 'kapandi' : 'acik',
            'kaydeden' => auth()->user()?->name,
        ]);

        $kararlar[$index]['bulgu_id'] = $bulgu->id;
        $toplanti->update(['kararlar' => $kararlar]);

        return $bulgu;
    }
}
