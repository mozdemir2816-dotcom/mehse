<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Risk Sihirbazı "Yapay Zeka" adımında `RiskUretici`nin kural tabanlı
 * sonuçlarına ek olarak Gemini'den işyerine özel risk önerisi ister
 * (isgpratik 103-115.jpg akışının gerçek LLM entegrasyonu). API anahtarı
 * tanımlı değilse veya istek başarısız olursa sessizce boş dizi döner —
 * sihirbaz kural tabanlı sonuçlarla çalışmaya devam eder.
 */
class GeminiRiskDanismani
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array<int, string>  $altKategoriler
     * @param  array<string, string|array<int, string>>  $cevaplar  soru anahtarı => değer(ler)
     * @param  array<int, string>  $mevcutTehlikeler  zaten üretilmiş aday tehlike metinleri (mükerrer istenmez)
     * @return array<int, array<string, mixed>> ham risk maddeleri (RiskUretici::normalize şemasına uyar)
     */
    public static function oner(string $sektorAdi, array $altKategoriler, array $cevaplar, array $mevcutTehlikeler): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        try {
            $yanit = Http::timeout(45)->post(
                static::endpoint(),
                static::istekGovdesi($sektorAdi, $altKategoriler, $cevaplar, $mevcutTehlikeler),
            );

            if ($yanit->failed()) {
                Log::warning('Gemini risk önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $adaylar = json_decode((string) $metin, true);

            if (! is_array($adaylar)) {
                return [];
            }

            return array_values(array_filter(
                $adaylar,
                fn ($a) => is_array($a) && filled($a['tehlike'] ?? null),
            ));
        } catch (Throwable $e) {
            Log::warning('Gemini risk önerisi istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $sektorAdi, array $altKategoriler, array $cevaplar, array $mevcutTehlikeler): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($sektorAdi, $altKategoriler, $cevaplar, $mevcutTehlikeler)]],
            ]],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'bolum' => ['type' => 'STRING'],
                            'faaliyet' => ['type' => 'STRING'],
                            'tehlike' => ['type' => 'STRING'],
                            'risk' => ['type' => 'STRING'],
                            'mevcut_onlem' => ['type' => 'STRING'],
                            'oneri' => ['type' => 'STRING'],
                            'mevzuat' => ['type' => 'STRING'],
                            'olasilik' => ['type' => 'INTEGER'],
                            'siddet' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['tehlike', 'risk', 'oneri'],
                    ],
                ],
            ],
        ];
    }

    private static function istem(string $sektorAdi, array $altKategoriler, array $cevaplar, array $mevcutTehlikeler): string
    {
        $altKategoriMetni = $altKategoriler ? implode(', ', $altKategoriler) : '(seçilmedi)';

        $cevapMetni = collect($cevaplar)
            ->map(fn ($deger, $anahtar) => $anahtar.': '.(is_array($deger) ? implode(', ', $deger) : $deger))
            ->implode("\n") ?: '(cevap yok)';

        $mevcutMetni = $mevcutTehlikeler ? ('- '.implode("\n- ", $mevcutTehlikeler)) : '(yok)';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki işyeri için risk değerlendirmesine
        eklenecek 3-6 adet EK risk maddesi öner. Listede zaten olan riskleri TEKRARLAMA.

        Sektör: {$sektorAdi}
        Alt kategoriler: {$altKategoriMetni}
        İşyeri anket cevapları:
        {$cevapMetni}

        Listede zaten olan riskler (tekrar önerme):
        {$mevcutMetni}

        Her öneri için: bölüm/faaliyet, tehlike, doğurduğu risk, varsa mevcut önlem,
        somut öneri (Türk mevzuatına atıfla), ilgili mevzuat maddesi, 1-5 arası olasılık
        ve şiddet puanı ver. Sadece bu işyerine özgü, somut ve uygulanabilir riskler öner;
        genel geçer ifadelerden kaçın. Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;
    }
}
