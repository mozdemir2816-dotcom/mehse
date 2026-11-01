<?php

namespace App\Support;

use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use Filament\Facades\Filament;

/**
 * Puansız kütüphane maddesi bir analizde ilk kez puanlanınca o puan kütüphaneye yazılır
 * (kullanıcı kararı 01.11.2026: "birkere puanı verince kütüphaneye o puanla kaydet").
 *
 * - Yalnız BOŞ olan yöntem puanı doldurulur; dolu kütüphane puanının üzerine yazılmaz.
 * - Fine-Kinney → tehlikeler.olasilik/frekans/siddet (+ son_*); 5x5 → tehlikeler.matris
 *   {olasilik, siddet, son_olasilik, son_siddet}. 5x5 madde FK puanı varsa zaten dönüştürülerek
 *   dolu geldiği için puansız sayılmaz. HAZOP/FMEA ve hastane şablonu öğrenilmez.
 * - Genel kütüphane tüm kullanıcılarda ortak olduğundan yalnız sahip hesabın puanı yazılır
 *   (yapay zekâ maddelerinin kütüphaneye kaydıyla aynı kural).
 */
class KutuphanePuanOgrenme
{
    public static function ogren(RiskMaddesi $m): void
    {
        if (! $m->tehlike_id || $m->olasilik === null || $m->siddet === null) {
            return;
        }

        if (! Filament::auth()->user()?->sahipMi()) {
            return;
        }

        $rd = $m->riskDegerlendirmesi;
        if (! $rd || $rd->hastaneMi()) {
            return;
        }

        $t = Tehlike::find($m->tehlike_id);
        if (! $t) {
            return;
        }

        if ($rd->yontem === 'fine_kinney') {
            if ($m->frekans === null || $t->olasilik !== null || $t->frekans !== null || $t->siddet !== null) {
                return;
            }

            $t->forceFill([
                'olasilik' => $m->olasilik, 'frekans' => $m->frekans, 'siddet' => $m->siddet,
                'son_olasilik' => $t->son_olasilik ?? $m->son_olasilik,
                'son_frekans' => $t->son_frekans ?? $m->son_frekans,
                'son_siddet' => $t->son_siddet ?? $m->son_siddet,
            ])->save();

            return;
        }

        if ($rd->yontem === 'matris_5x5') {
            $matris = $t->matris ?? [];
            if (isset($matris['olasilik']) || isset($matris['siddet']) || $t->olasilik !== null || $t->siddet !== null) {
                return;
            }

            $t->matris = array_merge($matris, [
                'olasilik' => (int) $m->olasilik,
                'siddet' => (int) $m->siddet,
                'son_olasilik' => $m->son_olasilik !== null ? (int) $m->son_olasilik : null,
                'son_siddet' => $m->son_siddet !== null ? (int) $m->son_siddet : null,
            ]);
            $t->save();
        }
    }
}
