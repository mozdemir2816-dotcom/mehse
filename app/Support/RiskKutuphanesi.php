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

    /**
     * Yöntem verilirse veritabanı kategorileri yalnız o yönteme ait ve firma NACE grubuna uyan
     * (ya da "ortak") olanlarla süzülür. Sanal Eğitim / Acil durum grupları yalnız seçilen yöntemin maddelerini toplar (boşsa gösterilmez).
     *
     * @return Collection<int, TehlikeKategorisi> tehlikeleriyle birlikte, sıralı; başta sanal gruplar
     */
    public static function kategoriler(?string $yontem = null, ?string $naceGrubu = null): Collection
    {
        $sanal = collect(self::SANAL_GRUPLAR)
            ->map(function (array $g, int $id) use ($yontem) {
                $kat = new TehlikeKategorisi(['ad' => $g['ad'], 'anahtar' => 'sanal_'.abs($id)]);
                $kat->id = $id;

                return $kat->setRelation('tehlikeler', static::grupTehlikeleri($id, $yontem));
            })
            ->filter(fn (TehlikeKategorisi $k) => $k->tehlikeler->isNotEmpty());

        $sorgu = TehlikeKategorisi::query()
            ->with(['tehlikeler' => fn ($q) => $q->orderBy('kod')]);

        if ($yontem !== null) {
            $sorgu->uygun($yontem, $naceGrubu);
        }

        return $sanal->values()->concat($sorgu
            ->orderBy('sira')
            ->orderBy('ad')
            ->get());
    }

    /**
     * @param  ?string  $yontem  verilirse sanal grup yalnız bu yöntemin kategorilerindeki maddeleri toplar
     * @return Collection<int, Tehlike> bir kategorinin (sanal grup dahil) tehlikeleri
     */
    public static function grupTehlikeleri(int $kategoriId, ?string $yontem = null): Collection
    {
        $g = self::SANAL_GRUPLAR[$kategoriId] ?? null;

        if (! $g) {
            return Tehlike::where('tehlike_kategorisi_id', $kategoriId)->orderBy('kod')->get();
        }

        return Tehlike::query()
            ->where(fn ($q) => $q->where('kod', 'like', $g['kod'])
                ->orWhereHas('kategori', fn ($k) => $k->where('anahtar', $g['anahtar'])))
            ->when(filled($yontem), fn ($q) => $q->whereHas('kategori', fn ($k) => $k->where(fn ($w) => $w->where('yontem', $yontem)
                ->when($yontem === 'fine_kinney', fn ($w) => $w->orWhereNull('yontem')))))
            ->orderBy('bolum')->orderBy('kod')
            ->get();
    }

    /**
     * "Önce kütüphane, yoksa AI" (02.11.2026): serbest yazılan ekipman / iş adına uyan kütüphane klasörleri.
     * Eşleşme: klasörün sade adı aranan metni (kelime başından) içerir ya da aranan metin klasörün sade
     * adını içerir (Türkçe karakter ve büyük/küçük harf duyarsız). HAZOP klasörleri ve maddesiz klasörler hariç;
     * yöntem süzgeci manuel listedeki gibidir (5x5'te Fine-Kinney klasörleri çevrilerek kullanılır).
     * En iyi 3 eşleşme: önce ekipman klasörleri, sonra madde sayısı.
     *
     * @return Collection<int, TehlikeKategorisi>
     */
    public static function ekipmanAra(string $ad, ?string $yontem = null): Collection
    {
        $aranan = static::aramaMetni($ad);

        if (mb_strlen($aranan) < 3) {
            return collect();
        }

        return TehlikeKategorisi::query()
            ->where(fn ($q) => $q->whereNull('yontem')->orWhere('yontem', '!=', 'hazop'))
            ->when($yontem, fn ($q) => $q->where(fn ($w) => $w->whereNull('yontem')->orWhere('yontem', $yontem)
                ->when($yontem === 'matris_5x5', fn ($x) => $x->orWhere('yontem', 'fine_kinney'))))
            ->withCount('tehlikeler')
            ->get()
            ->filter(function (TehlikeKategorisi $k) use ($aranan) {
                $sade = static::aramaMetni(static::sadeAd($k->ad));

                // Kelime başından eşleşme: "pres" → "Hidrolik pres(i)" evet, "Kompresör" hayır.
                $kelimeBasi = fn (string $samanlik, string $igne) => (bool) preg_match('/(^| )'.preg_quote($igne, '/').'/', $samanlik);

                return $k->tehlikeler_count > 0 && $sade !== ''
                    && ($kelimeBasi($sade, $aranan) || (mb_strlen($sade) >= 4 && $kelimeBasi($aranan, $sade)));
            })
            ->sortBy([
                fn ($a, $b) => (int) ! str_starts_with($a->anahtar, 'ekipman_') <=> (int) ! str_starts_with($b->anahtar, 'ekipman_'),
                fn ($a, $b) => $b->tehlikeler_count <=> $a->tehlikeler_count,
            ])
            ->take(3)
            ->values();
    }

    /** "Ekipman – 15 avuc taslama spiral risk degerlendirmesi" → "avuc taslama spiral"; "(AI)" / "(Özel Ekipman)" eki atılır. */
    public static function sadeAd(string $ad): string
    {
        return trim((string) preg_replace(
            ['/^(Ekipman|Atölye|Şantiye)\s*[–-]\s*/u', '/^\d+\s+/u', '/\s*risk\s+de[gğ]erlendirmesi\b/iu', '/\s*\((AI|Özel Ekipman)\)\s*$/u', '/\s*\d+\s+madde$/iu'],
            '',
            $ad,
        ));
    }

    /** Karşılaştırma için: küçük harf, Türkçe karakterler ASCII, tek boşluk. */
    private static function aramaMetni(string $metin): string
    {
        $metin = mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $metin));
        $metin = strtr($metin, ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'û' => 'u']);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $metin));
    }

    /**
     * Kütüphane tehlikesini sihirbaz "seçilen madde" dizisine çevirir.
     *
     * Kütüphane puanları Fine-Kinney ölçeğindedir; $yontem 'matris_5x5' ise O/Ş 1-5 ölçeğine
     * çevrilir (5x5 Excel'inden yüklenmişse kaynak değerler aynen, yoksa ölçek eşlemesiyle)
     * ve frekans boş bırakılır.
     *
     * @return array<string, mixed>
     */
    public static function maddeyeCevir(Tehlike $t, ?string $yontem = null): array
    {
        $madde = [
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
            // HAZOP senaryosunun Excel hücre metinleri (puansız çıktı bunlardan basılır).
            'hazop' => $t->hazop,
        ];

        if ($yontem === 'matris_5x5') {
            [$o, $s] = RiskYontemDonusturucu::fineKinneyDegeriniMatrise($madde['olasilik'], $madde['siddet']);
            [$so, $ss] = RiskYontemDonusturucu::fineKinneyDegeriniMatrise($madde['son_olasilik'], $madde['son_siddet']);
            $kaynak = $t->matris ?? [];

            $madde['olasilik'] = $kaynak['olasilik'] ?? $o;
            $madde['siddet'] = $kaynak['siddet'] ?? $s;
            $madde['son_olasilik'] = $kaynak['son_olasilik'] ?? $so;
            $madde['son_siddet'] = $kaynak['son_siddet'] ?? $ss;
            $madde['frekans'] = $madde['son_frekans'] = null;
        }

        return $madde;
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
            'son_olasilik' => null,
            'son_frekans' => null,
            'son_siddet' => null,
        ];
    }
}
