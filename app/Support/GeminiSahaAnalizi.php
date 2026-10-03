<?php

namespace App\Support;

use App\Models\SahaAnalizi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Saha Gözlem Raporu yapay zekâ çağrıları (Gemini):
 * - analizEt(): fotoğraflardan uygunsuzluk bulguları (isgpratik AI SAHA ANALİZİ/1-3.jpg)
 * - aciklamadanUret(): kısa açıklamadan tek bulgu (isgsuite "Açıklamadan")
 * - iyilestir(): mevcut bulguyu kullanıcı geri bildirimiyle yeniden yazar (isgsuite "İyileştir")
 * Her bulguya Fine-Kinney olasılık/frekans/şiddet önerisi eklenir. API anahtarı
 * yoksa/istek başarısızsa boş sonuç döner — kullanıcı elle madde ekler.
 */
class GeminiSahaAnalizi
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array<int, string>  $fotografYollari  'public' diskindeki dosya yolları (1'den başlayan sırayla)
     * @return array<int, array<string, mixed>>
     */
    public static function analizEt(array $fotografYollari, ?string $baglamNotu = null): array
    {
        if (! static::aktifMi() || ! $fotografYollari) {
            return [];
        }

        $fotografYollari = array_values($fotografYollari);
        $parts = [['text' => static::istem($baglamNotu, count($fotografYollari))]];

        foreach ($fotografYollari as $i => $yol) {
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

        $bulgular = static::istek($parts, ['type' => 'ARRAY', 'items' => static::bulguSemasi(true)]);

        if (! is_array($bulgular)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($b) use ($fotografYollari) {
            if (! is_array($b) || blank($b['tespit'] ?? null)) {
                return null;
            }

            $fotoIndex = (int) ($b['foto_index'] ?? 1);

            return ['foto_index' => $fotoIndex, 'foto_yolu' => $fotografYollari[$fotoIndex - 1] ?? ($fotografYollari[0] ?? null)]
                + static::normalize($b);
        }, $bulgular)));
    }

    /** Kullanıcının kısa açıklamasından tek bulgu üretir. @return array<string, mixed>|null */
    public static function aciklamadanUret(string $aciklama, ?string $baglamNotu = null): ?array
    {
        if (! static::aktifMi() || blank($aciklama)) {
            return null;
        }

        $baglam = filled($baglamNotu) ? "\nBağlam: {$baglamNotu}" : '';
        $istem = static::uzmanGirisi()."\nSahada gözlemlenen durumun kısa açıklaması: \"{$aciklama}\"{$baglam}\n\n"
            ."Bu açıklamadan TEK bir uygunsuzluk maddesi oluştur.\n".static::alanTarifi(false);

        $b = static::istek([['text' => $istem]], static::bulguSemasi(false));

        return is_array($b) && filled($b['tespit'] ?? null) ? static::normalize($b) : null;
    }

    /**
     * Mevcut bulguyu kullanıcının geri bildirimiyle yeniden yazar; fotoğrafı varsa
     * modele birlikte gönderilir. @return array<string, mixed>|null
     */
    public static function iyilestir(array $bulgu, ?string $geriBildirim = null): ?array
    {
        if (! static::aktifMi()) {
            return null;
        }

        $mevcut = json_encode([
            'tespit' => $bulgu['tespit'] ?? '',
            'oneriler' => array_values(array_filter(preg_split('/\r\n|\r|\n/', (string) ($bulgu['oneriler_metni'] ?? '')))),
            'yasal_gerekce' => $bulgu['yasal_gerekce'] ?? null,
            'olasilik' => $bulgu['olasilik'] ?? null,
            'frekans' => $bulgu['frekans'] ?? null,
            'siddet' => $bulgu['siddet'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        $not = filled($geriBildirim) ? "\nKullanıcının geri bildirimi (buna mutlaka uy): \"{$geriBildirim}\"" : '';
        $istem = static::uzmanGirisi()."\nAşağıdaki uygunsuzluk maddesini daha somut, teknik ve uygulanabilir hâle getir.{$not}\n"
            ."Mevcut madde: {$mevcut}\n\n".static::alanTarifi(false);

        $parts = [['text' => $istem]];
        $tamYol = filled($bulgu['foto_yolu'] ?? null) ? Storage::disk('public')->path($bulgu['foto_yolu']) : null;

        if ($tamYol && is_file($tamYol)) {
            $parts[] = ['inline_data' => [
                'mime_type' => mime_content_type($tamYol) ?: 'image/jpeg',
                'data' => base64_encode((string) file_get_contents($tamYol)),
            ]];
        }

        $b = static::istek($parts, static::bulguSemasi(false));

        return is_array($b) && filled($b['tespit'] ?? null) ? static::normalize($b) : null;
    }

    /** @return array<string, mixed> */
    private static function normalize(array $b): array
    {
        $o = SahaAnalizi::olcegeYuvarla('olasilik', $b['olasilik'] ?? null);
        $f = SahaAnalizi::olcegeYuvarla('frekans', $b['frekans'] ?? null);
        $s = SahaAnalizi::olcegeYuvarla('siddet', $b['siddet'] ?? null);
        $skor = SahaAnalizi::fineKinneySkoru(['olasilik' => $o, 'frekans' => $f, 'siddet' => $s]);

        return [
            'bina_bolge' => $b['bina_bolge'] ?? null,
            'kategori' => $b['kategori'] ?? null,
            'tespit' => (string) $b['tespit'],
            'oneriler' => is_array($b['oneriler'] ?? null) ? array_values($b['oneriler']) : [],
            'yasal_gerekce' => $b['yasal_gerekce'] ?? null,
            'olasilik' => $o,
            'frekans' => $f,
            'siddet' => $s,
            'risk_derecesi' => $skor !== null
                ? SahaAnalizi::skordanDerece($skor)
                : max(1, min(4, (int) ($b['risk_derecesi'] ?? 3))),
        ];
    }

    /** @return mixed çözümlenmiş JSON ya da null */
    private static function istek(array $parts, array $sema): mixed
    {
        try {
            $yanit = Http::timeout(90)->post(static::endpoint(), [
                'contents' => [['role' => 'user', 'parts' => $parts]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $sema,
                ],
            ]);

            if ($yanit->failed()) {
                Log::warning('Gemini saha analizi başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return null;
            }

            return json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
        } catch (Throwable $e) {
            Log::warning('Gemini saha analizi istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function bulguSemasi(bool $fotoIndexli): array
    {
        $ozellikler = [
            'bina_bolge' => ['type' => 'STRING'],
            'kategori' => ['type' => 'STRING'],
            'tespit' => ['type' => 'STRING'],
            'oneriler' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'yasal_gerekce' => ['type' => 'STRING'],
            'olasilik' => ['type' => 'NUMBER'],
            'frekans' => ['type' => 'NUMBER'],
            'siddet' => ['type' => 'NUMBER'],
            'risk_derecesi' => ['type' => 'INTEGER'],
        ];
        $zorunlu = ['tespit', 'oneriler', 'olasilik', 'frekans', 'siddet'];

        if ($fotoIndexli) {
            $ozellikler = ['foto_index' => ['type' => 'INTEGER']] + $ozellikler;
            $zorunlu[] = 'foto_index';
        }

        return ['type' => 'OBJECT', 'properties' => $ozellikler, 'required' => $zorunlu];
    }

    private static function uzmanGirisi(): string
    {
        return "Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan deneyimli bir İş Güvenliği Uzmanısın.";
    }

    private static function olcekMetni(string $olcek): string
    {
        return collect(config('isg.risk_fine_kinney.'.$olcek))->map(fn ($e, $k) => "{$k}={$e}")->implode('; ');
    }

    private static function alanTarifi(bool $fotoIndexli): string
    {
        $foto = $fotoIndexli ? "- foto_index: bulgunun ait olduğu fotoğrafın sırası (1'den başlar).\n" : '';
        $olasilikOlcegi = static::olcekMetni('olasilik');
        $frekansOlcegi = static::olcekMetni('frekans');
        $siddetOlcegi = static::olcekMetni('siddet');

        return $foto.<<<METIN
        - bina_bolge: görülebiliyorsa bina/bölge adı (yoksa boş bırak).
        - kategori: kısa kategori etiketi (örn. "Yüksekte Çalışma", "Düzen/Temizlik", "KKD Eksikliği", "Elektrik Güvenliği").
        - tespit: uygunsuzluğun/tehlikenin açıklaması (2-4 cümle, somut ve teknik).
        - oneriler: alınması gereken önlemler, her biri tek cümle olan dizi.
        - yasal_gerekce: ilgili mevzuatın tam adı ve varsa maddesi.
        - Fine-Kinney değerlendirmesi — YALNIZ şu değerlerden birini seç:
          olasilik: {$olasilikOlcegi}
          frekans: {$frekansOlcegi}
          siddet: {$siddetOlcegi}
        - risk_derecesi: 1 (Çok Yüksek), 2 (Yüksek), 3 (Orta) veya 4 (Düşük).
        Yanıtı SADECE JSON olarak ver — başka açıklama ekleme.
        METIN;
    }

    private static function istem(?string $baglamNotu, int $fotoSayisi): string
    {
        $baglam = filled($baglamNotu) ? "\nBağlam notu (kullanıcı verdi): {$baglamNotu}\n" : '';

        return static::uzmanGirisi()." Sana {$fotoSayisi} adet iş yeri saha fotoğrafı veriliyor. "
            ."Her fotoğrafı İSG açısından tehlike/uygunsuzluk yönünden dikkatle incele.\n{$baglam}\n"
            ."Tespit ettiğin HER uygunsuzluk için bir bulgu üret:\n".static::alanTarifi(true)."\n"
            .'Fotoğrafta belirgin bir tehlike/uygunsuzluk yoksa o fotoğraf için bulgu üretme. Yanıt bir JSON dizisi olmalı.';
    }
}
