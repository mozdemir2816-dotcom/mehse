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
    /**
     * Ekipman / şantiye dosyalarının "Eğitim Riskleri" ve "Acil Durum Riskleri" sayfa
     * maddeleri (kod ...-E-/...-A-) her ekipmanın klasörüne dağılmış durumda; manuel
     * seçimde tek klasörden seçilebilsin diye sanal gruplar (negatif id). Kullanıcı 14.10.2026.
     */
    public const SANAL_GRUPLAR = [
        -1 => ['ad' => 'Genel – Eğitim ve yetkilendirme (tüm ekipmanlar)', 'kod' => '%-E-%', 'anahtar' => 'atolye_egitim_ve_yetkilendirme'],
        -2 => ['ad' => 'Genel – Acil durumlar (tüm ekipmanlar)', 'kod' => '%-A-%', 'anahtar' => 'atolye_acil_durumlar'],
    ];

    /** @return Collection<int, TehlikeKategorisi> tehlikeleriyle birlikte, sıralı; başta sanal Eğitim / Acil durum grupları */
    public static function kategoriler(): Collection
    {
        $sanal = collect(self::SANAL_GRUPLAR)
            ->map(function (array $g, int $id) {
                $kat = new TehlikeKategorisi(['ad' => $g['ad'], 'anahtar' => 'sanal_'.abs($id)]);
                $kat->id = $id;

                return $kat->setRelation('tehlikeler', static::grupTehlikeleri($id));
            })
            ->filter(fn (TehlikeKategorisi $k) => $k->tehlikeler->isNotEmpty());

        return $sanal->values()->concat(TehlikeKategorisi::query()
            ->with(['tehlikeler' => fn ($q) => $q->orderBy('kod')])
            ->orderBy('sira')
            ->orderBy('ad')
            ->get());
    }

    /** @return Collection<int, Tehlike> bir kategorinin (sanal grup dahil) tehlikeleri */
    public static function grupTehlikeleri(int $kategoriId): Collection
    {
        $g = self::SANAL_GRUPLAR[$kategoriId] ?? null;

        if (! $g) {
            return Tehlike::where('tehlike_kategorisi_id', $kategoriId)->orderBy('kod')->get();
        }

        return Tehlike::query()
            ->where(fn ($q) => $q->where('kod', 'like', $g['kod'])
                ->orWhereHas('kategori', fn ($k) => $k->where('anahtar', $g['anahtar'])))
            ->orderBy('bolum')->orderBy('kod')
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
            // Kütüphanedeki "Alınacak önlem" (ekipman / şantiye dosyalarının DÖF metni) → Önerilen önlem.
            'oneri' => $t->oneri,
            'sorumlu' => $t->sorumlu,
            'termin' => $t->termin,
            // Kütüphaneye sektörel bir analizden (ör. İnşaat Fine-Kinney) O/F/Ş ile
            // aktarıldıysa sihirbaz formuna önceden dolsun.
            'olasilik' => $t->olasilik !== null ? (float) $t->olasilik : null,
            'frekans' => $t->frekans !== null ? (float) $t->frekans : null,
            'siddet' => $t->siddet !== null ? (float) $t->siddet : null,
            // Hedef (önlem sonrası) puanlar — dosyadaki "Hedef P/F/S"; boşsa model varsayımı.
            'son_olasilik' => $t->son_olasilik !== null ? (float) $t->son_olasilik : null,
            'son_frekans' => $t->son_frekans !== null ? (float) $t->son_frekans : null,
            'son_siddet' => $t->son_siddet !== null ? (float) $t->son_siddet : null,
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
            'termin' => $t->termin,
            'olasilik' => null,
            'frekans' => null,
            'siddet' => null,
            'son_olasilik' => null,
            'son_frekans' => null,
            'son_siddet' => null,
        ];
    }
}
