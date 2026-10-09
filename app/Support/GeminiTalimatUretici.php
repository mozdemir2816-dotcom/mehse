<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talimat Oluştur "AI ile Üret" — bir çalışma talimatı şablonu için (başlık +
 * kategori + KKD listesi) Gemini'den numaralı, adım adım güvenli çalışma
 * maddeleri ister (isgpratik 82-83.jpg). API anahtarı yoksa/istek
 * başarısızsa boş dizi döner — kullanıcı elle yazar.
 */
class GeminiTalimatUretici
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array<int, string>  $kkdler
     * @return array<int, string>
     */
    public static function uret(string $baslik, string $kategoriAdi, array $kkdler): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        try {
            $yanit = GeminiIstemci::post(static::istekGovdesi($baslik, $kategoriAdi, $kkdler), 45);

            if ($yanit->failed()) {
                Log::warning('Gemini talimat üretimi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $maddeler = json_decode((string) $metin, true);

            if (! is_array($maddeler)) {
                return [];
            }

            return array_values(array_filter($maddeler, fn ($m) => is_string($m) && filled($m)));
        } catch (Throwable $e) {
            Log::warning('Gemini talimat üretimi istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Kullanıcının bölümlü talimat düzeninde (Kalıp/Demir talimatları) içerik:
     * Amaç, Kapsam, Genel Kurallar, KKD, işe özgü bölümler, Yasaklar, Acil
     * Durum, Çalışan Yükümlülükleri. Taahhüt sistem tarafından eklenir.
     *
     * @param  array<int, string>  $kkdler
     * @return array<int, array{baslik: string, aciklama: string, maddeler: array<int, string>}>
     */
    public static function uretBolumlu(string $baslik, string $kategoriAdi, array $kkdler): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        $kkdMetni = $kkdler ? implode(', ', $kkdler) : '(işe göre sen belirle)';
        $istem = <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. "{$baslik}" ({$kategoriAdi} kategorisi)
        için bölümlü bir güvenli çalışma talimatı hazırla. Gerekli KKD: {$kkdMetni}.

        Bölümler şu sırayla olsun (başlıklar BÜYÜK HARF, numarasız):
        AMAÇ ve KAPSAM: yalnız "aciklama" (tek cümle, "Bu talimatın amacı; ..." / "Bu talimat; ... kapsar."), maddeler boş.
        GENEL KURALLAR; KİŞİSEL KORUYUCU DONANIMLAR (KKD) (aciklama: "Çalışanlar aşağıdaki ekipmanları kullanmak zorundadır:", maddeler KKD adları);
        ardından bu işe özgü 4-8 bölüm (ör. kurulum, söküm, malzeme taşıma, elektrik, çalışma ortamı);
        en sonda YASAKLAR, ACİL DURUM VE BİLDİRİM, ÇALIŞAN YÜKÜMLÜLÜKLERİ.
        Her bölümde 3-8 kısa, somut madde; "...malıdır/meli" kipinde. TAAHHÜT bölümü EKLEME.
        PROMPT;

        try {
            $yanit = GeminiIstemci::post([
                'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => ['type' => 'ARRAY', 'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'baslik' => ['type' => 'STRING'],
                            'aciklama' => ['type' => 'STRING'],
                            'maddeler' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        ],
                        'required' => ['baslik', 'maddeler'],
                    ]],
                ],
            ], 60);

            if ($yanit->failed()) {
                Log::warning('Gemini bölümlü talimat üretimi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return [];
            }

            $bolumler = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);

            return collect(is_array($bolumler) ? $bolumler : [])
                ->filter(fn ($b) => is_array($b) && filled($b['baslik'] ?? null))
                ->map(fn ($b) => [
                    'baslik' => trim((string) $b['baslik']),
                    'aciklama' => trim((string) ($b['aciklama'] ?? '')),
                    'maddeler' => array_values(array_filter((array) ($b['maddeler'] ?? []), fn ($m) => is_string($m) && filled($m))),
                ])
                ->reject(fn ($b) => str_contains($b['baslik'], 'TAAHH'))
                ->values()->all();
        } catch (Throwable $e) {
            Log::warning('Gemini bölümlü talimat üretimi istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $baslik, string $kategoriAdi, array $kkdler): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($baslik, $kategoriAdi, $kkdler)]],
            ]],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
                'responseSchema' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
        ];
    }

    private static function istem(string $baslik, string $kategoriAdi, array $kkdler): string
    {
        $kkdMetni = $kkdler ? implode(', ', $kkdler) : '(belirtilmedi)';

        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. "{$baslik}" ({$kategoriAdi} kategorisi)
        için 8-12 maddelik, adım adım, somut ve uygulanabilir bir güvenli çalışma
        talimatı hazırla. Gerekli KKD: {$kkdMetni}.

        Her madde tek bir kısa cümle olsun (numaralandırma ekleme, sadece madde
        metinlerini ver). Yanıtı SADECE JSON dizisi (string listesi) olarak ver.
        PROMPT;
    }
}
