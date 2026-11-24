<?php

namespace App\Support;

use App\Models\IseOzguEgitimKonusu;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gemini API ile tüm NACE / sektörler ve çalışan grupları (ekip / iş kolu) için
 * RG 02.04.2026 (33212) ve 6331 m.17 uyumlu 4. Bölüm ("İşe ve İşyerine Özgü Riskler")
 * eğitim konu içerikleri üretir ve kütüphaneye (ise_ozgu_egitim_konulari) entegre eder.
 */
class GeminiIseOzguEgitimUretici
{
    public static function aktifMi(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Sistemdeki tüm sektör anahtarları (elle yazılan 'ozel' hariç) ve etiketleri.
     *
     * @return array<string, string>
     */
    public static function sektorSecenekleri(): array
    {
        return collect(EgitimIcerikOlusturucu::sektorler())
            ->except('ozel')
            ->all();
    }

    /**
     * Bir sektör anahtarının NACE 2 haneli (veya 4 haneli) ön ek kapsamını döndürür.
     */
    public static function sektorNaceOnekleri(?string $sektorAnahtari, ?string $firmaNace = null): ?string
    {
        if ($sektorAnahtari) {
            if ($sektorAnahtari === 'asansor') {
                return '43.29, 28.22';
            }

            $riskGrup = null;
            if (str_starts_with($sektorAnahtari, 'nace_')) {
                $riskGrup = substr($sektorAnahtari, 5);
            } else {
                $ters = array_flip(EgitimIcerikOlusturucu::NACE_SEKTOR);
                $riskGrup = $ters[$sektorAnahtari] ?? null;
            }

            if ($riskGrup && ($bolumler = config("risk_nace.gruplar.{$riskGrup}.bolumler"))) {
                return implode(', ', (array) $bolumler);
            }
        }

        $rakam = preg_replace('/\D/', '', (string) $firmaNace);
        if (strlen($rakam) >= 4) {
            return substr($rakam, 0, 2).'.'.substr($rakam, 2, 2);
        }
        if (strlen($rakam) >= 2) {
            return substr($rakam, 0, 2);
        }

        return null;
    }

    /**
     * Verilen sektör (ve isteğe bağlı NACE / tehlike sınıfı / ekip / ek bağlam) için
     * Gemini API'den 4. bölüm işe özgü eğitim konularını JSON olarak üretir.
     *
     * @return array{konular: array<int, array{ad: string, hedef: string, egitici: string}>, hata: ?string}
     */
    public static function uret(
        string $sektorAdi,
        ?string $naceKodu = null,
        ?string $tehlikeSinifi = null,
        ?string $isKolu = null,
        ?string $ekBaglam = null,
        int $adet = 6,
    ): array {
        if (! static::aktifMi()) {
            return ['konular' => [], 'hata' => 'Yapay zekâ API anahtarı (GEMINI_API_KEY) tanımlı değil.'];
        }

        $adet = max(3, min(10, $adet));

        try {
            $yanit = GeminiIstemci::post(
                static::govde($sektorAdi, $naceKodu, $tehlikeSinifi, $isKolu, $ekBaglam, $adet),
                45,
            );

            if ($yanit->failed()) {
                Log::warning('Gemini işe özgü eğitim konusu başarısız', ['durum' => $yanit->status(), 'sektor' => $sektorAdi]);

                return ['konular' => [], 'hata' => 'Yapay zekâ API şu an yanıt vermedi (HTTP '.$yanit->status().'), birazdan tekrar deneyin.'];
            }

            $ham = json_decode((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'), true);
            $gorulen = [];
            $konular = [];

            foreach (is_array($ham) ? $ham : [] as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $ad = trim((string) ($m['ad'] ?? ''));
                if ($ad === '') {
                    continue;
                }
                $anahtar = mb_strtolower($ad);
                if (isset($gorulen[$anahtar])) {
                    continue;
                }
                $gorulen[$anahtar] = true;

                $hedef = trim((string) ($m['hedef'] ?? ''));
                $egitici = trim((string) ($m['egitici'] ?? ''));
                $konular[] = [
                    'ad' => $ad,
                    'hedef' => $hedef !== '' ? $hedef : $ad.' kaynaklı tehlikeleri tanımak, sahada güvenli çalışma kurallarını ve korunma önlemlerini uygulamak.',
                    'egitici' => $egitici !== '' ? $egitici : IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                ];

                if (count($konular) >= $adet) {
                    break;
                }
            }

            return [
                'konular' => $konular,
                'hata' => $konular !== [] ? null : 'Bu sektör için eğitim konusu üretilemedi; sektörü veya açıklamayı netleştirip tekrar deneyin.',
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini işe özgü eğitim konusu istisna', ['hata' => $e->getMessage(), 'sektor' => $sektorAdi]);

            return ['konular' => [], 'hata' => 'Yapay zekâ API bağlantısı kurulamadı, birazdan tekrar deneyin.'];
        }
    }

    /**
     * Üretilen konuları kullanıcının İşe Özgü Eğitim Konuları kütüphanesine kaydeder
     * (aynı ad + aynı iş kolunda mükerrer kayıt oluşturmaz).
     *
     * @param  array<int, array{ad: string, hedef?: ?string, egitici?: ?string}>  $konular
     * @param  array<int, string>  $isKalemleri
     */
    public static function kutuphaneyeKaydet(
        int $userId,
        array $konular,
        ?string $naceKapsami = null,
        ?string $isKolu = null,
        array $isKalemleri = [],
    ): int {
        $naceOnekleri = IseOzguEgitimKutuphanesi::naceHazirla($naceKapsami);
        $temizIsKolu = filled($isKolu) ? trim((string) $isKolu) : null;
        $hedefIsKolu = $temizIsKolu !== null ? mb_strtolower($temizIsKolu) : null;

        $mevcut = IseOzguEgitimKonusu::query()
            ->where('user_id', $userId)
            ->whereNull('sistem_anahtari')
            ->where('gizli', false)
            ->get(['ad', 'is_kolu'])
            ->filter(fn (IseOzguEgitimKonusu $k) => $hedefIsKolu === null
                ? blank($k->is_kolu)
                : mb_strtolower(trim((string) $k->is_kolu)) === $hedefIsKolu)
            ->map(fn (IseOzguEgitimKonusu $k) => mb_strtolower(trim((string) $k->ad)))
            ->flip();

        $eklenen = 0;

        foreach ($konular as $k) {
            $ad = trim((string) ($k['ad'] ?? ''));
            if ($ad === '') {
                continue;
            }
            $anahtar = mb_strtolower($ad);
            if ($mevcut->has($anahtar)) {
                continue;
            }

            IseOzguEgitimKonusu::create([
                'user_id' => $userId,
                'ad' => $ad,
                'hedef' => filled($k['hedef'] ?? null) ? trim((string) $k['hedef']) : null,
                'egitici' => filled($k['egitici'] ?? null) ? trim((string) $k['egitici']) : IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                'nace_onekleri' => $naceOnekleri,
                'is_kalemleri' => array_values(array_filter($isKalemleri)) ?: null,
                'is_kolu' => $temizIsKolu,
            ]);

            $mevcut->put($anahtar, true);
            $eklenen++;
        }

        return $eklenen;
    }

    /**
     * Seçilen birden çok (veya tüm) sektör için Gemini API ile 4. bölüm konularını üretip
     * NACE ön ekleriyle kütüphaneye kaydeder.
     *
     * @param  array<int, string>  $sektorAnahtarlari
     * @return array{toplam_eklenen: int, basarili_sektor: int, hatalar: array<int, string>}
     */
    public static function sektorlerIcinTopluUret(
        int $userId,
        array $sektorAnahtarlari,
        int $sektorBasinaAdet = 5,
        ?string $isKolu = null,
        ?string $ekBaglam = null,
    ): array {
        $sektorler = static::sektorSecenekleri();
        $toplamEklenen = 0;
        $basarili = 0;
        $hatalar = [];

        foreach ($sektorAnahtarlari as $anahtar) {
            $ad = $sektorler[$anahtar] ?? null;
            if (! $ad) {
                continue;
            }

            $nace = static::sektorNaceOnekleri($anahtar);
            $sonuc = static::uret($ad, $nace, null, $isKolu, $ekBaglam, $sektorBasinaAdet);

            if ($sonuc['hata'] !== null) {
                $hatalar[] = $ad.': '.$sonuc['hata'];

                continue;
            }

            $toplamEklenen += static::kutuphaneyeKaydet($userId, $sonuc['konular'], $nace, $isKolu);
            $basarili++;
        }

        return [
            'toplam_eklenen' => $toplamEklenen,
            'basarili_sektor' => $basarili,
            'hatalar' => $hatalar,
        ];
    }

    /** @return array<string, mixed> */
    private static function govde(
        string $sektorAdi,
        ?string $naceKodu,
        ?string $tehlikeSinifi,
        ?string $isKolu,
        ?string $ekBaglam,
        int $adet,
    ): array {
        $satirlar = ["Sektör / Faaliyet Alanı: {$sektorAdi}"];
        if (filled($naceKodu)) {
            $satirlar[] = "NACE Kapsamı / Kodu: {$naceKodu}";
        }
        if (filled($tehlikeSinifi)) {
            $etiket = match ($tehlikeSinifi) {
                'cok_tehlikeli' => 'Çok Tehlikeli',
                'tehlikeli' => 'Tehlikeli',
                default => 'Az Tehlikeli',
            };
            $satirlar[] = "Tehlike Sınıfı: {$etiket}";
        }
        if (filled($isKolu)) {
            $satirlar[] = "Çalışan Grubu / İş Kolu: {$isKolu}";
        }
        if (filled($ekBaglam)) {
            $satirlar[] = "Ek Saha Bağlamı / Ekipmanlar: {$ekBaglam}";
        }

        $baglamMetni = implode("\n", $satirlar);

        $istem = <<<PROMPT
        Sen Türkiye 6331 sayılı İş Sağlığı ve Güvenliği Kanunu ile Çalışanların İş Sağlığı ve Güvenliği
        Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026) mevzuatına hâkim A Sınıfı bir
        İş Güvenliği Uzmanısın.

        {$baglamMetni}

        Bu sektör / çalışan grubu için Yıllık Eğitim Planı ve Eğitim Katılım Formu'nun 4. Bölümü
        ("İşe ve İşyerine Özgü Riskler") kapsamında yüz yüze / uygulamalı verilecek tam {$adet} adet
        SOMUT, SAHAYA ÖZGÜ eğitim konusu üret.

        Kurallar:
        - Genel geçer temel konuları (ör. "Çalışma mevzuatı", "İş kazası nedenleri", "İlk yardım",
          "Genel yangın", "Ekranlı araçlar" gibi 1., 2. veya 3. bölüm konularını) TEKRARLAMA.
        - Doğrudan bu sektörün / ekibin gerçek makine, ekipman, kimyasal, çalışma yöntemi ve saha
          risklerine odaklanan net başlıklar yaz.
        - Her madde için:
          - ad: Kısa, resmi eğitim konu başlığı (3-10 kelime, madde işareti olmadan).
          - hedef: Çalışanın bu eğitim sonunda kazanacağı somut davranış/korunma hedefi (1-2 cümle).
          - egitici: Genellikle "İŞ GÜVENLİĞİ UZMANI" (doğrudan mesleki maruziyet/sağlık ağırlıklıysa
            "İŞ GÜVENLİĞİ UZMANI / İŞYERİ HEKİMİ").
        - Tamamen Türkçe yaz. Yanıtı SADECE JSON dizisi olarak ver.
        PROMPT;

        $metin = ['type' => 'STRING'];

        return [
            'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
            'generationConfig' => [
                'temperature' => 0.35,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'ad' => $metin,
                            'hedef' => $metin,
                            'egitici' => $metin,
                        ],
                        'required' => ['ad', 'hedef', 'egitici'],
                    ],
                ],
            ],
        ];
    }
}
