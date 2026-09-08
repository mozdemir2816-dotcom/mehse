<?php

namespace App\Support;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Collection;

/**
 * Risk Sihirbazı "Manuel Seçim" adımı — Risk Kütüphanesi kategorileri ve
 * tehlikeleri, ayrıca bir tehlikeyi sihirbaz maddesine çevirme.
 */
class RiskKutuphanesi
{
    /** @return Collection<int, TehlikeKategorisi> tehlikeleriyle birlikte, sıralı */
    public static function kategoriler(): Collection
    {
        return TehlikeKategorisi::query()
            ->with(['tehlikeler' => fn ($q) => $q->orderBy('kod')])
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();
    }

    /**
     * Kütüphane tehlikesini sihirbaz "seçilen madde" dizisine çevirir.
     *
     * @return array<string, mixed>
     */
    public static function maddeyeCevir(Tehlike $t): array
    {
        return [
            'anahtar' => 'lib-'.$t->id.'-'.substr(md5(uniqid('', true)), 0, 6),
            'kaynak' => 'kutuphane',
            'tehlike_id' => $t->id,
            'bolum' => $t->bolum,
            'faaliyet' => $t->faaliyet,
            'tehlike' => $t->tehlike,
            'risk' => $t->risk,
            'mevcut_onlem' => $t->mevcut_onlem,
            'mevzuat' => $t->mevzuat,
            'oneri' => null,
            'sorumlu' => null,
            'termin' => null,
            // Kütüphaneye sektörel bir analizden (ör. İnşaat Fine-Kinney) O/F/Ş ile
            // aktarıldıysa sihirbaz formuna önceden dolsun.
            'olasilik' => $t->olasilik !== null ? (float) $t->olasilik : null,
            'frekans' => $t->frekans !== null ? (float) $t->frekans : null,
            'siddet' => $t->siddet !== null ? (float) $t->siddet : null,
        ];
    }

    /** Boş (elle doldurulacak) madde iskeleti. */
    public static function bosMadde(): array
    {
        return [
            'anahtar' => 'yeni-'.substr(md5(uniqid('', true)), 0, 8),
            'kaynak' => 'manuel',
            'tehlike_id' => null,
            'bolum' => null,
            'faaliyet' => null,
            'tehlike' => '',
            'risk' => null,
            'mevcut_onlem' => null,
            'mevzuat' => null,
            'oneri' => null,
            'sorumlu' => null,
            'termin' => null,
            'olasilik' => null,
            'frekans' => null,
            'siddet' => null,
        ];
    }
}
