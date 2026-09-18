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
    /**
     * @return array<string, array<int, array{firma: Firma, amac: ?string, sure_saat: ?float, durum: string, program_id: int, ay_index: int, satir_index: int}>>
     *         'Y-m-d' => o günkü ziyaretler. program_id/ay_index/satir_index, ZiyaretProgrami::durumIlerlet()
     *         ile bu girdiyi tekrar bulup güncellemek isteyen çağıranlar (ör. panel takvim widget'ı) içindir.
     */
    public static function gunlukGruplar(int $userId): array
    {
        $gruplar = [];

        ZiyaretProgrami::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->with('firma:id,unvan')
            ->get()
            ->each(function (ZiyaretProgrami $program) use (&$gruplar): void {
                foreach ($program->ziyaretler ?? [] as $ayIndex => $ay) {
                    foreach (ZiyaretProgrami::ayGirdileri($ay) as $satirIndex => $z) {
                        if (blank($z['tarih'] ?? null)) {
                            continue;
                        }

                        $gruplar[$z['tarih']][] = [
                            'firma' => $program->firma,
                            'amac' => $z['amac'] ?? null,
                            'sure_saat' => $z['sure_saat'] ?? null,
                            'durum' => $z['durum'] ?? 'bos',
                            'program_id' => $program->id,
                            'ay_index' => $ayIndex,
                            'satir_index' => $satirIndex,
                        ];
                    }
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
