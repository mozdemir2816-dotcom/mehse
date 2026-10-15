<?php

namespace App\Support;

use App\Models\RiskMaddesi;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kütüphaneden aktarılan, Excel'den yüklenen veya elle eklenip Olasılık/
 * Şiddet(/Frekans) puanı ya da Mevcut Önlem metni girilmemiş risk
 * maddelerini Gemini ile tamamlar — kullanıcı isteği: "olasılık şiddet ve
 * frekans olmayan kayıtları otomatik yapay zekaya yaptırarak kaydet" ve
 * ardından "yükleyeceğim tablolarda puanlama ve önlemler bölümü boşsa
 * yapay zeka doldursun". `GeminiRiskDanismani`'nin aksine YENİ risk
 * ÜRETMEZ, zaten var olan bir tehlike/risk metnine puan/önlem atar.
 * API anahtarı yoksa veya istek başarısız olursa sessizce null döner —
 * madde boş kalır, kullanıcı elle girebilir.
 */
class GeminiRiskPuanTamamlayici
{
    /** Bu kadar art arda başarısız istekten sonra (kota/hız sınırı, servis kapalı) aynı
     *  istek içinde artık Gemini'ye gidilmez — toplu tamamlama döngüsü 120 sn'yi aşmasın. */
    private const DEVRE_KESME_ESIGI = 3;

    /** Tek bir toplu tamamlamada AI çağrılarına ayrılan toplam süre (sn). Bu süre
     *  aşılınca kalan maddeler AI'sız bırakılır — sayfa çökmesin (Windows'ta PHP
     *  max_execution_time curl beklemesini de sayar). */
    private const BUTCE_SN = 45;

    /** Tek istek için curl zaman aşımı — gerçek yanıt 2-5 sn, 429 anında döner;
     *  yalnızca ağ takılması bu sınıra dayanır. Kısa tutulur ki birkaç başarısız
     *  istek toplam bütçeyi/max_execution_time'ı patlatmasın. */
    private const ISTEK_TIMEOUT = 12;

    /** Toplu öneri isteği (25 madde) — yalnız arka plan komutu kullanır, sayfa beklemez. */
    private const TOPLU_ISTEK_TIMEOUT = 120;

    private static int $ardArdaBasarisiz = 0;

    private static ?float $sonlanma = null;

    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** Art arda çok sayıda başarısızlık oldu mu VEYA süre bütçesi bitti mi. */
    public static function devreKesikMi(): bool
    {
        return static::$ardArdaBasarisiz >= self::DEVRE_KESME_ESIGI
            || (static::$sonlanma !== null && microtime(true) >= static::$sonlanma);
    }

    /** Yeni bir toplu tamamlama başlamadan önce sayacı + süre bütçesini sıfırlar. */
    public static function devreyiSifirla(): void
    {
        static::$ardArdaBasarisiz = 0;
        static::$sonlanma = microtime(true) + self::BUTCE_SN;
    }

    private static function basarisizlikKaydet(): void
    {
        static::$ardArdaBasarisiz++;
    }

    private static function basariKaydet(): void
    {
        static::$ardArdaBasarisiz = 0;
    }

    /** @return array{olasilik: float, siddet: float, frekans: ?float}|null */
    public static function oner(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, string $yontem): ?array
    {
        if (! static::aktifMi() || static::devreKesikMi()) {
            return null;
        }

        $fk = RiskSkorlama::ucEksenliMi($yontem);

        try {
            $yanit = GeminiIstemci::post(
                static::istekGovdesi($tehlike, $risk, $bolum, $faaliyet, $yontem),
                self::ISTEK_TIMEOUT, 5,
            );

            if ($yanit->failed()) {
                Log::warning('Gemini risk puanı önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);
                static::basarisizlikKaydet();

                return null;
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $sonuc = json_decode((string) $metin, true);

            if (! is_array($sonuc) || ! isset($sonuc['olasilik'], $sonuc['siddet'])) {
                return null;
            }

            static::basariKaydet();

            return [
                'olasilik' => static::enYakinDeger($sonuc['olasilik'], $yontem, 'olasilik'),
                'siddet' => static::enYakinDeger($sonuc['siddet'], $yontem, 'siddet'),
                'frekans' => $fk ? static::enYakinDeger($sonuc['frekans'] ?? 1, $yontem, 'frekans') : null,
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini risk puanı önerisi istisna', ['hata' => $e->getMessage()]);
            static::basarisizlikKaydet();

            return null;
        }
    }

    /**
     * Mevcut Önlem metni boş olan bir maddeye kısa, tehlikeye özgü bir önlem
     * önerisi ister. Puanlamadan bağımsız, ayrı ve daha ucuz bir istek —
     * yalnızca metin boşsa çağrılmalı (var olan metnin üzerine hiç yazılmaz).
     */
    public static function onlemOner(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, ?string $istem = null): ?string
    {
        if (! static::aktifMi() || static::devreKesikMi()) {
            return null;
        }

        try {
            $yanit = GeminiIstemci::post(
                static::onlemIstekGovdesi($istem ?? static::onlemIstem($tehlike, $risk, $bolum, $faaliyet)),
                self::ISTEK_TIMEOUT, 5,
            );

            if ($yanit->failed()) {
                Log::warning('Gemini önlem önerisi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);
                static::basarisizlikKaydet();

                return null;
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $sonuc = json_decode((string) $metin, true);

            if (! is_array($sonuc) || blank($sonuc['onlem'] ?? null)) {
                return null;
            }

            static::basariKaydet();

            return trim((string) $sonuc['onlem']);
        } catch (Throwable $e) {
            Log::warning('Gemini önlem önerisi istisna', ['hata' => $e->getMessage()]);
            static::basarisizlikKaydet();

            return null;
        }
    }

    /**
     * Önerisi boş birden çok maddeye TEK istekte öneri ister — risk:oneri-tamamla kullanır.
     * Ücretsiz Gemini kotası model başına günde 20 istek (15.10.2026 ölçüldü); madde başına
     * istekle günde ~20 madde dolabiliyor ve kota sihirbaz gibi elle kullanılan özelliklere
     * kalmıyordu. Başarısızlıkta boş dizi döner.
     *
     * @param  iterable<RiskMaddesi>  $maddeler
     * @return array<int, string> madde id => öneri metni
     */
    public static function oneriTopluOner(iterable $maddeler): array
    {
        if (! static::aktifMi()) {
            return [];
        }

        $liste = [];
        foreach ($maddeler as $m) {
            $liste[] = array_filter([
                'no' => $m->id,
                'bolum' => $m->bolum,
                'faaliyet' => $m->faaliyet,
                'tehlike' => $m->tehlike,
                'risk' => $m->risk,
                'mevcut_onlem' => $m->mevcut_onlem,
            ], 'filled');
        }

        if ($liste === []) {
            return [];
        }

        $maddelerJson = json_encode($liste, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $istem = <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki risk değerlendirmesi maddelerinin
        HER BİRİ için "Düzeltici / önleyici faaliyet" metni yaz: 1-2 cümle (en fazla 40
        kelime), somut ve uygulanabilir, tehlikeye özgü teknik ve idari önlemler; önce
        tehlikeyi kaynağında yok etme / mühendislik önlemi, sonra idari önlem ve gerekiyorsa
        KKD. Genel klişe yazma. Mevcut önlemleri tekrarlama, eksik kalanı belirt.

        Maddeler (no = madde numarası, yanıtta aynen kullan):
        {$maddelerJson}

        Yanıtı SADECE JSON dizisi olarak ver: her madde için {"no": <numara>, "oneri": "..."}.
        PROMPT;

        try {
            $yanit = GeminiIstemci::post([
                'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'ARRAY',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => ['no' => ['type' => 'INTEGER'], 'oneri' => ['type' => 'STRING']],
                            'required' => ['no', 'oneri'],
                        ],
                    ],
                ],
            ], self::TOPLU_ISTEK_TIMEOUT, 5);

            if ($yanit->failed()) {
                Log::warning('Gemini toplu öneri başarısız', ['durum' => $yanit->status(), 'govde' => mb_substr($yanit->body(), 0, 500)]);

                return [];
            }

            $sonuc = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
            $gecerliIdler = array_column($liste, 'no');
            $oneriler = [];

            foreach (is_array($sonuc) ? $sonuc : [] as $satir) {
                $id = (int) ($satir['no'] ?? 0);
                $metin = trim((string) ($satir['oneri'] ?? ''));

                if ($metin !== '' && in_array($id, $gecerliIdler, true)) {
                    $oneriler[$id] = $metin;
                }
            }

            return $oneriler;
        } catch (Throwable $e) {
            Log::warning('Gemini toplu öneri istisna', ['hata' => $e->getMessage()]);

            return [];
        }
    }

    /** @return array<string, mixed> */
    private static function onlemIstekGovdesi(string $istem): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $istem]],
            ]],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json',
                'responseSchema' => ['type' => 'OBJECT', 'properties' => ['onlem' => ['type' => 'STRING']], 'required' => ['onlem']],
            ],
        ];
    }

    private static function onlemIstem(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet): string
    {
        return <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Aşağıdaki tehlike/risk maddesi için
        KISA (tek cümle, en fazla 25-30 kelime), somut ve uygulanabilir bir
        "mevcut/alınması gereken önlem" metni yaz — genel klişe değil, tehlikeye
        özgü teknik/idari bir önlem belirt.

        Bölüm/Ünite: {$bolum}
        Faaliyet: {$faaliyet}
        Tehlike: {$tehlike}
        Risk (olası sonuç): {$risk}

        Yanıtı SADECE JSON nesnesi olarak ver (ör. {"onlem": "..."}).
        PROMPT;
    }

    /** LLM'in döndürdüğü sayı, ölçekteki İZİN VERİLEN değerlerden en yakınına yuvarlanır (ölçek dışı değer sızmasın diye). */
    private static function enYakinDeger(mixed $deger, string $yontem, string $eksen): float
    {
        $izinli = array_map('floatval', array_keys(RiskSkorlama::olcek($yontem, $eksen)));
        $sayi = (float) $deger;

        usort($izinli, fn (float $a, float $b) => abs($a - $sayi) <=> abs($b - $sayi));

        return $izinli[0] ?? $sayi;
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, string $yontem): array
    {
        $properties = [
            'olasilik' => ['type' => 'NUMBER'],
            'siddet' => ['type' => 'NUMBER'],
        ];
        $required = ['olasilik', 'siddet'];

        if (RiskSkorlama::ucEksenliMi($yontem)) {
            $properties['frekans'] = ['type' => 'NUMBER'];
            $required[] = 'frekans';
        }

        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => static::istem($tehlike, $risk, $bolum, $faaliyet, $yontem)]],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => ['type' => 'OBJECT', 'properties' => $properties, 'required' => $required],
            ],
        ];
    }

    private static function istem(string $tehlike, ?string $risk, ?string $bolum, ?string $faaliyet, string $yontem): string
    {
        $fk = RiskSkorlama::ucEksenliMi($yontem);
        $etiket = RiskSkorlama::eksenEtiketleri($yontem);

        $olcekMetni = fn (string $eksen) => collect(RiskSkorlama::olcek($yontem, $eksen))
            ->map(fn ($ad, $deger) => "{$deger}: {$ad}")
            ->implode("\n");

        $frekansBlok = $fk ? "\n{$etiket['frekans']} ölçeği — SADECE bu değerlerden seç:\n".$olcekMetni('frekans')."\n" : '';
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
