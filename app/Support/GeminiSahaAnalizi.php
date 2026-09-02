<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * AI Saha Analizi "Fotoğrafları AI ile Analiz Et" — saha fotoğraflarını
 * Gemini vision'a gönderir, her fotoğraftaki uygunsuzluk için bulgu
 * (tespit + öneriler + yasal gerekçe + risk derecesi) üretir (isgpratik
 * AI SAHA ANALİZİ/1-3.jpg). API anahtarı yoksa/istek başarısızsa boş dizi
 * döner — kullanıcı elle madde ekler.
 */
class GeminiSahaAnalizi
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array<int, string>  $fotografYollari  'public' diskindeki dosya yolları (1'den başlayan sırayla)
     * @return array<int, array{foto_index: int, bina_bolge: ?string, kategori: ?string, tespit: string, oneriler: array<int, string>, yasal_gerekce: ?string, risk_derecesi: int}>
     */
    public static function analizEt(array $fotografYollari, ?string $baglamNotu = null): array
    {
        if (! static::aktifMi() || ! $fotografYollari) {
            return [];
        }

        try {
            $yanit = Http::timeout(90)->post(static::endpoint(), static::istekGovdesi($fotografYollari, $baglamNotu));

            if ($yanit->failed()) {
                Log::warning('Gemini saha analizi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $bulgular = json_decode((string) $metin, true);

            if (! is_array($bulgular)) {
                return [];
            }

            return array_values(array_filter(array_map(function ($b) use ($fotografYollari) {
                if (! is_array($b) || blank($b['tespit'] ?? null)) {
                    return null;
                }

                $fotoIndex = (int) ($b['foto_index'] ?? 1);

                return [
                    'foto_index' => $fotoIndex,
                    'foto_yolu' => $fotografYollari[$fotoIndex - 1] ?? ($fotografYollari[array_key_first($fotografYollari)] ?? null),
                    'bina_bolge' => $b['bina_bolge'] ?? null,
                    'kategori' => $b['kategori'] ?? null,
                    'tespit' => (string) $b['tespit'],
                    'oneriler' => is_array($b['oneriler'] ?? null) ? array_values($b['oneriler']) : [],
                    'yasal_gerekce' => $b['yasal_gerekce'] ?? null,
                    'risk_derecesi' => max(1, min(4, (int) ($b['risk_derecesi'] ?? 3))),
                ];
            }, $bulgular)));
        } catch (Throwable $e) {
            Log::warning('Gemini saha analizi istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /**
     * @param  array<int, string>  $fotografYollari
     * @return array<string, mixed>
     */
    private static function istekGovdesi(array $fotografYollari, ?string $baglamNotu): array
    {
        $parts = [['text' => static::istem($baglamNotu, count($fotografYollari))]];

        foreach (array_values($fotografYollari) as $i => $yol) {
            $tamYol = Storage::disk('public')->path($yol);

            if (! is_file($tamYol)) {
                continue;
            }

            $parts[] = ['text' => 'Fotoğraf '.($i + 1).':'];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => mime_content_type($tamYol) ?: 'image/jpeg',
                    'data' => base64_encode((string) file_get_contents($tamYol)),
                ],
            ];
        }

        return [
            'contents' => [[
                'role' => 'user',
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'foto_index' => ['type' => 'INTEGER'],
                            'bina_bolge' => ['type' => 'STRING'],
                            'kategori' => ['type' => 'STRING'],
                            'tespit' => ['type' => 'STRING'],
                            'oneriler' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'yasal_gerekce' => ['type' => 'STRING'],
                            'risk_derecesi' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['foto_index', 'tespit', 'oneriler', 'risk_derecesi'],
                    ],
                ],
            ],
        ];
    }

    private static function istem(?string $baglamNotu, int $fotoSayisi): string
    {
        $baglam = filled($baglamNotu) ? "\nBağlam notu (kullanıcı verdi): {$baglamNotu}\n" : '';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Sana {$fotoSayisi} adet iş yeri saha
        fotoğrafı veriliyor. Her fotoğrafı İSG açısından tehlike/uygunsuzluk yönünden
        dikkatle incele.
        {$baglam}
        Tespit ettiğin HER uygunsuzluk için bir bulgu üret:
        - foto_index: bulgunun ait olduğu fotoğrafın sırası (1'den başlar).
        - bina_bolge: fotoğrafta görülebiliyorsa bina/bölge adı tahmini (yoksa boş bırak).
        - kategori: kısa bir kategori etiketi (örn. "Makine Güvenliği", "Düzen/Temizlik", "KKD Eksikliği", "Elektrik Güvenliği").
        - tespit: uygunsuzluğun/tehlikenin detaylı açıklaması (2-4 cümle, somut ve teknik).
        - oneriler: alınması gereken düzeltici/önleyici faaliyetleri madde madde listeleyen dizi (her biri tek cümle).
        - yasal_gerekce: ilgili mevzuat/yönetmelik maddesi.
        - risk_derecesi: 1 (Çok Yüksek), 2 (Yüksek), 3 (Orta) veya 4 (Düşük).

        Fotoğrafta belirgin bir tehlike/uygunsuzluk yoksa o fotoğraf için bulgu üretme.
        Yanıtı SADECE JSON dizisi olarak ver — başka açıklama ekleme.
        PROMPT;
    }
}
