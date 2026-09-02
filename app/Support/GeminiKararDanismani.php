<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kurul Toplantısı "Yapay Zeka Karar Yaz" butonu — bir gündem maddesi
 * metninden Gemini'ye kısa, uygulanabilir bir karar metni önerisi ister
 * (isgpratik yardım/kurul-toplantisi rehberi). API anahtarı tanımlı değilse
 * veya istek başarısız olursa sessizce null döner — kullanıcı elle yazar.
 */
class GeminiKararDanismani
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public static function oner(string $gundemMaddesi, ?string $firmaSektoru = null): ?string
    {
        if (! static::aktifMi() || blank($gundemMaddesi)) {
            return null;
        }

        try {
            $yanit = Http::timeout(45)->post(static::endpoint(), static::istekGovdesi($gundemMaddesi, $firmaSektoru));

            if ($yanit->failed()) {
                Log::warning('Gemini karar önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return null;
            }

            $metin = trim((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'));

            return $metin ?: null;
        } catch (Throwable $e) {
            Log::warning('Gemini karar önerisi istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $gundemMaddesi, ?string $firmaSektoru): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($gundemMaddesi, $firmaSektoru)]],
            ]],
            'generationConfig' => ['temperature' => 0.4],
        ];
    }

    private static function istem(string $gundemMaddesi, ?string $firmaSektoru): string
    {
        $sektorSatiri = $firmaSektoru ? "İşyeri sektörü: {$firmaSektoru}\n" : '';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki İSG Kurulu gündem maddesi için
        TEK bir, kısa (1-2 cümle), somut ve uygulanabilir karar metni öner.

        {$sektorSatiri}Gündem maddesi: {$gundemMaddesi}

        Yanıtı SADECE karar metni olarak ver — başlık, numaralandırma veya açıklama ekleme.
        PROMPT;
    }
}
