<?php

namespace App\Support;

use App\Models\Tehlike;
use Illuminate\Support\Str;

/**
 * Risk Sihirbazı "Yapay Zeka" adımı — sektör + alt kategori + soru
 * cevaplarındaki eksikliklerden **kural tabanlı** mevzuat referanslı risk
 * maddeleri türetir (isgpratik 103-115.jpg). `GeminiRiskDanismani` API
 * anahtarı tanımlıysa aynı şemada işyerine özel ek öneriler ekler.
 */
class RiskUretici
{
    /** Sektör → Risk Kütüphanesi kategori anahtarları (baz riskler). */
    private const KUTUPHANE_ESLESME = [
        'fabrika' => ['atolye'],
        'servis' => ['atolye'],
        'depo' => ['atolye'],
        'insaat' => ['insaat'],
        'maden' => ['insaat'],
    ];

    /**
     * @param  string  $sektor  config isg.risk_ai.sektorler anahtarı
     * @param  array<int, string>  $altKategoriler  seçilen alt kategori etiketleri
     * @param  array<string, string|array<int,string>>  $cevaplar  soru anahtarı => seçilen değer(ler)
     * @return array<int, array<string, mixed>> aday risk maddeleri
     */
    public static function uret(string $sektor, array $altKategoriler, array $cevaplar): array
    {
        $adaylar = [];

        // 1) Cevaplardaki eksikliklerin tetiklediği riskler
        foreach (config('isg.risk_ai.sorular', []) as $soru) {
            $secim = $cevaplar[$soru['anahtar']] ?? null;

            if (blank($secim)) {
                continue;
            }

            $secilenDegerler = (array) $secim;

            foreach ($soru['secenekler'] as $secenek) {
                if (! in_array($secenek['deger'], $secilenDegerler, true)) {
                    continue;
                }

                foreach ($secenek['riskler'] ?? [] as $risk) {
                    $adaylar[] = static::normalize($risk);
                }
            }
        }

        // 2) Sektöre göre kütüphane baz riskleri
        $kategoriAnahtarlari = array_merge(
            ['genel_isyeri'],
            self::KUTUPHANE_ESLESME[$sektor] ?? [],
        );

        $tehlikeler = Tehlike::query()
            ->whereHas('kategori', fn ($q) => $q->whereIn('anahtar', $kategoriAnahtarlari))
            ->get();

        foreach ($tehlikeler as $t) {
            $adaylar[] = static::normalize([
                'bolum' => $t->bolum,
                'faaliyet' => $t->faaliyet,
                'tehlike' => $t->tehlike,
                'risk' => $t->risk,
                'mevcut_onlem' => $t->mevcut_onlem,
                'oneri' => null,
                'mevzuat' => $t->mevzuat,
                'olasilik' => null,
                'siddet' => null,
            ], kaynak: 'kutuphane', tehlikeId: $t->id);
        }

        // 3) Gemini (gerçek LLM) — API anahtarı tanımlıysa işyerine özel ek öneriler ister
        if (GeminiRiskDanismani::aktifMi()) {
            $sektorAdi = config('isg.risk_ai.sektorler.'.$sektor.'.ad', $sektor);
            $mevcutTehlikeler = collect($adaylar)->pluck('tehlike')->filter()->values()->all();

            foreach (GeminiRiskDanismani::oner($sektorAdi, $altKategoriler, $cevaplar, $mevcutTehlikeler) as $risk) {
                $adaylar[] = static::normalize($risk, kaynak: 'llm');
            }
        }

        // 4) Aynı tehlike metnini tekrar etme
        return array_values(collect($adaylar)
            ->unique(fn ($m) => Str::lower(trim($m['tehlike'])))
            ->all());
    }

    /** @return array<string, mixed> */
    private static function normalize(array $risk, string $kaynak = 'ai', ?int $tehlikeId = null): array
    {
        return [
            'anahtar' => 'ai-'.substr(md5(($risk['tehlike'] ?? '').uniqid('', true)), 0, 10),
            'kaynak' => $kaynak,
            'tehlike_id' => $tehlikeId,
            'bolum' => $risk['bolum'] ?? null,
            'faaliyet' => $risk['faaliyet'] ?? null,
            'tehlike' => $risk['tehlike'] ?? '',
            'risk' => $risk['risk'] ?? null,
            'mevcut_onlem' => $risk['mevcut_onlem'] ?? null,
            'oneri' => $risk['oneri'] ?? null,
            'mevzuat' => $risk['mevzuat'] ?? null,
            'olasilik' => $risk['olasilik'] ?? null,
            'frekans' => null,
            'siddet' => $risk['siddet'] ?? null,
        ];
    }

    /**
     * Sıradaki sorulacak sorunun indeksini döndürür (sektöre uymayan sorular atlanır).
     *
     * @param  array<int, string>  $atlananlar  kullanıcının "Atla" dediği soru anahtarları
     */
    public static function siradakiSoru(string $sektor, array $cevaplananlar, array $atlananlar): ?array
    {
        foreach (config('isg.risk_ai.sorular', []) as $soru) {
            if (isset($soru['sektorler']) && ! in_array($sektor, $soru['sektorler'], true)) {
                continue;
            }

            if (in_array($soru['anahtar'], $cevaplananlar, true) || in_array($soru['anahtar'], $atlananlar, true)) {
                continue;
            }

            return $soru;
        }

        return null;
    }

    /** Sektöre uygun toplam soru sayısı (ilerleme çubuğu için). */
    public static function soruSayisi(string $sektor): int
    {
        return collect(config('isg.risk_ai.sorular', []))
            ->reject(fn ($s) => isset($s['sektorler']) && ! in_array($sektor, $s['sektorler'], true))
            ->count();
    }
}
