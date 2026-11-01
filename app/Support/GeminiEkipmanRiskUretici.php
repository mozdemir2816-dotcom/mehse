<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Risk Sihirbazı — "Kütüphanede Olmayan Ekipman / Proses Ekle (Yapay Zekâ)".
 * Ekipman/proses adından Gemini ile 3-5 teknik risk maddesi üretir. Her madde hem
 * Fine-Kinney (O/F/Ş) hem 5x5 (O/Ş) puanıyla gelir; puanlar resmî ölçek değerlerine
 * yuvarlanır. Uzman maddeleri onaylamadan hiçbir şey analize eklenmez.
 */
class GeminiEkipmanRiskUretici
{
    public const FK_OLASILIK = [0.2, 0.5, 1, 3, 6, 10];

    public const FK_FREKANS = [0.5, 1, 2, 3, 6, 10];

    public const FK_SIDDET = [1, 3, 7, 15, 40, 100];

    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @return array{maddeler: array<int, array<string, mixed>>, hata: ?string}
     */
    public static function uret(string $ekipman, string $yontem): array
    {
        if (! static::aktifMi()) {
            return ['maddeler' => [], 'hata' => 'Yapay zekâ anahtarı (Gemini) tanımlı değil.'];
        }

        try {
            $yanit = GeminiIstemci::post(static::govde($ekipman, $yontem), 45);

            if ($yanit->failed()) {
                Log::warning('Gemini ekipman riski başarısız', ['durum' => $yanit->status()]);

                return ['maddeler' => [], 'hata' => 'Yapay zekâ şu an yanıt vermedi, birazdan tekrar deneyin.'];
            }

            $ham = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
            $maddeler = collect(is_array($ham) ? $ham : [])
                ->filter(fn ($m) => is_array($m) && filled($m['tehlike'] ?? null) && filled($m['oneri'] ?? null))
                ->take(5)
                ->map(fn (array $m) => static::normalize($m, $ekipman))
                ->values()->all();

            return ['maddeler' => $maddeler, 'hata' => $maddeler ? null : 'Bu ekipman için madde üretilemedi; adı daha açık yazıp tekrar deneyin.'];
        } catch (Throwable $e) {
            Log::warning('Gemini ekipman riski istisna', ['hata' => $e->getMessage()]);

            return ['maddeler' => [], 'hata' => 'Yapay zekâya bağlanılamadı, birazdan tekrar deneyin.'];
        }
    }

    /** @return array<string, mixed> sihirbazın madde alanları + `matris` (5x5) */
    public static function normalize(array $m, string $ekipman): array
    {
        $metin = fn ($k) => trim((string) ($m[$k] ?? '')) ?: null;
        $kok = $metin('kok_neden');
        $risk = $metin('risk');

        return [
            'bolum' => $metin('bolum') ?: $ekipman,
            'faaliyet' => $metin('faaliyet'),
            'tehlike' => $metin('tehlike'),
            'risk' => $kok ? 'Kök neden: '.$kok.($risk ? ' → '.$risk : '') : $risk,
            'mevcut_onlem' => $metin('mevcut_onlem'),
            'oneri' => $metin('oneri'),
            'mevzuat' => $metin('mevzuat'),
            'olasilik' => static::enYakin($m['fk_olasilik'] ?? null, self::FK_OLASILIK),
            'frekans' => static::enYakin($m['fk_frekans'] ?? null, self::FK_FREKANS),
            'siddet' => static::enYakin($m['fk_siddet'] ?? null, self::FK_SIDDET),
            'matris' => [
                'olasilik' => max(1, min(5, (int) round((float) ($m['x5_olasilik'] ?? 3)))),
                'siddet' => max(1, min(5, (int) round((float) ($m['x5_siddet'] ?? 3)))),
            ],
        ];
    }

    private static function enYakin(mixed $deger, array $olcek): float
    {
        $d = is_numeric($deger) ? (float) $deger : $olcek[(int) floor(count($olcek) / 2)];

        return (float) collect($olcek)->sortBy(fn ($o) => abs($o - $d))->first();
    }

    /** @return array<string, mixed> */
    private static function govde(string $ekipman, string $yontem): array
    {
        $yontemAdi = $yontem === 'matris_5x5' ? '5x5 Matris' : 'Fine-Kinney';

        $istem = <<<PROMPT
        Sen Türkiye 6331 sayılı İş Sağlığı ve Güvenliği Kanunu, Makine Emniyeti Yönetmeliği (2006/42/AT)
        ve EN ISO standartlarına hâkim A Sınıfı bir İş Güvenliği Uzmanı ve Makine Mühendisisin.

        Ekipman / proses: "{$ekipman}"
        Kullanılan yöntem: {$yontemAdi}

        Bu ekipman/proses için risk değerlendirmesine girecek 3-5 adet TEKNİK risk maddesi üret.
        Genel geçer maddeler (ör. "KKD kullanılmaması") değil, bu ekipmana özgü mekanik, elektrik,
        enerji, ergonomi veya kimyasal tehlikeleri yaz. Her madde için:
        - bolum: ekipman adı, faaliyet: ilgili iş (operasyon, bakım, kalıp/ayar vb.)
        - tehlike: kısa başlık
        - kok_neden: tehlikenin sahadaki somut nedeni
        - risk: doğurabileceği yaralanma/kayıp
        - mevcut_onlem: sahada tipik olarak gözlenen mevcut durum (bilinmiyorsa "Belirlenmedi")
        - oneri: somut, uygulanabilir düzeltici aksiyon
        - mevzuat: ilgili Türk mevzuatı ve standart adı. Emin olmadığın madde numarası YAZMA; yalnız
          emin olduğun yönetmelik ve standart adlarını yaz.
        - fk_olasilik (0.2/0.5/1/3/6/10), fk_frekans (0.5/1/2/3/6/10), fk_siddet (1/3/7/15/40/100):
          önlem alınmamış mevcut durum için Fine-Kinney puanları
        - x5_olasilik ve x5_siddet: 1-5 arası 5x5 matris puanları
        Türkçe yaz. Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;

        $metin = ['type' => 'STRING'];
        $sayi = ['type' => 'NUMBER'];

        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'bolum' => $metin, 'faaliyet' => $metin, 'tehlike' => $metin, 'kok_neden' => $metin,
                            'risk' => $metin, 'mevcut_onlem' => $metin, 'oneri' => $metin, 'mevzuat' => $metin,
                            'fk_olasilik' => $sayi, 'fk_frekans' => $sayi, 'fk_siddet' => $sayi,
                            'x5_olasilik' => ['type' => 'INTEGER'], 'x5_siddet' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['tehlike', 'kok_neden', 'risk', 'oneri', 'mevzuat', 'fk_olasilik', 'fk_frekans', 'fk_siddet', 'x5_olasilik', 'x5_siddet'],
                    ],
                ],
            ],
        ];
    }
}
