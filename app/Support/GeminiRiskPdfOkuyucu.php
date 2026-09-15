<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Risk Sihirbazı "PDF Yükleyin" — kullanıcının kendi risk analizi PDF'ini
 * (taranmış/görsel tablo dahil) Gemini'ye gönderip madde madde ayıklar.
 * Çıktı, RiskDegerlendirmesiExcelOkuyucu::oku() ile AYNI 'aday' şeklini
 * kullanır (kaynak='pdf') — Risk Sihirbazı'ndaki Excel inceleme/seçim
 * ekranı ve toplu ekleme akışı değişmeden bu kaynağı da kabul eder.
 */
class GeminiRiskPdfOkuyucu
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /** @return array{basarili: int, adaylar: array<int, array<string, mixed>>, hatalar: array<int, string>} */
    public static function oku(string $dosyaYolu): array
    {
        if (! static::aktifMi()) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Gemini API anahtarı yapılandırılmamış — PDF okunamıyor.']];
        }

        try {
            $yanit = Http::timeout(180)->post(static::endpoint(), static::istekGovdesi($dosyaYolu));

            if ($yanit->failed()) {
                Log::warning('Gemini risk PDF okuma başarısız', ['durum' => $yanit->status(), 'govde' => $yanit->body()]);

                return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['PDF işlenemedi (Gemini isteği başarısız oldu). Lütfen tekrar deneyin.']];
            }

            $metin = data_get($yanit->json(), 'candidates.0.content.parts.0.text');
            $maddeler = json_decode((string) $metin, true);

            if (! is_array($maddeler)) {
                return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['PDF içeriği anlaşılamadı — tablo net değil ya da belge çok büyük olabilir.']];
            }

            $adaylar = array_values(array_filter(array_map(fn ($m, $i) => static::adayaCevir($m, $i), $maddeler, array_keys($maddeler))));

            if (! $adaylar) {
                return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['PDF içinde tanınabilir bir risk maddesi bulunamadı.']];
            }

            return ['basarili' => count($adaylar), 'adaylar' => $adaylar, 'hatalar' => []];
        } catch (Throwable $e) {
            Log::warning('Gemini risk PDF okuma istisna', ['hata' => $e->getMessage()]);

            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Beklenmeyen bir hata oluştu: '.$e->getMessage()]];
        }
    }

    private static function adayaCevir($m, int $i): ?array
    {
        if (! is_array($m) || blank($m['tehlike'] ?? null)) {
            return null;
        }

        return [
            'anahtar' => 'pdf-'.$i.'-'.substr(md5(($m['tehlike'] ?? '').$i.uniqid('', true)), 0, 6),
            'kaynak' => 'pdf',
            'tehlike_id' => null,
            'bolum' => $m['bolum'] ?? null,
            'faaliyet' => $m['faaliyet'] ?? null,
            'tehlike' => (string) $m['tehlike'],
            'risk' => $m['risk'] ?? null,
            'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
            'oneri' => $m['oneri'] ?? null,
            'mevzuat' => null,
            'sorumlu' => $m['sorumlu'] ?? null,
            'termin' => $m['termin'] ?? null,
            'aciklama' => null,
            'olasilik' => is_numeric($m['olasilik'] ?? null) ? (float) $m['olasilik'] : null,
            'frekans' => is_numeric($m['frekans'] ?? null) ? (float) $m['frekans'] : null,
            'siddet' => is_numeric($m['siddet'] ?? null) ? (float) $m['siddet'] : null,
            'son_olasilik' => is_numeric($m['son_olasilik'] ?? null) ? (float) $m['son_olasilik'] : null,
            'son_frekans' => is_numeric($m['son_frekans'] ?? null) ? (float) $m['son_frekans'] : null,
            'son_siddet' => is_numeric($m['son_siddet'] ?? null) ? (float) $m['son_siddet'] : null,
        ];
    }

    private static function endpoint(): string
    {
        $model = config('services.gemini.model', 'gemini-3.6-flash');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key');
    }

    /** @return array<string, mixed> */
    private static function istekGovdesi(string $dosyaYolu): array
    {
        return [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => static::istem()],
                    [
                        'inline_data' => [
                            'mime_type' => 'application/pdf',
                            'data' => base64_encode((string) file_get_contents($dosyaYolu)),
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'bolum' => ['type' => 'STRING'],
                            'faaliyet' => ['type' => 'STRING'],
                            'tehlike' => ['type' => 'STRING'],
                            'risk' => ['type' => 'STRING'],
                            'mevcut_onlem' => ['type' => 'STRING'],
                            'oneri' => ['type' => 'STRING'],
                            'sorumlu' => ['type' => 'STRING'],
                            'termin' => ['type' => 'STRING'],
                            'olasilik' => ['type' => 'NUMBER'],
                            'frekans' => ['type' => 'NUMBER'],
                            'siddet' => ['type' => 'NUMBER'],
                            'son_olasilik' => ['type' => 'NUMBER'],
                            'son_frekans' => ['type' => 'NUMBER'],
                            'son_siddet' => ['type' => 'NUMBER'],
                        ],
                        'required' => ['tehlike'],
                    ],
                ],
            ],
        ];
    }

    private static function istem(): string
    {
        return <<<'PROMPT'
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu'na göre çalışan
        deneyimli bir İş Güvenliği Uzmanısın. Sana bir risk değerlendirmesi belgesi
        (PDF — taranmış tablo, görsel tablo veya düz metin olabilir) veriliyor.

        Belgedeki HER risk/tehlike satırını, HİÇBİRİNİ ATLAMADAN, madde madde çıkar.
        Belge birden çok bölüm/sayfa içeriyorsa hepsini tara. Her tehlike için:
        - bolum: bölüm/alan/faaliyet grubu adı (varsa; tablo başlığından/bölüm
          sütunundan al).
        - faaliyet: yapılan iş/faaliyetin kısa adı (varsa).
        - tehlike: tehlikenin/tehlikeli durumun tam açıklaması (ZORUNLU, boş bırakma).
        - risk: bu tehlikenin yol açabileceği risk/sonuç (ör. "Elektrik çarpması").
        - mevcut_onlem: hâlihazırda alınmış önlem/mevcut durum (varsa).
        - oneri: alınması önerilen/planlanan önlem (varsa).
        - sorumlu: sorumlu kişi/birim (varsa).
        - termin: termin/süre bilgisi (varsa).
        - olasilik, frekans, siddet: belgede ÖNCESİ (mevcut durum) için verilmiş
          sayısal O/F/Ş (veya sadece O/Ş) değerleri — sayı olarak ver, yoksa alanı
          hiç yazma (uydurma).
        - son_olasilik, son_frekans, son_siddet: belgede SONRASI (önlem sonrası/
          revizyon) için verilmiş sayısal değerler — yoksa hiç yazma.

        Belgede frekans (F) sütunu yoksa (ör. L tipi matris/5x5 gibi 2 eksenli
        yöntemler) frekans alanlarını hiç yazma, uydurma sayı verme.

        Metni birebir belgeden al, kendi yorumunu katma, madde uydurma. Belge çok
        sayıda madde içeriyorsa TAMAMINI çıkarmaya öncelik ver; metinleri gereksiz
        uzatma. Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;
    }
}
