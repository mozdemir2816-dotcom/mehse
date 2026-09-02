<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Eğitim Soruları sayfası "AI ile 10 Soru Üret" butonu — sektör + zorluğa göre
 * Gemini'den çoktan seçmeli İSG sınav sorusu ister (isgpratik 69-70.jpg).
 * API anahtarı yoksa/istek başarısızsa boş dizi döner — kullanıcı elle ekler.
 */
class GeminiSoruUretici
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array<int, array{soru: string, secenekler: array<int, string>, dogru_index: int}> */
    public static function uret(string $sektorAdi, string $zorlukEtiketi, int $adet = 10): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        try {
            $yanit = Http::timeout(45)->post(static::endpoint(), static::istekGovdesi($sektorAdi, $zorlukEtiketi, $adet));

            if ($yanit->failed()) {
                Log::warning('Gemini soru üretimi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $sorular = json_decode((string) $metin, true);

            if (! is_array($sorular)) {
                return [];
            }

            return array_values(array_filter($sorular, fn ($s) => is_array($s)
                && filled($s['soru'] ?? null)
                && is_array($s['secenekler'] ?? null)
                && count($s['secenekler']) === 4
                && is_int($s['dogru_index'] ?? null)
                && $s['dogru_index'] >= 0 && $s['dogru_index'] <= 3));
        } catch (Throwable $e) {
            Log::warning('Gemini soru üretimi istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $sektorAdi, string $zorlukEtiketi, int $adet): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($sektorAdi, $zorlukEtiketi, $adet)]],
            ]],
            'generationConfig' => [
                'temperature' => 0.5,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'soru' => ['type' => 'STRING'],
                            'secenekler' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'dogru_index' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['soru', 'secenekler', 'dogru_index'],
                    ],
                ],
            ],
        ];
    }

    private static function istem(string $sektorAdi, string $zorlukEtiketi, int $adet): string
    {
        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. "{$sektorAdi}" sektöründeki bir işyerinde
        çalışanlara verilen temel İSG eğitiminin ardından uygulanacak, {$adet} adet
        çoktan seçmeli sınav sorusu hazırla. Zorluk seviyesi: {$zorlukEtiketi}.

        Her soru için tam olarak 4 seçenek ver (dogru_index 0-3 arası, doğru şıkkın
        indeksi). Sorular Türkçe, sektöre özgü, net ve tek doğru cevaplı olmalı.
        Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;
    }
}
