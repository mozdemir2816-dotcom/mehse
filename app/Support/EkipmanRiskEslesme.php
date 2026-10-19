<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use Illuminate\Support\Collection;

/**
 * Ekipman envanteri ↔ risk analizi eşleştirmesi, ekipman ADINA göre.
 * Envanterdeki her aktif ekipmanın adı, firmanın risk değerlendirmelerindeki
 * madde metinlerinde (bölüm, faaliyet, tehlike, risk) geçiyor mu bakılır.
 * Geçmiyorsa "risk analizinde yok" sayılır; periyodik kontrol takibi ile
 * risk analizi eksiklikleri birlikte yakalanır.
 */
class EkipmanRiskEslesme
{
    /** Karşılaştırma için: Türkçe küçük harf, noktalama/işaretler boşluğa, tek boşluk. */
    public static function normalize(?string $metin): string
    {
        $kucuk = mb_strtolower(strtr((string) $metin, ['I' => 'ı', 'İ' => 'i']));
        $temiz = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $kucuk);

        return trim(preg_replace('/\s+/u', ' ', $temiz));
    }

    /**
     * Firmanın envanterinde olup hiçbir risk değerlendirmesinde adı geçmeyen ekipmanlar.
     * Hiç risk değerlendirmesi yoksa boş döner (o durum ayrı bir uyarıyla zaten bildirilir).
     *
     * @return Collection<int, IsEkipmani>
     */
    public static function eksikEkipmanlar(Firma $firma): Collection
    {
        $rdler = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->with('maddeler')->get();

        if ($rdler->isEmpty()) {
            return collect();
        }

        $metin = self::normalize($rdler
            ->flatMap(fn (RiskDegerlendirmesi $rd) => $rd->maddeler)
            ->map(fn ($m) => implode(' ', [$m->bolum, $m->faaliyet, $m->tehlike, $m->risk]))
            ->implode(' | '));

        return EkipmanKatalogu::ekipmanlar($firma)
            ->filter(function (IsEkipmani $e) use ($metin) {
                $ad = self::normalize($e->ekipman_adi);

                return $ad !== '' && ! str_contains($metin, $ad);
            })
            ->values();
    }
}
