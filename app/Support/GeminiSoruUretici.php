<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Soru Bankası / Eğitim Soruları "AI ile Soru Üret" — sektör + konu + zorluğa
 * göre Gemini'den çoktan seçmeli İSG sınav sorusu ister. Her soru için doğru
 * cevabın gerekçesi (aciklama) ve yasal/eğitsel dayanak (kaynak) da istenir.
 * API anahtarı yoksa/istek başarısızsa boş dizi döner — kullanıcı elle ekler.
 */
class GeminiSoruUretici
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @return array<int, array{soru: string, secenekler: array<int, string>, dogru_index: int, kaynak: ?string, aciklama: ?string}>
     */
    public static function uret(string $sektorAdi, string $zorlukEtiketi, int $adet = 10, ?string $konuAdi = null): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        try {
            $yanit = Http::timeout(45)->post(static::endpoint(), static::istekGovdesi($sektorAdi, $zorlukEtiketi, $adet, $konuAdi));

            if ($yanit->failed()) {
                Log::warning('Gemini soru üretimi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $sorular = json_decode((string) $metin, true);

            if (! is_array($sorular)) {
                return [];
            }

            return array_values(array_map(
                fn ($s) => [
                    'soru' => $s['soru'],
                    'secenekler' => array_values($s['secenekler']),
                    'dogru_index' => $s['dogru_index'],
                    'kaynak' => filled($s['kaynak'] ?? null) ? $s['kaynak'] : null,
                    'aciklama' => filled($s['aciklama'] ?? null) ? $s['aciklama'] : null,
                ],
                array_filter($sorular, fn ($s) => is_array($s)
                    && filled($s['soru'] ?? null)
                    && is_array($s['secenekler'] ?? null)
                    && count($s['secenekler']) === 4
                    && is_int($s['dogru_index'] ?? null)
                    && $s['dogru_index'] >= 0 && $s['dogru_index'] <= 3),
            ));
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
    private static function istekGovdesi(string $sektorAdi, string $zorlukEtiketi, int $adet, ?string $konuAdi): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($sektorAdi, $zorlukEtiketi, $adet, $konuAdi)]],
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
                            'aciklama' => ['type' => 'STRING'],
                            'kaynak' => ['type' => 'STRING'],
                        ],
                        'required' => ['soru', 'secenekler', 'dogru_index'],
                    ],
                ],
            ],
        ];
    }

    private static function istem(string $sektorAdi, string $zorlukEtiketi, int $adet, ?string $konuAdi): string
    {
        $konuCumlesi = $konuAdi
            ? "Sorular \"{$konuAdi}\" konusuna odaklanmalı."
            : 'Sorular temel İSG konularını kapsamalı.';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. "{$sektorAdi}" sektöründeki bir işyerinde
        çalışanlara verilen İSG eğitiminin ardından uygulanacak, {$adet} adet çoktan
        seçmeli sınav sorusu hazırla. Zorluk seviyesi: {$zorlukEtiketi}. {$konuCumlesi}

        Her soru için:
        - tam olarak 4 seçenek (dogru_index 0-3 arası, doğru şıkkın indeksi),
        - "aciklama": doğru cevabın 1-2 cümlelik gerekçesi (cevap anahtarına yazılır),
        - "kaynak": ilgili mevzuat / yönetmelik maddesi veya eğitim başlığı (kısa).

        Sorular Türkçe, sektöre özgü, net ve tek doğru cevaplı olmalı.
        Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;
    }
}
