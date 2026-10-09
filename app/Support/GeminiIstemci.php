<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tüm Gemini sınıflarının ortak generateContent çağrısı. Ana model geçici olarak
 * yoğunsa (503 "high demand", 429 kota, 500) sıradaki yedek modelle aynı istek
 * tekrarlanır — 09.10.2026'da gemini-3.6-flash 503 verdiği için Talimat ve Saha
 * Raporu "AI ile Üret" boş dönüyordu. Zaman aşımında yedek denenmez (süre katlanmasın).
 */
class GeminiIstemci
{
    /** Yedek modele geçilen geçici hata kodları. */
    private const GECICI = [429, 500, 503];

    /** @return array<int, string> denenecek modeller, sırasıyla */
    public static function modeller(): array
    {
        return collect([config('services.gemini.model', 'gemini-3.6-flash')])
            ->concat(config('services.gemini.yedek_modeller', []))
            ->filter()->unique()->values()->all();
    }

    /** @param  array<string, mixed>  $govde */
    public static function post(array $govde, int $timeout, ?int $connectTimeout = null): Response
    {
        $modeller = self::modeller();

        // Yedekli deneme paylaşımlı sunucunun 30 sn PHP sınırını aşabilir (yerelde 42 sn ölçüldü).
        @set_time_limit($timeout * count($modeller) + 30);

        foreach ($modeller as $i => $model) {
            $istek = Http::timeout($timeout);
            if ($connectTimeout) {
                $istek = $istek->connectTimeout($connectTimeout);
            }

            $yanit = $istek->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key'),
                $govde
            );

            if (! in_array($yanit->status(), self::GECICI, true) || $i === count($modeller) - 1) {
                return $yanit;
            }

            Log::info('Gemini modeli geçici hata verdi, yedek modele geçiliyor', ['model' => $model, 'durum' => $yanit->status()]);
        }

        throw new ConnectionException('Gemini modeli tanımlı değil');
    }
}
