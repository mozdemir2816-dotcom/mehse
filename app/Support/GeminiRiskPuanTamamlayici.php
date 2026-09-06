<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kütüphaneden aktarılan veya elle eklenip Olasılık/Şiddet(/Frekans) puanı
 * girilmemiş risk maddelerini Gemini ile tamamlar — kullanıcı isteği:
 * "olasılık şiddet ve frekans olmayan kayıtları otomatik yapay zekaya
 * yaptırarak kaydet". `GeminiRiskDanismani`'nin aksine YENİ risk ÜRETMEZ,
 * zaten var olan bir tehlike/risk metnine puan atar. API anahtarı yoksa veya
 * istek başarısız olursa sessizce null döner — madde puansız kalır, kullanıcı
 * elle girebilir.
 */
class GeminiRiskPuanTamamlayici
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array{olasilik: float, siddet: float, frekans: ?float}|null */
    public static function oner(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, string $yontem): ?array
    {
        if (! static::aktifMi()) {
            return null;
        }

        $fk = $yontem === 'fine_kinney';

        try {
            $yanit = Http::timeout(45)->post(
                static::endpoint(),
                static::istekGovdesi($tehlike, $risk, $bolum, $faaliyet, $fk),
            );

            if ($yanit->failed()) {
                Log::warning('Gemini risk puanı önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return null;
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $sonuc = json_decode((string) $metin, true);

            if (! is_array($sonuc) || ! isset($sonuc['olasilik'], $sonuc['siddet'])) {
                return null;
            }

            return [
                'olasilik' => static::enYakinDeger($sonuc['olasilik'], $yontem, 'olasilik'),
                'siddet' => static::enYakinDeger($sonuc['siddet'], $yontem, 'siddet'),
                'frekans' => $fk ? static::enYakinDeger($sonuc['frekans'] ?? 1, $yontem, 'frekans') : null,
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini risk puanı önerisi istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }

    /** LLM'in döndürdüğü sayı, ölçekteki İZİN VERİLEN değerlerden en yakınına yuvarlanır (ölçek dışı değer sızmasın diye). */
    private static function enYakinDeger(mixed $deger, string $yontem, string $eksen): float
    {
        $izinli = array_map('floatval', array_keys(RiskSkorlama::olcek($yontem, $eksen)));
        $sayi = (float) $deger;

        usort($izinli, fn (float $a, float $b) => abs($a - $sayi) <=> abs($b - $sayi));

        return $izinli[0] ?? $sayi;
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, bool $fk): array
    {
        $properties = [
            'olasilik' => ['type' => 'NUMBER'],
            'siddet' => ['type' => 'NUMBER'],
        ];
        $required = ['olasilik', 'siddet'];

        if ($fk) {
            $properties['frekans'] = ['type' => 'NUMBER'];
            $required[] = 'frekans';
        }

        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($tehlike, $risk, $bolum, $faaliyet, $fk)]],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => ['type' => 'OBJECT', 'properties' => $properties, 'required' => $required],
            ],
        ];
    }

    private static function istem(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, bool $fk): string
    {
        $yontem = $fk ? 'fine_kinney' : 'matris_5x5';

        $olcekMetni = fn (string $eksen) => collect(RiskSkorlama::olcek($yontem, $eksen))
            ->map(fn ($ad, $deger) => "{$deger}: {$ad}")
            ->implode("\n");

        $frekansBlok = $fk ? "\nFrekans (maruz kalma) ölçeği — SADECE bu değerlerden seç:\n".$olcekMetni('frekans')."\n" : '';
        $frekansEtiket = $fk ? '/frekans' : '';
        $olasilikBlok = $olcekMetni('olasilik');
        $siddetBlok = $olcekMetni('siddet');

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki tehlike/risk maddesi için
        {$yontem} yöntemine göre olasılık{$frekansEtiket}/şiddet puanı belirle.

        Bölüm/Ünite: {$bolum}
        Faaliyet: {$faaliyet}
        Tehlike: {$tehlike}
        Risk (olası sonuç): {$risk}

        Olasılık ölçeği — SADECE bu değerlerden seç:
        {$olasilikBlok}
        {$frekansBlok}
        Şiddet ölçeği — SADECE bu değerlerden seç:
        {$siddetBlok}

        Yanıtı SADECE JSON nesnesi olarak ver (ör. {"olasilik": 3, "siddet": 7}).
        PROMPT;
    }
}
