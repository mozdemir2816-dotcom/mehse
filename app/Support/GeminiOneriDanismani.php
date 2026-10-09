<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tespit ve Öneri Defteri "Yapay Zekadan Öneri Al" butonu — serbest metinle
 * yazılan bir tespitten Gemini'ye kısa, uygulanabilir bir öneri metni ister
 * (isgpratik 66.jpg). API anahtarı yoksa/istek başarısızsa null döner.
 */
class GeminiOneriDanismani
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public static function oner(string $tespit): ?string
    {
        if (! static::aktifMi() || blank($tespit)) {
            return null;
        }

        try {
            $yanit = GeminiIstemci::post(static::istekGovdesi($tespit), 45);

            if ($yanit->failed()) {
                Log::warning('Gemini tespit önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return null;
            }

            $metin = trim((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'));

            return $metin ?: null;
        } catch (Throwable $e) {
            Log::warning('Gemini tespit önerisi istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $tespit): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($tespit)]],
            ]],
            'generationConfig' => ['temperature' => 0.4],
        ];
    }

    private static function istem(string $tespit): string
    {
        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki tespit için TEK, kısa (1-2
        cümle), somut ve uygulanabilir bir öneri/düzeltici faaliyet metni yaz.

        Tespit: {$tespit}

        Yanıtı SADECE öneri metni olarak ver — başlık, numaralandırma veya açıklama ekleme.
        PROMPT;
    }
}
