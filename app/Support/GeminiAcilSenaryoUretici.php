<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Acil Durum Planı — "Listede Olmayan Özel Senaryo Ekle (Yapay Zekâ)" (kullanıcı 02.11.2026).
 * Başlıktan (ör. "Lityum İyon Batarya Termal Kaçağı") EK-1 düzeninde bir senaryo üretir: tanım,
 * müdahale adımları, olay sonrası, önleyici tedbirler, kişisel davranış ve KKD. Uzman önizlemede
 * düzeltip onaylamadan plana hiçbir şey eklenmez.
 */
class GeminiAcilSenaryoUretici
{
    /** Senaryodaki liste alanları => PDF'teki başlık (mudahale / olay_sonrasi görünümde sabit başlıklı). */
    public const LISTELER = [
        'mudahale' => 'MÜDAHALE ADIMLARI',
        'olay_sonrasi' => 'OLAY SONRASI',
        'onleyici' => 'ÖNLEYİCİ TEDBİRLER',
        'kkd' => 'KİŞİSEL DAVRANIŞ VE KKD',
    ];

    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array{senaryo: ?array, hata: ?string} */
    public static function uret(string $baslik, ?string $sektor = null): array
    {
        $baslik = trim($baslik);
        if (mb_strlen($baslik) < 4) {
            return ['senaryo' => null, 'hata' => 'Senaryo başlığını biraz daha açık yazın.'];
        }
        if (! static::aktifMi()) {
            return ['senaryo' => null, 'hata' => 'Yapay zekâ anahtarı (Gemini) tanımlı değil.'];
        }

        try {
            $yanit = GeminiIstemci::post(static::govde($baslik, $sektor), 45);

            if ($yanit->failed()) {
                Log::warning('Gemini acil senaryo başarısız', ['durum' => $yanit->status()]);

                return ['senaryo' => null, 'hata' => 'Yapay zekâ şu an yanıt vermedi, birazdan tekrar deneyin.'];
            }

            $ham = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
            $senaryo = is_array($ham) ? static::normalize([
                'ad' => $ham['baslik'] ?? $baslik,
                'aciklama' => $ham['tanim'] ?? null,
                'mudahale' => $ham['mudahale_adimlari'] ?? [],
                'olay_sonrasi' => $ham['olay_sonrasi'] ?? [],
                'onleyici' => $ham['onleyici_tedbirler'] ?? [],
                'kkd' => $ham['kisisel_davranis_ve_kkd'] ?? [],
            ]) : null;

            return $senaryo && $senaryo['mudahale']
                ? ['senaryo' => $senaryo, 'hata' => null]
                : ['senaryo' => null, 'hata' => 'Bu başlık için senaryo üretilemedi; başlığı daha açık yazıp tekrar deneyin.'];
        } catch (Throwable $e) {
            Log::warning('Gemini acil senaryo istisna', ['hata' => $e->getMessage()]);

            return ['senaryo' => null, 'hata' => 'Yapay zekâya bağlanılamadı, birazdan tekrar deneyin.'];
        }
    }

    /**
     * Önizlemeden / kayıttan gelen senaryoyu sınırlar. Listeler dizi ya da satır satır metin olabilir.
     *
     * @return array{anahtar: string, ad: string, aciklama: ?string, mudahale: array, olay_sonrasi: array, onleyici: array, kkd: array}|null
     */
    public static function normalize(array $s): ?array
    {
        $ad = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($s['ad'] ?? ''))), 0, 120);
        if ($ad === '') {
            return null;
        }

        $liste = fn ($x) => collect(is_array($x) ? $x : preg_split('/\R/u', (string) $x))
            ->map(fn ($m) => mb_substr(trim(preg_replace('/^\s*(?:[-•*]|\d+[.)])\s*/u', '', (string) $m)), 0, 500))
            ->filter()->take(15)->values()->all();

        return [
            'anahtar' => filled($s['anahtar'] ?? null) && str_starts_with((string) $s['anahtar'], 'ozel_')
                ? mb_substr((string) $s['anahtar'], 0, 60) : 'ozel_'.Str::slug($ad, '_').'_'.Str::lower(Str::random(4)),
            'ad' => $ad,
            'aciklama' => filled($s['aciklama'] ?? null) ? mb_substr(trim((string) $s['aciklama']), 0, 1500) : null,
            ...collect(array_keys(self::LISTELER))->mapWithKeys(fn ($k) => [$k => $liste($s[$k] ?? [])])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function govde(string $baslik, ?string $sektor): array
    {
        $sektorSatiri = $sektor ? "İşyerinin sektörü: {$sektor}\n" : '';

        $istem = <<<PROMPT
        Sen Türkiye'de çalışan A Sınıfı bir İş Güvenliği Uzmanısın. 6331 sayılı Kanun ve
        "İşyerlerinde Acil Durumlar Hakkında Yönetmelik" (Md.9-12) kapsamındaki acil durum planının
        EK-1 "Acil Durumlar ve Müdahale Yöntemleri" bölümü için bir senaryo sayfası hazırla.

        Senaryo başlığı: "{$baslik}"
        {$sektorSatiri}
        Alanlar:
        - baslik: senaryonun kısa, resmî başlığı (ör. "Lityum İyon Batarya Termal Kaçağı")
        - tanim: olayın ne olduğu, işyerinde nasıl ortaya çıkabileceği (2-4 cümle)
        - mudahale_adimlari: olay anında sırasıyla yapılacaklar (6-10 madde; alarm, 112 ihbarı,
          güvenli mesafe, tahliye ve toplanma, ekiplerin görevleri)
        - olay_sonrasi: olay sonrası yapılacaklar (4-6 madde; sayım, alanın güvenliği, kayıt,
          kök neden, planın gözden geçirilmesi)
        - onleyici_tedbirler: olayın oluşmaması için alınacak tedbirler (4-8 madde)
        - kisisel_davranis_ve_kkd: çalışanın kişisel davranışı ve kullanacağı KKD (3-6 madde)

        Kurallar: Yalnız eğitimli ve yetkili kişilerin müdahale edeceğini, can güvenliğinin
        malzemeden önce geldiğini ve acil çağrının 112 olduğunu esas al. Emin olmadığın madde
        numarası, standart ya da telefon numarası YAZMA. Maddeler kısa ve uygulanabilir olsun.
        Türkçe yaz. Yanıtı SADECE JSON nesnesi olarak ver.
        PROMPT;

        $metin = ['type' => 'STRING'];
        $dizi = ['type' => 'ARRAY', 'items' => $metin];

        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'baslik' => $metin, 'tanim' => $metin, 'mudahale_adimlari' => $dizi, 'olay_sonrasi' => $dizi,
                        'onleyici_tedbirler' => $dizi, 'kisisel_davranis_ve_kkd' => $dizi,
                    ],
                    'required' => ['baslik', 'tanim', 'mudahale_adimlari', 'olay_sonrasi', 'onleyici_tedbirler', 'kisisel_davranis_ve_kkd'],
                ],
            ],
        ];
    }
}
