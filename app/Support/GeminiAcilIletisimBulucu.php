<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Acil Durum Planı — "Adrese göre resmî kurum / telefon bulucu" (kullanıcı 02.11.2026). Firmanın
 * adresinden en yakın hastane, itfaiye grubu, emniyet / jandarma, kaymakamlık, doğalgaz ve elektrik
 * dağıtım şirketinin adını ve santral telefonunu önerir. Önce Google Arama dayanaklı (grounding)
 * istek denenir; desteklenmezse modelin kendi bilgisiyle yanıt alınır ve "tahmin" diye işaretlenir.
 *
 * Telefonlar yanlış olabilir: sonuçlar önizlemede uzmanın doğrulamasına sunulur, yalnız onaylanan
 * satırlar EK-2'ye girer. Acil çağrı her zaman 112'dir (belgede ayrıca basılır).
 */
class GeminiAcilIletisimBulucu
{
    /** kurum anahtarı => EK-2'deki birim adı */
    public const KURUMLAR = [
        'hastane' => 'EN YAKIN HASTANE',
        'itfaiye' => 'İTFAİYE GRUP AMİRLİĞİ',
        'emniyet' => 'EMNİYET / JANDARMA',
        'kaymakamlik' => 'KAYMAKAMLIK / VALİLİK',
        'dogalgaz' => 'DOĞALGAZ DAĞITIM ŞİRKETİ',
        'elektrik' => 'ELEKTRİK DAĞITIM ŞİRKETİ',
    ];

    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array{satirlar: array<int, array{kurum: string, birim: string, ad: string, telefon: string, not: string}>, aramali: bool, hata: ?string} */
    public static function bul(?string $adres): array
    {
        $bos = ['satirlar' => [], 'aramali' => false];

        if (mb_strlen(trim((string) $adres)) < 8) {
            return [...$bos, 'hata' => 'Firmanın adresi girilmemiş. Önce Firmalar ekranında açık adresi (ilçe / il) doldurun.'];
        }
        if (! static::aktifMi()) {
            return [...$bos, 'hata' => 'Yapay zekâ anahtarı (Gemini) tanımlı değil.'];
        }

        try {
            $aramali = true;
            $yanit = GeminiIstemci::post(static::govde($adres, true), 60);

            // Arama aracı bu modelde / anahtarda yoksa (400) ya da arama kotası dolduysa (429) aracı
            // kaldırıp yapılandırılmış yanıt iste (02.11.2026 yerel anahtar aramada 429 verdi).
            if ($yanit->failed()) {
                Log::info('Gemini aramalı acil iletişim başarısız, aramasız deneniyor', ['durum' => $yanit->status()]);
                $aramali = false;
                $yanit = GeminiIstemci::post(static::govde($adres, false), 45);
            }

            if ($yanit->failed()) {
                Log::warning('Gemini acil iletişim başarısız', ['durum' => $yanit->status()]);

                return [...$bos, 'hata' => 'Yapay zekâ şu an yanıt vermedi, birazdan tekrar deneyin.'];
            }

            $metin = collect(data_get($yanit->json(), 'candidates.0.content.parts', []))->pluck('text')->implode('');
            $satirlar = static::ayristir($metin);

            return [
                'satirlar' => $satirlar,
                'aramali' => $aramali,
                'hata' => $satirlar ? null : 'Bu adres için kurum bilgisi bulunamadı; adresi ilçe ve il ile birlikte yazıp tekrar deneyin.',
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini acil iletişim istisna', ['hata' => $e->getMessage()]);

            return [...$bos, 'hata' => 'Yapay zekâya bağlanılamadı, birazdan tekrar deneyin.'];
        }
    }

    /** Yanıt metnindeki JSON'dan (kod bloğu içinde de olabilir) kurum satırları. */
    public static function ayristir(string $metin): array
    {
        $json = json_decode($metin, true);
        if (! is_array($json) && preg_match('/\{.*\}/su', $metin, $m)) {
            $json = json_decode($m[0], true);
        }

        $temiz = fn ($x, $uz) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $x)), 0, $uz);

        return collect(is_array($json) ? ($json['kurumlar'] ?? $json) : [])
            ->filter(fn ($k) => is_array($k) && isset(self::KURUMLAR[$k['kurum'] ?? '']) && filled($k['ad'] ?? null))
            ->unique('kurum')
            ->map(fn ($k) => [
                'kurum' => $k['kurum'],
                'birim' => self::KURUMLAR[$k['kurum']],
                'ad' => $temiz($k['ad'], 80),
                // Telefonda yalnız rakam, boşluk, +, (, ) ve tire kalır.
                'telefon' => $temiz(preg_replace('/[^\d\s+()\-]/u', '', (string) ($k['telefon'] ?? '')), 30),
                'not' => $temiz($k['not'] ?? '', 120),
            ])
            ->sortBy(fn ($k) => array_search($k['kurum'], array_keys(self::KURUMLAR), true))
            ->values()->all();
    }

    /** @return array<string, mixed> */
    private static function govde(string $adres, bool $aramali): array
    {
        $kurumlar = collect(self::KURUMLAR)->map(fn ($ad, $k) => "- {$k}: {$ad}")->implode("\n");

        $istem = <<<PROMPT
        Türkiye'deki şu işyeri adresi için acil durum planının EK-2 Acil İletişim Listesine girecek
        yerel resmî kurumları bul:

        Adres: "{$adres}"

        Kurum anahtarları:
        {$kurumlar}

        Her kurum için: kurum (yukarıdaki anahtar), ad (kurumun tam resmî adı — ör. hastane için
        adrese en yakın acil servisi olan devlet / şehir hastanesi; itfaiye için bölgeye bakan itfaiye
        grup amirliği; emniyet için ilçe emniyet müdürlüğü, kırsal alansa ilçe jandarma komutanlığı;
        doğalgaz ve elektrik için bölgenin dağıtım şirketi), telefon (santral / sabit hat; doğalgaz için
        187, elektrik için 186 arıza hattı da yazılabilir), not (kısa açıklama, ör. ilçe adı).
        Emin olmadığın telefonu UYDURMA; telefon alanını boş bırak. Türkçe yaz.
        PROMPT;

        if ($aramali) {
            return [
                'contents' => [['role' => 'user', 'parts' => [['text' => $istem.
                    "\nGüncel bilgiyi web aramasıyla doğrula. Yanıtı SADECE şu biçimde JSON olarak ver: ".
                    '{"kurumlar":[{"kurum":"hastane","ad":"...","telefon":"...","not":"..."}]}']]]],
                'tools' => [['google_search' => (object) []]],
                'generationConfig' => ['temperature' => 0.1],
            ];
        }

        $metin = ['type' => 'STRING'];

        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => $istem.' Yanıtı SADECE JSON olarak ver.']]]],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => ['kurumlar' => ['type' => 'ARRAY', 'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['kurum' => ['type' => 'STRING', 'enum' => array_keys(self::KURUMLAR)], 'ad' => $metin, 'telefon' => $metin, 'not' => $metin],
                        'required' => ['kurum', 'ad', 'telefon'],
                    ]]],
                    'required' => ['kurumlar'],
                ],
            ],
        ];
    }
}
