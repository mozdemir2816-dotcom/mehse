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

    /** Önlem sonrası hedef: 5x5'te bu puan ve altı (katlanılabilir), Fine-Kinney'de bu puanın altı. */
    public const HEDEF_5X5_EN_FAZLA = 6;

    public const HEDEF_FK_ALTI = 70;

    /**
     * @return array<string, mixed> sihirbazın madde alanları (ilk + son_* Fine-Kinney) + `matris`
     *                              (5x5: olasilik, siddet, son_olasilik, son_siddet)
     */
    public static function normalize(array $m, string $ekipman): array
    {
        $metin = fn ($k) => trim((string) ($m[$k] ?? '')) ?: null;
        $kok = $metin('kok_neden');
        $risk = $metin('risk');

        // Yeni şema: puanlar.ilk / puanlar.reziduel; eski düz alanlar da kabul edilir.
        $ilk = (array) data_get($m, 'puanlar.ilk', []);
        $rez = (array) data_get($m, 'puanlar.reziduel', []);
        $al = fn (array $kaynak, string $anahtar, string $duz) => $kaynak[$anahtar] ?? $m[$duz] ?? null;

        $fkO = static::enYakin($al($ilk, 'fk_o', 'fk_olasilik'), self::FK_OLASILIK);
        $fkF = static::enYakin($al($ilk, 'fk_f', 'fk_frekans'), self::FK_FREKANS);
        $fkS = static::enYakin($al($ilk, 'fk_s', 'fk_siddet'), self::FK_SIDDET);
        $mO = static::besli($al($ilk, 'matris_o', 'x5_olasilik'));
        $mS = static::besli($al($ilk, 'matris_s', 'x5_siddet'));

        // Rezidüel: Şiddet ve frekans önlemden etkilenmez (ilk değerde sabit); yalnız olasılık düşer.
        $fkSonO = static::fkReziduelOlasilik($al($rez, 'reziduel_fk_o', 'reziduel_fk_olasilik'), $fkO, $fkF, $fkS);
        $mSonO = static::matrisReziduelOlasilik($al($rez, 'reziduel_matris_o', 'reziduel_x5_olasilik'), $mO, $mS);

        return [
            'bolum' => $metin('bolum') ?: $ekipman,
            'faaliyet' => $metin('faaliyet'),
            'tehlike' => $metin('tehlike'),
            'risk' => $kok ? 'Kök neden: '.$kok.($risk ? ' → '.$risk : '') : $risk,
            'mevcut_onlem' => $metin('mevcut_onlem'),
            'oneri' => $metin('oneri'),
            'mevzuat' => $metin('mevzuat'),
            'olasilik' => $fkO,
            'frekans' => $fkF,
            'siddet' => $fkS,
            'son_olasilik' => $fkSonO,
            'son_frekans' => $fkF,
            'son_siddet' => $fkS,
            'matris' => [
                'olasilik' => $mO,
                'siddet' => $mS,
                'son_olasilik' => $mSonO,
                'son_siddet' => $mS,
            ],
        ];
    }

    private static function besli(mixed $deger): int
    {
        return max(1, min(5, (int) round(is_numeric($deger) ? (float) $deger : 3)));
    }

    /**
     * Önerilen değer ölçeğe yuvarlanır, ilk olasılıktan en az bir basamak aşağıda olur (mümkünse) ve
     * O×F×Ş hedefin (70) altına inene kadar ölçekte düşürülür. Şiddet×frekans çok yüksekse (ör. 100×10)
     * en düşük olasılıkta bile hedefe inilemez — o zaman en düşük olasılık döner, puan dürüstçe gösterilir.
     */
    private static function fkReziduelOlasilik(mixed $oneri, float $o, float $f, float $s): float
    {
        $olcek = self::FK_OLASILIK;
        $ilkSira = (int) array_search($o, $olcek, false);
        $sira = is_numeric($oneri) ? (int) array_search(static::enYakin($oneri, $olcek), $olcek, false) : $ilkSira;
        $sira = min($sira, max(0, $ilkSira - 1));

        while ($sira > 0 && $olcek[$sira] * $f * $s >= self::HEDEF_FK_ALTI) {
            $sira--;
        }

        return (float) $olcek[$sira];
    }

    /** 5x5: olasılık ilkten en az 1 düşük (mümkünse) ve O×Ş ≤ 6 olana kadar düşürülür (en az 1). */
    private static function matrisReziduelOlasilik(mixed $oneri, int $o, int $s): int
    {
        $son = is_numeric($oneri) ? static::besli($oneri) : $o;
        $son = min($son, max(1, $o - 1));

        while ($son > 1 && $son * $s > self::HEDEF_5X5_EN_FAZLA) {
            $son--;
        }

        return $son;
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
        - puanlar.ilk: önlem alınmamış MEVCUT durum puanları —
          matris_o ve matris_s (1-5 arası 5x5 matris), fk_o (0.2/0.5/1/3/6/10),
          fk_f (0.5/1/2/3/6/10), fk_s (1/3/7/15/40/100) Fine-Kinney
        - puanlar.reziduel: "oneri" alanındaki önlemler UYGULANDIKTAN SONRAKİ hedef puanlar —
          reziduel_matris_o, reziduel_matris_s, reziduel_fk_o, reziduel_fk_f, reziduel_fk_s

        REZİDÜEL RİSK KURALI: Önerilen önlemler uygulandıktan sonra tehlike izole edildiği için
        Olasılık (O) değeri belirgin şekilde düşürülmeli, Şiddet (S) değeri sabit kalmalıdır
        (Fine-Kinney frekansı da maruziyet süresi olduğundan aynı kalır). 5x5 rezidüel skoru
        (O×S) 6 ve altına (kabul edilebilir yeşil bölgeye), Fine-Kinney rezidüel skoru (O×F×S)
        ise 70'in altına (kabul edilebilir seviyeye) düşecek şekilde rezidüel puanlar üretilmelidir.
        Türkçe yaz. Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;

        $metin = ['type' => 'STRING'];
        $sayi = ['type' => 'NUMBER'];
        $tam = ['type' => 'INTEGER'];
        $nesne = fn (array $alanlar) => ['type' => 'OBJECT', 'properties' => $alanlar, 'required' => array_keys($alanlar)];

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
                            // Skorlar (çarpımlar) yapay zekâdan istenmez; sistem kendisi hesaplar.
                            'puanlar' => $nesne([
                                'ilk' => $nesne(['matris_o' => $tam, 'matris_s' => $tam, 'fk_o' => $sayi, 'fk_f' => $sayi, 'fk_s' => $sayi]),
                                'reziduel' => $nesne([
                                    'reziduel_matris_o' => $tam, 'reziduel_matris_s' => $tam,
                                    'reziduel_fk_o' => $sayi, 'reziduel_fk_f' => $sayi, 'reziduel_fk_s' => $sayi,
                                ]),
                            ]),
                        ],
                        'required' => ['tehlike', 'kok_neden', 'risk', 'oneri', 'mevzuat', 'puanlar'],
                    ],
                ],
            ],
        ];
    }
}
