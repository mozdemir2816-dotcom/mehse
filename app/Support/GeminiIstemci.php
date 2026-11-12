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
    /** Yedek modele geçilen hata kodları — 404: model Google tarafında kapatılmış
     *  (15.10.2026 gemini-2.5-flash "no longer available"). */
    private const GECICI = [404, 429, 500, 503];

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
        // "Gemini'de elle yap" ile yapıştırılmış yanıt varsa API'ye gidilmez (GeminiElleMod, 12.11.2026).
        if ($elle = GeminiElleMod::kayitliYanit($govde)) {
            return $elle;
        }

        try {
            $yanit = self::gonder($govde, $timeout, $connectTimeout);
        } catch (ConnectionException $e) {
            GeminiElleMod::hazirla($govde);

            throw $e;
        }

        if ($yanit->failed()) {
            GeminiElleMod::hazirla($govde, $yanit->status());
        }

        return $yanit;
    }

    /** @param  array<string, mixed>  $govde */
    private static function gonder(array $govde, int $timeout, ?int $connectTimeout): Response
    {
        $modeller = self::modeller();

        // Yedekli deneme paylaşımlı sunucunun 30 sn PHP sınırını aşabilir (yerelde 42 sn ölçüldü).
        @set_time_limit(app()->runningInConsole() ? 0 : $timeout * count($modeller) + 30);   // komut satırında (zamanlayıcı, test) sınırsız kalır

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
