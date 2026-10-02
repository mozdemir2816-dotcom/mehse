<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Collection;

/**
 * Tehlike ve Risk Analitiği (isgsuite "Tehlike ve Risk Analitiği"): bir
 * işyerinin risk kayıtlarını tehlike türüne (fiziksel / kimyasal /
 * biyolojik / ergonomik / psikososyal) göre toplar ve maruz kalan kişi
 * sayısıyla ağırlıklandırır:
 *
 *   dominans skoru = risk puanı × max(maruz kalan kişi, 1)
 *
 * NACE'ye göre olası tehlike kaynakları (config/risk_nace.php) risk
 * kayıtlarıyla karşılaştırılır; kayıtta karşılığı olmayan başlık
 * "saha doğrulaması bekliyor" olarak kalır. Kayıt üretmez.
 */
class RiskAnalitigi
{
    /** @return array<string, array{ad: string, renk: string, anahtar: array<int, string>}> */
    public static function turler(): array
    {
        return config('risk_nace.tehlike_turleri');
    }

    public static function kucult(string $metin): string
    {
        return mb_strtolower(strtr($metin, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
    }

    /** Maddede seçilmiş tür; yoksa tehlike / risk / faaliyet metninden tahmin. */
    public static function tur(RiskMaddesi $m): string
    {
        if ($m->tehlike_turu && array_key_exists($m->tehlike_turu, static::turler())) {
            return $m->tehlike_turu;
        }

        $metin = ' '.static::kucult(implode(' ', [$m->tehlike, $m->risk, $m->faaliyet])).' ';

        foreach (static::turler() as $anahtar => $t) {
            foreach ($t['anahtar'] as $kelime) {
                if (str_contains($metin, $kelime)) {
                    return $anahtar;
                }
            }
        }

        return 'fiziksel';
    }

    public static function dominans(RiskMaddesi $m): float
    {
        return (float) $m->puan * max((int) $m->maruz_kisi, 1);
    }

    /** @return array<string, mixed> */
    public static function analiz(Firma $firma, ?RiskDegerlendirmesi $rd): array
    {
        $maddeler = $rd ? $rd->maddeler()->orderBy('sira')->get() : collect();
        $turler = static::turler();

        $satirlar = $maddeler->map(fn (RiskMaddesi $m) => [
            'madde' => $m,
            'tur' => static::tur($m),
            'tahmin' => blank($m->tehlike_turu),
            'dominans' => static::dominans($m),
        ]);

        $toplamDominans = $satirlar->sum('dominans');

        $dagilim = collect($turler)->map(function ($t, $anahtar) use ($satirlar, $toplamDominans) {
            $grup = $satirlar->where('tur', $anahtar);
            $skor = $grup->sum('dominans');

            return [
                'ad' => $t['ad'],
                'renk' => $t['renk'],
                'kayit' => $grup->count(),
                'maruz' => $grup->sum(fn ($s) => (int) $s['madde']->maruz_kisi),
                'skor' => $skor,
                'pay' => $toplamDominans > 0 ? round($skor / $toplamDominans * 100, 1) : 0.0,
            ];
        })->sortByDesc('skor')->all();

        $baskin = collect($dagilim)->filter(fn ($d) => $d['skor'] > 0)->keys()->first();

        return [
            'kapsam' => [
                'nace' => $firma->nace_kodu,
                'tehlike_sinifi' => $firma->tehlikeSinifiEtiketi(),
                'calisan' => $firma->calisanlar()->where('aktif', true)->count() ?: (int) $firma->calisan_sayisi,
                'risk_kaydi' => $maddeler->count(),
            ],
            'baskin' => $baskin,
            'dagilim' => $dagilim,
            'satirlar' => $satirlar,
            'en_yuksek' => $satirlar->filter(fn ($s) => $s['dominans'] > 0)->sortByDesc('dominans')->take(10)->values(),
            'maruz_toplam' => $maddeler->sum(fn ($m) => (int) $m->maruz_kisi),
            'maruz_eksik' => $maddeler->filter(fn ($m) => blank($m->maruz_kisi))->count(),
            'tur_tahmin' => $satirlar->where('tahmin', true)->count(),
            'kaynaklar' => static::kaynaklar($firma, $maddeler),
        ];
    }

    /**
     * NACE bölümüne göre olası tehlike kaynakları ve risk kayıtlarında
     * karşılığı olup olmadığı (başlıktaki anlamlı kelimelerin kökü aranır).
     *
     * @return array<int, array{baslik: string, eslesen: int}>
     */
    public static function kaynaklar(Firma $firma, Collection $maddeler): array
    {
        $grup = RiskMerkeziVerisi::naceGrubu($firma);

        if (! $grup) {
            return [];
        }

        $metinler = $maddeler->map(fn ($m) => static::kucult(implode(' ', [$m->bolum, $m->faaliyet, $m->tehlike, $m->risk])));

        return collect($grup['basliklar'])->map(function (string $baslik) use ($metinler) {
            $kokler = collect(preg_split('/[^\p{L}]+/u', static::kucult($baslik), -1, PREG_SPLIT_NO_EMPTY))
                ->filter(fn ($k) => mb_strlen($k) >= 4 && ! in_array($k, ['için', 'karşı', 'olan', 'veya', 'ile'], true))
                ->map(fn ($k) => mb_substr($k, 0, 5))
                // genel kelimeler (çalışma, koruma, geçici…) her kayda uyar, eşleşmeyi anlamsızlaştırır
                ->reject(fn ($k) => in_array($k, ['çalış', 'korum', 'geçic', 'faali', 'işler', 'kontr', 'genel', 'alanl', 'kayna', 'malze'], true));

            $eslesen = $kokler->isEmpty() ? 0 : $metinler->filter(fn ($t) => $kokler->contains(fn ($k) => str_contains($t, $k)))->count();

            return ['baslik' => $baslik, 'eslesen' => $eslesen];
        })->all();
    }
}
