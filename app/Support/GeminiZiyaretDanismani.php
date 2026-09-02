<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ziyaret Programı "AI ile Amaç Öner" butonu — firma bilgisinden (sektör,
 * tehlike sınıfı, ay) o ay için kısa bir ziyaret amacı/kapsamı önerisi
 * ister. API anahtarı tanımlı değilse veya istek başarısız olursa sessizce
 * null döner — kullanıcı elle yazar (GeminiKararDanismani ile aynı desen).
 */
class GeminiZiyaretDanismani
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public static function oner(string $ayAdi, ?string $firmaSektoru, ?string $tehlikeSinifi): ?string
    {
        if (! static::aktifMi()) {
            return null;
        }

        try {
            $yanit = Http::timeout(45)->post(static::endpoint(), static::istekGovdesi($ayAdi, $firmaSektoru, $tehlikeSinifi));

            if ($yanit->failed()) {
                Log::warning('Gemini ziyaret önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return null;
            }

            $metin = trim((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'));

            return $metin ?: null;
        } catch (Throwable $e) {
            Log::warning('Gemini ziyaret önerisi istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $ayAdi, ?string $firmaSektoru, ?string $tehlikeSinifi): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($ayAdi, $firmaSektoru, $tehlikeSinifi)]],
            ]],
            'generationConfig' => ['temperature' => 0.4],
        ];
    }

    private static function istem(string $ayAdi, ?string $firmaSektoru, ?string $tehlikeSinifi): string
    {
        $sektorSatiri = $firmaSektoru ? "İşyeri sektörü: {$firmaSektoru}\n" : '';
        $tehlikeSatiri = $tehlikeSinifi ? "Tehlike sınıfı: {$tehlikeSinifi}\n" : '';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki işyeri için {$ayAdi} ayında
        yapılacak saha ziyaretinin amacını/kapsamını TEK kısa cümleyle öner.

        {$sektorSatiri}{$tehlikeSatiri}
        Yanıtı SADECE öneri cümlesi olarak ver — başlık, numaralandırma veya açıklama ekleme.
        PROMPT;
    }
}
