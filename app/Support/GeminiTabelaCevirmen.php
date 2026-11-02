<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tabela editörü "🤖 AI ile Çevir" (02.11.2026): Türkçe ikaz başlığı ve açıklamasını ANSI Z535 / OSHA
 * güvenlik levhası İngilizcesine çevirir (kısa, emir kipinde, teknik). Sinyal sözcüğü sabit eşlemeyle
 * (TEHLİKE → DANGER …) çevrilir; yapay zekâya bırakılmaz.
 */
class GeminiTabelaCevirmen
{
    public const SINYALLER = [
        'TEHLİKE' => 'DANGER', 'UYARI' => 'WARNING', 'DİKKAT' => 'CAUTION', 'BİLGİ' => 'NOTICE',
        'GÜVENLİK' => 'SAFETY INSTRUCTIONS', 'ACİL DURUM' => 'EMERGENCY', 'YASAK' => 'PROHIBITED',
        'ZORUNLU' => 'MANDATORY', 'YANGIN' => 'FIRE', 'İLK YARDIM' => 'FIRST AID',
    ];

    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public static function sinyal(?string $tr): ?string
    {
        // Türkçe büyük harf: i → İ, ı → I ("Dikkat" → "DİKKAT").
        $tr = mb_strtoupper(strtr(trim((string) $tr), ['i' => 'İ', 'ı' => 'I']), 'UTF-8');

        return self::SINYALLER[$tr] ?? null;
    }

    /**
     * @return array{baslik: ?string, aciklama: ?string, sinyal: ?string, hata: ?string}
     */
    public static function cevir(?string $baslik, ?string $aciklama, ?string $sinyal = null): array
    {
        $baslik = trim((string) $baslik);
        $aciklama = trim((string) $aciklama);
        $bos = ['baslik' => null, 'aciklama' => null, 'sinyal' => static::sinyal($sinyal)];

        if ($baslik === '' && $aciklama === '') {
            return [...$bos, 'hata' => 'Çevrilecek Türkçe başlık veya açıklama yok.'];
        }
        if (! static::aktifMi()) {
            return [...$bos, 'hata' => 'Yapay zekâ anahtarı (Gemini) tanımlı değil.'];
        }

        $girdi = json_encode(['baslik' => $baslik, 'aciklama' => $aciklama], JSON_UNESCAPED_UNICODE);
        $istem = <<<PROMPT
        Sen ANSI Z535 ve OSHA standartlarına göre iş güvenliği levhası metni yazan teknik bir çevirmensin.
        Aşağıdaki Türkçe güvenlik levhası metnini levha İngilizcesine çevir:
        - baslik: BÜYÜK HARF, kısa ve net (ör. "YÜKSEK GERİLİM - 380 VOLT" → "HIGH VOLTAGE - 380 V").
        - aciklama: kısa emir kipi cümleler, standart levha ifadeleri (ör. "AUTHORIZED PERSONNEL ONLY",
          "LOCK OUT AND TAG OUT BEFORE SERVICING", "HARD HAT REQUIRED"). Kelime kelime değil, anlamca çevir.
        Sayıları, birimleri ve teknik kısaltmaları (LOTO, KKD → PPE) koru. Boş verilen alanı boş bırak.

        Metin: {$girdi}

        Yanıtı SADECE JSON olarak ver.
        PROMPT;

        try {
            $yanit = GeminiIstemci::post([
                'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => ['type' => 'OBJECT', 'properties' => ['baslik' => ['type' => 'STRING'], 'aciklama' => ['type' => 'STRING']], 'required' => ['baslik', 'aciklama']],
                ],
            ], 30, 5);

            if ($yanit->failed()) {
                Log::warning('Gemini tabela çevirisi başarısız', ['durum' => $yanit->status()]);

                return [...$bos, 'hata' => 'Yapay zekâ şu an yanıt vermedi, birazdan tekrar deneyin.'];
            }

            $s = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
            $temiz = fn ($v, int $sinir) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, $sinir) ?: null;

            return [
                'baslik' => $baslik !== '' ? mb_strtoupper((string) $temiz($s['baslik'] ?? '', 120), 'UTF-8') ?: null : null,
                'aciklama' => $aciklama !== '' ? $temiz($s['aciklama'] ?? '', 400) : null,
                'sinyal' => $bos['sinyal'],
                'hata' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini tabela çevirisi istisna', ['hata' => $e->getMessage()]);

            return [...$bos, 'hata' => 'Yapay zekâya bağlanılamadı, birazdan tekrar deneyin.'];
        }
    }
}
