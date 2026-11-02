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
 *   (yapay zekâ maddelerinin kütüphaneye kaydıyla aynı kural). Arka planda yapay zekânın doldurduğu
 *   alanlar (sistemYazimi) da yazılır.
 * - 02.11.2026: puanla birlikte metin alanları da (öneri, mevcut önlem, mevzuat, risk tanımı, sorumlu)
 *   kütüphanedeki BOŞ karşılığına yazılır — App\Support\RiskAlanTamamlama::kutuphaneyeYaz.
 */
class KutuphanePuanOgrenme
{
    /** Yapay zekâ ile toplu tamamlama sırasında (komutlar) kütüphaneye yazmaya izin verir. */
    public static bool $sistemYazimi = false;

    /** İşi, kütüphaneye yazma izniyle çalıştırır (arka plan yapay zekâ tamamlaması). */
    public static function sistemOlarak(callable $is): mixed
    {
        $once = static::$sistemYazimi;
        static::$sistemYazimi = true;

        try {
            return $is();
        } finally {
            static::$sistemYazimi = $once;
        }
    }

    public static function ogren(RiskMaddesi $m): void
    {
        if (! static::$sistemYazimi && ! Filament::auth()->user()?->sahipMi()) {
            return;
        }

        $rd = $m->riskDegerlendirmesi;
        if (! $rd || $rd->hastaneMi()) {
            return;
        }

        // Metin alanları: kütüphaneden eklenip sonradan doldurulan / düzeltilen madde (yeni oluşturma hariç —
        // kütüphaneden kopyalanan değerler zaten oradan gelir).
        if (! $m->wasRecentlyCreated && $m->wasChanged(RiskAlanTamamlama::KUTUPHANE_METIN_ALANLARI)) {
            RiskAlanTamamlama::kutuphaneyeYaz($m);
        }

        if (! $m->tehlike_id || $m->olasilik === null || $m->siddet === null) {
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
