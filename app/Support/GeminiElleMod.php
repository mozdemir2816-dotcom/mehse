<?php

namespace App\Support;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Throwable;

/**
 * "Gemini'de elle yap" (12.11.2026, kullanıcı fikri) — API kotası dolunca / yanıt gelmeyince sistem gönderemediği
 * istemi saklar ve ekranda pencere açar (resources/views/livewire/gemini-elle-pencere.blade.php). Kullanıcı istemi
 * Gemini sohbetine yapıştırır, yanıtı pencereye geri yapıştırır; aynı yapay zekâ düğmesine tekrar basınca
 * GeminiIstemci API'ye gitmeden bu yanıtı döndürür ve sayfanın kendi kodu onu normal yanıt gibi işler.
 *
 * Eşleşme istemin içeriğiyle (contents + yanıt şeması): aynı girdilerle aynı düğme aynı istemi üretir.
 */
class GeminiElleMod
{
    private const SURE_SAAT = 6;

    /** Bu istekte açılan son elle istem (test ve tanı için). */
    public static ?string $sonToken = null;

    /** @param  array<string, mixed>  $govde */
    public static function anahtar(array $govde): string
    {
        return md5(json_encode([$govde['contents'] ?? null, data_get($govde, 'generationConfig.responseSchema')]));
    }

    /** Elle yapıştırılmış yanıt varsa sahte API yanıtı (bir kez kullanılır). */
    public static function kayitliYanit(array $govde): ?Response
    {
        $metin = Cache::pull('gemini-elle-yanit:'.static::anahtar($govde));

        return $metin === null ? null : new Response(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'candidates' => [['content' => ['parts' => [['text' => $metin]]], 'finishReason' => 'STOP']],
        ], JSON_UNESCAPED_UNICODE)));
    }

    /**
     * API başarısız: istemi sakla, panelde pencereyi aç. Komut satırında (zamanlayıcı) ve testte açılmaz.
     *
     * @param  array<string, mixed>  $govde
     */
    public static function hazirla(array $govde, ?int $durum = null): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $token = static::$sonToken = Str::random(24);
        Cache::put('gemini-elle-istek:'.$token, [
            'anahtar' => static::anahtar($govde),
            'istem' => static::istemMetni($govde),
            'json' => data_get($govde, 'generationConfig.responseMimeType') === 'application/json',
            'fotograf' => static::fotografVarMi($govde),
            'durum' => $durum,
            'zaman' => now()->toDateTimeString(),
        ], now()->addHours(self::SURE_SAAT));

        try {
            Livewire::current()?->dispatch('gemini-elle-ac', token: $token);
        } catch (Throwable) {
            // Livewire isteği değilse pencere açılmaz; yapay zekâ sayfası kendi uyarısını gösterir.
        }

        return $token;
    }

    /** @return array{anahtar: string, istem: string, json: bool, fotograf: bool, durum: ?int, zaman: string}|null */
    public static function istek(string $token): ?array
    {
        return Cache::get('gemini-elle-istek:'.$token);
    }

    /**
     * Yapıştırılan yanıtı doğrular ve saklar. JSON beklenen istemde ```json çitleri ve baştaki/sondaki açıklama ayıklanır.
     *
     * @return string|null hata mesajı (null = kaydedildi)
     */
    public static function yanitKaydet(string $token, string $metin): ?string
    {
        $istek = static::istek($token);
        if (! $istek) {
            return 'Bu istemin süresi doldu; yapay zekâ düğmesine yeniden basın.';
        }

        $metin = trim($metin);
        if ($metin === '') {
            return 'Gemini\'nin yanıtını yapıştırın.';
        }

        if ($istek['json']) {
            $metin = static::jsonAyikla($metin);
            if ($metin === null) {
                return 'Yanıt JSON olarak okunamadı. Gemini\'ye "yanıtı yalnız JSON olarak, açıklamasız ver" deyip yeniden deneyin.';
            }
        }

        Cache::put('gemini-elle-yanit:'.$istek['anahtar'], $metin, now()->addHours(self::SURE_SAAT));

        return null;
    }

    /** "```json … ```", baştaki "İşte yanıt:" gibi metinler ayıklanır; geçerli JSON değilse null. */
    public static function jsonAyikla(string $metin): ?string
    {
        if (preg_match('/```(?:json)?\s*(.+?)```/su', $metin, $m)) {
            $metin = $m[1];
        }
        $bas = strcspn($metin, '[{');
        $son = max(strrpos($metin, ']') ?: -1, strrpos($metin, '}') ?: -1);
        if ($bas >= strlen($metin) || $son < $bas) {
            return null;
        }
        $aday = substr($metin, $bas, $son - $bas + 1);
        json_decode($aday);

        return json_last_error() === JSON_ERROR_NONE ? $aday : null;
    }

    /** Gemini sohbetine yapıştırılacak metin: istemin metin parçaları + (varsa) beklenen JSON biçimi. */
    public static function istemMetni(array $govde): string
    {
        $parcalar = collect($govde['contents'] ?? [])->flatMap(fn ($c) => $c['parts'] ?? [])
            ->pluck('text')->filter()->map(fn ($t) => trim($t))->implode("\n\n");
        if ($sistem = data_get($govde, 'systemInstruction.parts.0.text')) {
            $parcalar = trim($sistem)."\n\n".$parcalar;
        }

        $sema = data_get($govde, 'generationConfig.responseSchema');
        if ($sema) {
            $parcalar .= "\n\nYANIT BİÇİMİ: Yanıtı YALNIZ aşağıdaki yapıya uyan JSON olarak ver; açıklama, başlık ya da kod çiti ekleme.\n"
                .json_encode(static::ornek($sema), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } elseif (data_get($govde, 'generationConfig.responseMimeType') === 'application/json') {
            $parcalar .= "\n\nYANIT BİÇİMİ: Yanıtı YALNIZ JSON olarak ver; açıklama ekleme.";
        }

        return $parcalar;
    }

    /** Gemini şemasından okunur örnek yapı: {"alan": "metin", "puan": 0, "liste": ["metin"]}. */
    private static function ornek(array $sema): mixed
    {
        return match (strtoupper((string) ($sema['type'] ?? 'STRING'))) {
            'ARRAY' => [static::ornek($sema['items'] ?? ['type' => 'STRING'])],
            'OBJECT' => collect($sema['properties'] ?? [])->map(fn ($p) => static::ornek($p))->all(),
            'INTEGER', 'NUMBER' => 0,
            'BOOLEAN' => true,
            default => isset($sema['enum']) ? implode(' | ', $sema['enum']) : 'metin',
        };
    }

    private static function fotografVarMi(array $govde): bool
    {
        return collect($govde['contents'] ?? [])->flatMap(fn ($c) => $c['parts'] ?? [])
            ->contains(fn ($p) => isset($p['inlineData']) || isset($p['inline_data']) || isset($p['fileData']));
    }
}
