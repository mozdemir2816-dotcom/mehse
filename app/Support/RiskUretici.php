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
     * @param  string|null  $yontem  verilirse kütüphane maddeleri bu yönteme çevrilir (puanları korunur),
     *                               5x5 ölçekli soru maddeleri Fine-Kinney'de O/F/Ş'ye çevrilir
     * @return array<int, array<string, mixed>> aday risk maddeleri
     */
    public static function uret(string $sektor, array $altKategoriler, array $cevaplar, ?string $yontem = null): array
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
                    $adaylar[] = static::olcegeCevir(static::normalize($risk), $yontem);
                }
            }
        }

        // 2) Sektöre göre kütüphane baz riskleri. Genel işyeri riskleri 02.11.2026'dan beri akış başında
        //    zorunlu_ortak olarak eklendiğinden yalnız yöntem verilmeyen (eski) çağrıda aday olur.
        $kategoriAnahtarlari = array_merge(
            $yontem ? [] : ['genel_isyeri'],
            self::KUTUPHANE_ESLESME[$sektor] ?? [],
        );

        $tehlikeler = $kategoriAnahtarlari ? Tehlike::query()
            ->whereHas('kategori', fn ($q) => $q->whereIn('anahtar', $kategoriAnahtarlari))
            ->get() : collect();

        foreach ($tehlikeler as $t) {
            if ($yontem) {
                // Kütüphane puanları korunarak (önceden "puan yok" geliyordu) seçili yönteme çevrilir.
                $adaylar[] = RiskKutuphanesi::maddeyeCevir($t, $yontem);

                continue;
            }

            $adaylar[] = static::normalize([
                'bolum' => $t->bolum,
                'faaliyet' => $t->faaliyet,
                'tehlike' => $t->tehlike,
                'risk' => $t->risk,
                'mevcut_onlem' => $t->mevcut_onlem,
                'oneri' => $t->oneri,
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
                $adaylar[] = static::olcegeCevir(static::normalize($risk, kaynak: 'llm'), $yontem);
            }
        }

        // 4) Aynı tehlike metnini tekrar etme
        return array_values(collect($adaylar)
            ->unique(fn ($m) => Str::lower(trim($m['tehlike'])))
            ->all());
    }

    /**
     * Soru / zorunlu ortak maddeler 5x5 ölçeğinde (O, Ş 1-5) yazılır; Fine-Kinney yönteminde
     * O/F/Ş'ye çevrilir ki maddeler "puan yok" kalmasın.
     */
    public static function olcegeCevir(array $madde, ?string $yontem): array
    {
        if ($yontem !== 'fine_kinney' || $madde['olasilik'] === null || $madde['siddet'] === null) {
            return $madde;
        }

        [$madde['olasilik'], $madde['frekans'], $madde['siddet']] =
            RiskYontemDonusturucu::matrisDegeriniFineKinneye((float) $madde['olasilik'], (float) $madde['siddet']);

        return $madde;
    }

    /**
     * Her işyerinde zorunlu ortak riskler (config isg.risk_ai.zorunlu_ortak) — soru sorulmadan eklenir.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function zorunluOrtakMaddeler(?string $yontem): array
    {
        return collect(config('isg.risk_ai.zorunlu_ortak', []))
            ->map(fn (array $r) => static::olcegeCevir(static::normalize($r, kaynak: 'genel'), $yontem))
            ->all();
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
    public static function siradakiSoru(string $sektor, array $cevaplananlar, array $atlananlar, ?string $naceGrubu = null): ?array
    {
        foreach (config('isg.risk_ai.sorular', []) as $soru) {
            if (! static::soruUygun($soru, $sektor, $naceGrubu)) {
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
    public static function soruSayisi(string $sektor, ?string $naceGrubu = null): int
    {
        return collect(config('isg.risk_ai.sorular', []))
            ->filter(fn ($s) => static::soruUygun($s, $sektor, $naceGrubu))
            ->count();
    }

    /**
     * Soru bu işyerine sorulur mu? (02.11.2026)
     * - 'genel_bina' soruları sorulmaz: karşılığı zorunlu ortak riskler olarak otomatik eklenir.
     * - 'nace_gruplari' olan sektörel soru: firmanın NACE grubu biliniyorsa ona göre, bilinmiyorsa
     *   (NACE yok / sektör elle seçildi) 'sektorler' listesine göre sorulur.
     * - Yalnız 'sektorler' olan soru o sektörlerde, hiçbiri olmayan soru her işyerinde sorulur.
     */
    public static function soruUygun(array $soru, string $sektor, ?string $naceGrubu = null): bool
    {
        if ($soru['genel_bina'] ?? false) {
            return false;
        }

        if ($naceGrubu && isset($soru['nace_gruplari'])) {
            return in_array($naceGrubu, $soru['nace_gruplari'], true);
        }

        return ! isset($soru['sektorler']) || in_array($sektor, $soru['sektorler'], true);
    }
}
