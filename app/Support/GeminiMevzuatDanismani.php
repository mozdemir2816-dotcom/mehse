<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mevzuata Sor (Yapay Zekâ Destekli İSG Mevzuat Danışmanı) — isgpratik Mevzuat
 * Kütüphanesi karşılığı. En sık sorulan 6 temel İSG mevzuat sorusu için anında
 * doğrulanmış yasal yanıt verir; serbest sorularda Gemini API aktifse ilgili
 * yönetmelik/kanun maddeleriyle yanıt üretir, API anahtarı yoksa yerleşik
 * mevzuat dizininden eşleşen hükümleri derler.
 */
class GeminiMevzuatDanismani
{
    /**
     * Tek tıkla sorulabilen hazır mevzuat soruları ve doğrulanmış yasal yanıtları.
     *
     * @var array<string, array{soru: string, yanit: string, anahtar_kelimeler: array<int, string>}>
     */
    public const HAZIR_SORULAR = [
        'risk_yenileme' => [
            'soru' => 'Risk değerlendirmesi ve acil durum planı kaç yılda bir yenilenir?',
            'yanit' => "İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği m.12 ve İşyerlerinde Acil Durumlar Hakkında Yönetmelik m.14 uyarınca yenileme periyotları tehlike sınıfına göre aynıdır:\n• Çok Tehlikeli sınıftaki işyerlerinde: En geç 2 yılda bir\n• Tehlikeli sınıftaki işyerlerinde: En geç 4 yılda bir\n• Az Tehlikeli sınıftaki işyerlerinde: En geç 6 yılda bir\nAyrıca işyerinin taşınması, bina/teknoloji değişikliği, iş kazası, meslek hastalığı veya ramak kala olay yaşanması halinde süre beklenmeksizin kısmen veya tamamen yenilenir.",
            'anahtar_kelimeler' => ['risk', 'yenileme', 'kaç yılda', 'acil durum planı', 'geçerlilik'],
        ],
        'egitim_sureleri' => [
            'soru' => 'Temel İSG eğitim süreleri ve periyotları tehlike sınıfına göre nedir?',
            'yanit' => "Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik m.6 uyarınca asgari eğitim süreleri ve tekrar periyotları:\n• Az Tehlikeli işyerleri: En az 8 saat (3 yılda bir tekrar)\n• Tehlikeli işyerleri: En az 12 saat (2 yılda bir tekrar)\n• Çok Tehlikeli işyerleri: En az 16 saat (Yılda bir tekrar)\nİşe girişte ayrıca her çalışana işe başlamadan önce en az 2 saatlik pratik İşbaşı Oryantasyon Eğitimi verilmesi zorunludur.",
            'anahtar_kelimeler' => ['eğitim', 'kaç saat', 'temel isg', 'oryantasyon', 'işbaşı'],
        ],
        'destek_ilkyardim' => [
            'soru' => 'İlk yardımcı ve acil durum destek elemanı (söndürme/kurtarma/koruma) sayıları nasıl hesaplanır?',
            'yanit' => "1) İlk Yardım Yönetmeliği m.19 uyarınca sertifikalı ilk yardımcı sayısı:\n• Çok Tehlikeli işyerlerinde: Her 10 çalışana 1 ilk yardımcı\n• Tehlikeli işyerlerinde: Her 15 çalışana 1 ilk yardımcı\n• Az Tehlikeli işyerlerinde: Her 20 çalışana 1 ilk yardımcı\n\n2) İşyerlerinde Acil Durumlar Hakkında Yönetmelik m.11 uyarınca Söndürme, Kurtarma ve Koruma ekiplerinin her biri için ayrı ayrı:\n• Çok Tehlikeli işyerlerinde: Her 30 çalışana kadar 1'er destek elemanı\n• Tehlikeli işyerlerinde: Her 40 çalışana kadar 1'er destek elemanı\n• Az Tehlikeli işyerlerinde: Her 50 çalışana kadar 1'er destek elemanı görevlendirilir.",
            'anahtar_kelimeler' => ['ilk yardımcı', 'ilkyardımcı', 'destek elemanı', 'söndürme', 'kurtarma', 'koruma', 'kontenjan'],
        ],
        'kurul_temsilci' => [
            'soru' => 'İSG Kurulu ne zaman zorunludur ve Çalışan Temsilcisi sayısı kaç olmalıdır?',
            'yanit' => "1) 6331 sayılı Kanun m.22 ve İSG Kurulları Hakkında Yönetmelik uyarınca:\n• 50 ve daha fazla çalışanın bulunduğu ve 6 aydan fazla süren sürekli işlerin yapıldığı işyerlerinde İSG Kurulu kurulması zorunludur.\n• Kurul; Çok Tehlikeli sınıfta ayda 1, Tehlikeli sınıfta 2 ayda 1, Az Tehlikeli sınıfta 3 ayda 1 toplanabilir.\n\n2) 6331 sayılı Kanun m.20 uyarınca Çalışan Temsilcisi sayısı:\n• 2 – 50 çalışan: 1 temsilci\n• 51 – 100 çalışan: 2 temsilci\n• 101 – 500 çalışan: 3 temsilci\n• 501 – 1000 çalışan: 4 temsilci\n• 1001 – 2000 çalışan: 5 temsilci\n• 2001 ve üzeri çalışan: 6 temsilci.",
            'anahtar_kelimeler' => ['kurul', 'temsilci', '50 çalışan', 'toplantı'],
        ],
        'saglik_muayene' => [
            'soru' => 'Periyodik sağlık muayenesi (EK-2) hangi aralıklarla yenilenmelidir?',
            'yanit' => "6331 sayılı Kanun m.15 ve İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik uyarınca:\n• Çok Tehlikeli sınıftaki işyerlerinde: En geç yılda 1 defa\n• Tehlikeli sınıftaki işyerlerinde: En geç 3 yılda 1 defa\n• Az Tehlikeli sınıftaki işyerlerinde: En geç 5 yılda 1 defa\n• Özel politika gerektiren çalışanlarda (çocuk, genç ve gebe çalışanlar): En geç 6 ayda 1 defa periyodik sağlık muayenesi (EK-2) tekrarlanır.",
            'anahtar_kelimeler' => ['sağlık', 'muayene', 'ek-2', 'periyodik muayene', 'hekim'],
        ],
        'kaza_bildirim' => [
            'soru' => 'İş kazası ve meslek hastalığı SGK bildirim süresi kaç gündür?',
            'yanit' => "5510 sayılı Sosyal Sigortalar ve Genel Sağlık Sigortası Kanunu m.13-14 ve 6331 sayılı Kanun m.14 uyarınca:\n• İşveren, iş kazasını kazadan sonraki 3 İŞ GÜNÜ içinde SGK'ya (e-Bildirge / İş Kazası ve Meslek Hastalığı Bildirim ekranından) bildirmek zorundadır.\n• Meslek hastalığını ise öğrendiği tarihten itibaren 3 İŞ GÜNÜ içinde SGK'ya bildirmekle yükümlüdür.\n• Sağlık hizmeti sunucuları (hastaneler) kendilerine intikal eden iş kazalarını en geç 10 gün içinde SGK'ya bildirir.",
            'anahtar_kelimeler' => ['iş kazası', 'bildirim', 'sgk', 'kaç gün', '3 iş günü', 'meslek hastalığı'],
        ],
    ];

    /**
     * Kullanıcının sorusunu yanıtlar ve dayanak mevzuat kartlarını döner.
     *
     * @return array{yanit: string, kaynaklar: array<int, array{baslik: string, kategori: string, aciklama: string}>}
     */
    public static function sor(string $soru): array
    {
        $temizSoru = trim($soru);
        if ($temizSoru === '') {
            return ['yanit' => '', 'kaynaklar' => []];
        }

        $kaynaklar = static::ilgiliMevzuatBul($temizSoru);

        // 1. Hazır sorulardan biriyle birebir veya çok yakın eşleşiyorsa anında doğrulanmış yanıtı dön
        foreach (self::HAZIR_SORULAR as $hazir) {
            if (Str::lower($hazir['soru']) === Str::lower($temizSoru)) {
                return ['yanit' => $hazir['yanit'], 'kaynaklar' => $kaynaklar];
            }
        }

        // 2. Gemini API aktifse yapay zekâdan madde dayanaklı yanıt al
        if (filled(config('services.gemini.key'))) {
            $aiYanit = static::geminiIleYanitla($temizSoru, $kaynaklar);
            if (filled($aiYanit)) {
                return ['yanit' => $aiYanit, 'kaynaklar' => $kaynaklar];
            }
        }

        // 3. Yedek (API anahtarı yoksa veya bağlantı kurulamadığında): anahtar kelime eşleşmesi
        $kucuk = Str::lower($temizSoru);
        foreach (self::HAZIR_SORULAR as $hazir) {
            foreach ($hazir['anahtar_kelimeler'] as $kelime) {
                if (Str::contains($kucuk, $kelime)) {
                    return ['yanit' => $hazir['yanit'], 'kaynaklar' => $kaynaklar];
                }
            }
        }

        if ($kaynaklar !== []) {
            $satirlar = collect($kaynaklar)->map(fn (array $m) => "• {$m['baslik']}: {$m['aciklama']}")->implode("\n");

            return [
                'yanit' => "Sorunuzla ilgili mevzuat dizinimizde bulunan temel düzenlemeler:\n{$satirlar}\n\nNot: Ayrıntılı madde metni için aşağıdaki 'mevzuat.gov.tr'de Gör' bağlantısını kullanabilirsiniz.",
                'kaynaklar' => $kaynaklar,
            ];
        }

        return [
            'yanit' => 'Bu soru için hazır dizinde doğrudan eşleşme bulunamadı. Lütfen "risk değerlendirmesi", "eğitim süreleri", "ilk yardımcı", "kurul", "muayene" veya "iş kazası bildirimi" gibi anahtar kelimelerle tekrar deneyin ya da mevzuat.gov.tr üzerinden tam metin araması yapın.',
            'kaynaklar' => [],
        ];
    }

    /**
     * Mevzuat başlığı için doğrudan mevzuat.gov.tr bağlantısı üretir.
     */
    public static function resmiMevzuatUrl(string $baslik): string
    {
        if (preg_match('/\b(6331|4857|5510)\b/', $baslik, $eslesme)) {
            return "https://www.mevzuat.gov.tr/mevzuat?MevzuatNo={$eslesme[1]}&MevzuatTur=1&MevzuatTertip=5";
        }

        return 'https://www.mevzuat.gov.tr';
    }

    /**
     * 6331 Sayılı İSG Kanunu Sade Özet Rehberi (isgpratik 6331 detay sayfası karşılığı):
     * 3 pratik özet kartı (Neyi Kapsar / Kimi Bağlar / Pratikte Ne Yapmalı - 7 adım)
     * ve 8 sadeleştirilmiş temel başlık.
     *
     * @return array{
     *     kartlar: array{neyi_kapsar: string, kimi_baglar: string, pratik_adimlar: array<int, string>},
     *     bolumler: array<int, array{baslik: string, maddeler: string, ozet: string}>
     * }
     */
    public static function kanun6331Rehberi(): array
    {
        return [
            'kartlar' => [
                'neyi_kapsar' => 'İşyerlerinde iş sağlığı ve güvenliğinin sağlanması, mevcut sağlık ve güvenlik şartlarının iyileştirilmesi için işveren ile çalışanların görev, yetki, sorumluluk, hak ve yükümlülüklerini düzenler.',
                'kimi_baglar' => 'Kamu ve özel sektöre ait bütün işlere ve işyerlerine; bu işyerlerinin işverenleri, işveren vekilleri, çırak ve stajyerler de dâhil olmak üzere tüm çalışanlarına faaliyet konularına bakılmaksızın uygulanır.',
                'pratik_adimlar' => [
                    '1. İşyerinin 6 haneli NACE koduna göre Tehlike Sınıfını (Az Tehlikeli / Tehlikeli / Çok Tehlikeli) belirleyin.',
                    '2. İSG-KATİP üzerinden İş Güvenliği Uzmanı (İGU), İşyeri Hekimi ve (Çok Tehlikeli 10+ çalışanda) DSP atamasını yapın.',
                    '3. Risk Değerlendirmesi ekibini kurup analiz raporunu ve Acil Durum Planını hazırlayın.',
                    '4. Çalışan Temsilcisini seçin; ilk yardımcı ile söndürme, kurtarma ve koruma destek elemanlarını atayın.',
                    '5. Çalışanlara işe giriş / periyodik sağlık muayenesi (EK-2), Temel İSG Eğitimi ve İşbaşı Oryantasyon Eğitimi verin.',
                    '6. İş ekipmanlarının periyodik kontrollerini, ortam ölçümlerini ve KKD zimmet tutanaklarını tamamlayın.',
                    '7. 50 ve üzeri çalışan varsa İSG Kurulunu kurun; Onaylı Tespit ve Öneri Defterini notere/İŞKUR\'a onaylatıp düzenli işleyin.',
                ],
            ],
            'bolumler' => [
                [
                    'baslik' => '1. 6331 Sayılı Kanun Nedir ve Neyi Değiştirdi?',
                    'maddeler' => 'Madde 1 – 3',
                    'ozet' => 'Kazadan sonra telafi ("reaktif") yaklaşımı yerine kaza olmadan önleme ("proaktif") yaklaşımını getirmiştir. İşçi/memur ayrımı olmaksızın 1 çalışanı olan işyerlerini bile kapsama almıştır.',
                ],
                [
                    'baslik' => '2. Kimler Kapsamda, Kimler İstisna?',
                    'maddeler' => 'Madde 2',
                    'ozet' => 'TSK, genel kolluk kuvvetleri ve MİT faaliyetleri, afet/acil durum müdahale ekipleri, ev hizmetleri, çalışan istihdam etmeksizin kendi namına mal/hizmet üretenler ve hükümlü/tutuklulara yönelik ıslah işleri istisnadır.',
                ],
                [
                    'baslik' => '3. İşverenin Genel Yükümlülüğü ve Risklerden Korunma İlkeleri',
                    'maddeler' => 'Madde 4 – 5',
                    'ozet' => 'İşveren; mesleki risklerin önlenmesi, eğitim ve bilgi verilmesi dâhil her türlü tedbiri almak, organizasyonu yapmak, KKD sağlamak ve alınan tedbirlere uyulup uyulmadığını izlemek/denetlemekle yükümlüdür. İSG tedbirlerinin maliyeti çalışanlara yansıtılamaz.',
                ],
                [
                    'baslik' => '4. İGU, İşyeri Hekimi ve DSP Görevlendirme & Onaylı Defter',
                    'maddeler' => 'Madde 6 – 8',
                    'ozet' => 'İşveren, tehlike sınıfına uygun İGU ve İşyeri Hekimi görevlendirir. Uzman ve hekimin onaylı deftere yazdığı tespit ve öneriler işverene tebliğ edilmiş sayılır. Hayati tehlike tedbir alınmazsa Bakanlığa bildirilir.',
                ],
                [
                    'baslik' => '5. Risk Değerlendirmesi, Acil Durumlar ve Tahliye',
                    'maddeler' => 'Madde 10 – 13',
                    'ozet' => 'Her işveren risk değerlendirmesi yapmak/yaptırmak, acil durum planı hazırlamak ve tatbikat yapmak zorundadır. Ciddi ve yakın tehlike halinde çalışanlar çalışmaktan kaçınma hakkına (m.13) sahiptir.',
                ],
                [
                    'baslik' => '6. İş Kazası ve Meslek Hastalığı Kayıt ve Bildirimi',
                    'maddeler' => 'Madde 14',
                    'ozet' => 'İşveren bütün iş kazalarının ve meslek hastalıklarının kaydını tutar, inceleme raporu hazırlar, yaralanmasız (ramak kala) olayları inceler ve iş kazasını kazadan sonraki 3 İŞ GÜNÜ içinde SGK\'ya bildirir.',
                ],
                [
                    'baslik' => '7. Sağlık Gözetimi (EK-2) ve Çalışanların İSG Eğitimi',
                    'maddeler' => 'Madde 15 – 17',
                    'ozet' => 'İşe girişte, iş değişikliğinde, iş kazası/meslek hastalığı sonrası işe dönüşte ve tehlike sınıfına göre periyodik (1/3/5 yıl) sağlık muayenesi zorunludur. Tehlikeli ve çok tehlikeli işlerde mesleki eğitim / MYK belgesi olmayan çalıştırılamaz.',
                ],
                [
                    'baslik' => '8. Çalışan Temsilcisi, İSG Kurulu, İşin Durdurulması ve İdari Para Cezaları',
                    'maddeler' => 'Madde 20 – 26',
                    'ozet' => 'Çalışan sayısına göre 1–6 çalışan temsilcisi seçilir; 50+ çalışanda İSG Kurulu kurulur. Hayati tehlike veya çok tehlikeli sınıfta risk değerlendirmesi olmaması işin durdurulması (m.25) sebebidir. İhlallerde m.26 uyarınca çalışan sayısı ve tehlike sınıfı çarpanlı İPC uygulanır.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{baslik: string, kategori: string, aciklama: string}>
     */
    public static function ilgiliMevzuatBul(string $soru): array
    {
        $kelimeler = collect(preg_split('/\s+/u', Str::lower($soru)))
            ->map(fn ($k) => trim($k, ".,?!;:()\"'"))
            ->filter(fn ($k) => mb_strlen($k) >= 3)
            ->values();

        if ($kelimeler->isEmpty()) {
            return [];
        }

        return collect(config('isg.mevzuat.liste', []))
            ->map(function (array $m) use ($kelimeler): array {
                $metin = Str::lower($m['baslik'].' '.$m['aciklama']);
                $puan = $kelimeler->sum(fn (string $k) => Str::contains($metin, $k) ? 1 : 0);

                return ['mevzuat' => $m, 'puan' => $puan];
            })
            ->filter(fn (array $x) => $x['puan'] > 0)
            ->sortByDesc('puan')
            ->take(4)
            ->pluck('mevzuat')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{baslik: string, kategori: string, aciklama: string}>  $kaynaklar
     */
    private static function geminiIleYanitla(string $soru, array $kaynaklar): ?string
    {
        $baglam = collect($kaynaklar)
            ->map(fn (array $m) => "- {$m['baslik']}: {$m['aciklama']}")
            ->implode("\n");

        $istem = <<<PROMPT
        Sen Türkiye'de 6331 sayılı İş Sağlığı ve Güvenliği Kanunu, 4857 sayılı İş Kanunu, 5510 sayılı Kanun ve ilgili İSG yönetmeliklerine hâkim bir A Sınıfı İş Güvenliği Uzmanı ve İSG Mevzuat Danışmanısın.

        Kullanıcının sorusu: "{$soru}"

        İlgili mevzuat bağlamı:
        {$baglam}

        Kurallar:
        1. Yanıtını Türkçe, net, maddeli ve doğrudan sahada uygulanabilir şekilde yaz (en fazla 6-8 kısa satır).
        2. İlgili kanun veya yönetmelik adını açıkça belirt; emin olmadığın madde numarasını uydurma.
        3. Tehlike sınıfına (Az Tehlikeli / Tehlikeli / Çok Tehlikeli) veya çalışan sayısına göre değişen bir kural varsa ayrı ayrı belirt.
        PROMPT;

        try {
            $yanit = GeminiIstemci::post([
                'contents' => [['role' => 'user', 'parts' => [['text' => $istem]]]],
                'generationConfig' => [
                    'temperature' => 0.2,
                ],
            ], 35);

            if ($yanit->failed()) {
                return null;
            }

            $metin = trim((string) data_get($yanit->json(), 'candidates.0.content.parts.0.text'));

            return $metin !== '' ? $metin : null;
        } catch (Throwable $e) {
            Log::warning('Gemini mevzuat danışmanı istisna', ['hata' => $e->getMessage()]);

            return null;
        }
    }
}
