<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\ZiyaretProgrami;

/**
 * Profilim > Firma Ziyaretleri (isgpratik 147.jpg) — yeni tablo YOK, mevcut
 * ZiyaretProgrami kayıtlarının (firma × yıl, 12 aylık satır) tarihli ay
 * satırlarını gün bazında gruplayan salt-okunur takvim görünümü.
 */
class ZiyaretTakvimi
{
    /** @return array<string, array<int, array{firma: Firma, amac: ?string, sure_saat: ?float, durum: string}>> 'Y-m-d' => o günkü ziyaretler */
    public static function gunlukGruplar(int $userId): array
    {
        $gruplar = [];

        ZiyaretProgrami::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->with('firma:id,unvan')
            ->get()
            ->each(function (ZiyaretProgrami $program) use (&$gruplar): void {
                foreach ($program->ziyaretler ?? [] as $ay) {
                    if (blank($ay['tarih'] ?? null)) {
                        continue;
                    }

                    $gruplar[$ay['tarih']][] = [
                        'firma' => $program->firma,
                        'amac' => $ay['amac'] ?? null,
                        'sure_saat' => $ay['sure_saat'] ?? null,
                        'durum' => $ay['durum'] ?? 'bos',
                    ];
                }
            });

        return $gruplar;
    }

    /** @return array{ziyaret: int, firma: int} */
    public static function ozet(int $userId): array
    {
        $gruplar = static::gunlukGruplar($userId);
        $firmaIdler = [];
        $ziyaretSayisi = 0;

        foreach ($gruplar as $gun) {
            foreach ($gun as $z) {
                $ziyaretSayisi++;
                $firmaIdler[$z['firma']?->id] = true;
            }
        }

        return ['ziyaret' => $ziyaretSayisi, 'firma' => count($firmaIdler)];
    }
}
