<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;

/**
 * Firmanın en güncel risk değerlendirmesindeki "izin gerektiren" işleri (sıcak iş, kapalı alan, yüksekte çalışma…)
 * madde metninden tespit eder; JSA sayfasında tek tıkla iş izni / talimat kartlarını besler.
 * Kayıt üretmez, yalnız okur. Tür anahtarları config('isg.is_izin.turler') ile aynıdır.
 */
class YuksekRiskliIsler
{
    /** tür => anahtar kelimeler (küçük harf, kök). */
    public const ANAHTARLAR = [
        'sicak_is' => ['sıcak iş', 'kaynak', 'alev', 'taşlama', 'gaz kesme', 'oksijen kes', 'kıvılcım'],
        'kapali_alan' => ['kapalı alan', 'dar alan', 'tank', 'silo', 'rögar', 'kuyu', 'galeri'],
        'yukseklik' => ['yüksekte', 'yüksekten', 'düşme', 'iskele', 'çatı', 'merdiven', 'platform'],
        'elektrik' => ['elektrik çarp', 'elektrik pano', 'elektrikli', 'enerji hat', 'yüksek gerilim'],
        'kazi' => ['kazı', 'göçük', 'hafriyat', 'çukur', 'yer altı'],
        'kaldirma' => ['vinç', 'kaldırma', 'forklift', 'caraskal', 'halat'],
    ];

    /**
     * @return array<int, array{tur: string, ad: string, adet: int, en_yuksek: float, ornek: string}>
     */
    public static function firma(Firma $firma): array
    {
        $rd = RiskDegerlendirmesi::where('firma_id', $firma->id)->latest('rapor_tarihi')->latest('id')->first();

        if (! $rd) {
            return [];
        }

        $maddeler = $rd->maddeler()->get();
        $adlar = config('isg.is_izin.turler', []);
        $sonuc = [];

        foreach (static::ANAHTARLAR as $tur => $kelimeler) {
            $eslesen = $maddeler->filter(function (RiskMaddesi $m) use ($kelimeler) {
                $metin = RiskAnalitigi::kucult(implode(' ', [$m->bolum, $m->faaliyet, $m->tehlike, $m->risk]));

                foreach ($kelimeler as $kelime) {
                    if (str_contains($metin, $kelime)) {
                        return true;
                    }
                }

                return false;
            });

            if ($eslesen->isEmpty()) {
                continue;
            }

            $sonuc[] = [
                'tur' => $tur,
                'ad' => $adlar[$tur] ?? $tur,
                'adet' => $eslesen->count(),
                'en_yuksek' => (float) $eslesen->max('puan'),
                'ornek' => (string) $eslesen->sortByDesc('puan')->first()->tehlike,
            ];
        }

        return collect($sonuc)->sortByDesc('en_yuksek')->values()->all();
    }
}
