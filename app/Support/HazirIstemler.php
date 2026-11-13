<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Hazır İstemler (13.11.2026) — yapay zekâ kotası dolduğunda ya da elle çalışmak
 * istendiğinde Gemini sohbetine yapıştırılacak istemler. Her istem firma / sektör
 * bilgisini içerir ve çıktıyı sistemin MEVCUT içe aktarıcılarının okuduğu biçimde ister:
 *  - risk_analizi        → RiskDegerlendirmesiExcelOkuyucu (Kayıtlı Değerlendirmeler → Excel Yükle)
 *  - tehlike_kutuphanesi → TehlikeExcelIceAktarici (Tehlike Kütüphanesi → Excel içe aktar)
 *  - talimat             → TalimatSablonuExcelIceAktarici (Talimat Oluştur → Excel ile yükle)
 *  - soru_bankasi        → SoruBankasiIceAktarici (Soru Bankası → Toplu taslak yükle, JSON)
 * Mevcut önlem hiçbir istemde doldurtulmaz (firmaya özgü; kullanıcı kararı 12.11.2026).
 */
class HazirIstemler
{
    public const TURLER = [
        'risk_analizi' => [
            'ad' => 'Risk analizi hazırlama',
            'aciklama' => 'Seçilen firmanın faaliyetine göre risk değerlendirme tablosu (Fine-Kinney ya da 5×5).',
            'cikti' => 'Excel tablosu',
            'yukle' => 'Risk Değerlendirmesi → Kayıtlı Değerlendirmeler → "Excel Yükle"',
            'firma' => true,
            'adet' => 100,
        ],
        'tehlike_kutuphanesi' => [
            'ad' => 'Tehlike kütüphanesine yeni maddeler',
            'aciklama' => 'Bir sektör ya da bölüm için kütüphaneye eklenecek tehlike maddeleri.',
            'cikti' => 'Excel tablosu',
            'yukle' => 'Risk Değerlendirmesi → Tehlike Kütüphanesi → "Excel içe aktar"',
            'firma' => false,
            'adet' => 40,
        ],
        'talimat' => [
            'ad' => 'Talimat şablonları',
            'aciklama' => 'Makine, ekipman ya da iş için güvenli çalışma talimatları.',
            'cikti' => 'Excel tablosu',
            'yukle' => 'Resmi Belge & Kurul → Talimat Oluştur → "Excel ile yükle"',
            'firma' => false,
            'adet' => 5,
        ],
        'soru_bankasi' => [
            'ad' => 'Soru bankası soruları',
            'aciklama' => 'Eğitim sınavları için kaynaklı, 4 şıklı sorular.',
            'cikti' => 'JSON dosyası',
            'yukle' => 'Eğitim → Soru Bankası → "Toplu taslak yükle" (.json dosyası)',
            'firma' => false,
            'adet' => 20,
        ],
    ];

    /**
     * @param  array{yontem?: string, adet?: int|string, konu?: string, kategori?: string, ek?: string}  $s
     */
    public static function istem(string $tur, ?Firma $firma, array $s = []): string
    {
        if (! isset(self::TURLER[$tur])) {
            throw new \InvalidArgumentException("Bilinmeyen istem türü: {$tur}");
        }

        $adet = max(1, min(200, (int) ($s['adet'] ?? 0) ?: self::TURLER[$tur]['adet']));
        $ek = trim((string) ($s['ek'] ?? ''));

        $govde = match ($tur) {
            'risk_analizi' => static::riskAnalizi($firma, (string) ($s['yontem'] ?? 'fine_kinney'), $adet, ! empty($s['parca'])),
            'tehlike_kutuphanesi' => static::tehlikeKutuphanesi($firma, trim((string) ($s['kategori'] ?? '')), $adet, ! empty($s['parca'])),
            'talimat' => static::talimat($firma, $adet),
            'soru_bankasi' => static::soruBankasi($firma, (string) ($s['konu'] ?? ''), $adet),
        };

        return implode("\n\n", array_filter([
            'Sen 6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili yönetmeliklere hâkim, deneyimli bir A sınıfı İş Güvenliği Uzmanısın. Türkçe yaz.',
            static::firmaBilgisi($firma),
            $ek !== '' ? "EK BİLGİ (mutlaka dikkate al):\n{$ek}" : null,
            $govde,
        ]));
    }

    /** @return array<int, string> İstemin üstünde gösterilecek kullanım adımları. */
    public static function adimlar(string $tur, bool $parca = false): array
    {
        $yukle = self::TURLER[$tur]['yukle'];

        if ($tur === 'soru_bankasi') {
            return [
                "Gemini'de yeni bir sohbet açın, Pro (Düşünme) modelini seçin ve istemi yapıştırın.",
                'Gemini\'nin verdiği JSON kod bloğunu kopyalayın; Not Defteri\'ne yapıştırıp "sorular.json" adıyla, Kodlama: UTF-8 seçerek kaydedin.',
                "Yükleyin: {$yukle}. Sorular TASLAK olarak girer; kaynaklarını kontrol edip yayımlayın.",
            ];
        }

        return [
            "Gemini'de yeni bir sohbet açın, Pro (Düşünme) modelini seçin ve istemi yapıştırın.",
            $tur === 'talimat' || ! $parca
                ? 'Gemini\'nin verdiği tabloyu kopyalayıp boş bir Excel sayfasına A1 hücresinden yapıştırın, .xlsx olarak kaydedin.'
                : 'Gemini tabloyu parça parça verir: ilk parçayı boş bir Excel sayfasına A1 hücresinden yapıştırın; "devam" yazıp gelen her parçayı bir öncekinin hemen altına yapıştırın. Araya giren başlık satırlarını silmenize gerek yok. "BİTTİ" yazınca .xlsx olarak kaydedin.',
            "Yükleyin: {$yukle}.",
        ];
    }

    private static function firmaBilgisi(?Firma $f): ?string
    {
        if (! $f) {
            return null;
        }

        $kalemler = collect((array) $f->is_kalemleri)
            ->map(fn ($k) => is_array($k) ? implode(' ', array_filter($k, 'is_scalar')) : (string) $k)
            ->filter()->implode(', ');

        $satirlar = array_filter([
            'Unvan: '.$f->unvan,
            filled($f->nace_kodu) ? 'NACE: '.$f->nace_kodu.(filled($f->nace_aciklama) ? ' — '.$f->nace_aciklama : '') : null,
            filled($f->tehlike_sinifi) ? 'Tehlike sınıfı: '.config('isg.tehlike_siniflari.'.$f->tehlike_sinifi, $f->tehlike_sinifi) : null,
            $f->calisan_sayisi ? 'Çalışan sayısı: '.$f->calisan_sayisi : null,
            filled($f->il) ? 'Konum: '.trim($f->il.' '.($f->ilce ?? '')) : null,
            $kalemler !== '' ? 'İş kalemleri / faaliyetler: '.$kalemler : null,
        ]);

        return "İŞYERİ BİLGİSİ:\n- ".implode("\n- ", $satirlar);
    }

    private static function olcekMetni(string $yontem, string $eksen): string
    {
        return implode(' / ', array_map(fn ($v) => str_replace('.', ',', (string) $v), array_keys(RiskSkorlama::olcek($yontem, $eksen))));
    }

    /** Varsayılan: tamamı tek yanıtta (Pro modeli 120 maddeyi tek seferde verdi, 13.11.2026); parça: kesilen modeller için. */
    private static function parcaKurali(bool $parca, int $boyut, int $adet): string
    {
        return $parca
            ? "PARÇA KURALI: Her yanıtta EN FAZLA {$boyut} satır ver. {$boyut} satırdan sonra dur ve tablonun altına yalnız şunu yaz: \"Devam için devam yazın (x/{$adet})\". Ben \"devam\" yazınca Sıra numarasından kaldığın yerden, aynı sütunlarla sürdür. {$adet}. satırı yazınca tablonun altına \"BİTTİ\" yaz."
            : "{$adet} satırın TAMAMINI tek yanıtta, tek tablo olarak ver; satır atlama, kısaltma. Yine de yanıt sınırına takılırsan kaldığın satırda dur; \"devam\" yazınca Sıra numarasından sürdür.";
    }

    private static function riskAnalizi(?Firma $f, string $yontem, int $adet, bool $parca = false): string
    {
        $fk = $yontem !== 'matris_5x5';
        $sutunlar = implode(' | ', $fk
            ? ['Sıra', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut Önlem', 'Olasılık', 'Frekans', 'Şiddet', 'Önerilen Önlem', 'Sorumlu', 'Termin', 'Mevzuat', 'Son Olasılık', 'Son Frekans', 'Son Şiddet']
            : ['Sıra', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut Önlem', 'Olasılık', 'Şiddet', 'Önerilen Önlem', 'Sorumlu', 'Termin', 'Mevzuat', 'Son Olasılık', 'Son Şiddet']);
        $puan = $fk
            ? 'Fine-Kinney yöntemi. Puanları YALNIZ şu değerlerden seç — Olasılık: '.static::olcekMetni('fine_kinney', 'olasilik')
                .' · Frekans: '.static::olcekMetni('fine_kinney', 'frekans').' · Şiddet: '.static::olcekMetni('fine_kinney', 'siddet').' (bu ölçekte 100 YOK; en yüksek şiddet 40).'
            : '5×5 L tipi matris. Olasılık ve Şiddet 1–5 arası tam sayı.';
        $kim = $f ? 'Yukarıdaki işyeri için' : 'Ek bilgide anlatılan işyeri için';
        $dagilim = $fk
            ? 'Olasılık çoğu maddede 1 ya da 3 olsun; 6 ve 10 yalnız sık yaşanan, kontrolsüz durumlarda. Risk puanı (O×F×Ş) 400 üstü (tolere edilemez) yalnız gerçekten hemen durdurulması gereken maddelerde verilsin ve tüm maddelerin EN FAZLA beşte biri olsun; maddelerin çoğu 20–200 arasında kalsın.'
            : 'Olasılık çoğu maddede 2 ya da 3 olsun; 4 ve 5 yalnız sık yaşanan, kontrolsüz durumlarda. Risk puanı (O×Ş) 15 ve üstü yalnız gerçekten hemen önlem gerektiren maddelerde verilsin ve tüm maddelerin EN FAZLA beşte biri olsun.';
        $parcaKurali = static::parcaKurali($parca, 25, $adet);

        return <<<METIN
        GÖREV: {$kim} TAM OLARAK {$adet} maddelik bir risk değerlendirme tablosu hazırla (Sıra 1'den {$adet}'e kadar; daha az madde yazma, kısaltma).
        - Önce işyerinde gerçekten bulunan bölümleri kendin belirle (üretim alanları, depo, bakım, ofis, yemekhane, temizlik, yükleme-boşaltma, elektrik, acil durum, genel alanlar vb.) ve maddeleri bölümlere dengeli dağıt; her bölüme en az 4–5 madde. Aynı tehlikeyi tekrarlama.
        - Tehlike: somut durum ya da kaynak ("korumasız kayış-kasnak", "ıslak zemin"). Risk: doğurabileceği sonuç (uzuv kaybı, düşme sonucu kırık…).
        - Mevcut Önlem sütununu BOŞ bırak (işyerinde yerinde görülüp doldurulacak).
        - {$puan}
        - Puanları işyerinin bugünkü olağan çalışma koşullarına göre gerçekçi ver; her maddeyi en kötü senaryoyla puanlama. {$dagilim}
        - Önerilen Önlem: kontrol hiyerarşisine göre (önce ortadan kaldırma ve mühendislik, sonra idari önlem, en son KKD), somut ve uygulanabilir; 1–2 cümle.
        - Sorumlu: rol yaz (İşveren / İşveren Vekili, Bakım Sorumlusu, Bölüm Şefi, Ustabaşı, İSG Uzmanı, İşyeri Hekimi…); kişi adı yazma.
        - Termin sütununu BOŞ bırak (sistem risk düzeyine göre kendisi verir).
        - Mevzuat: ilgili yönetmelik ya da standart adı; emin olmadığın madde numarasını yazma.
        - Son puanlar: önerilen önlem uygulandıktan sonraki değerler; ilk puanlardan büyük olamaz (şiddet genelde aynı kalır, olasılık ve gerekirse frekans düşer).
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        {$sutunlar}
        Hücrelerde satır sonu kullanma. Puan hücrelerine yalnız sayı yaz (ondalıkta virgül).
        {$parcaKurali}
        METIN;
    }

    private static function tehlikeKutuphanesi(?Firma $f, string $kategori, int $adet, bool $parca = false): string
    {
        $kategori = $kategori !== '' ? $kategori : ($f?->nace_aciklama ?: 'Genel');
        $parcaKurali = static::parcaKurali($parca, 40, $adet);

        return <<<METIN
        GÖREV: Risk değerlendirmesinde kullanılacak bir tehlike kütüphanesi için "{$kategori}" konusunda TAM OLARAK {$adet} madde hazırla (Sıra 1'den {$adet}'e kadar; daha az yazma).
        - Her satır tek bir tehlike olsun; birbirinin tekrarı olan maddeler yazma.
        - Kategori sütununa her satırda aynen şunu yaz: {$kategori}
        - Kod: boş bırak.
        - Bölüm: tehlikenin görüldüğü alan (Depo, Bakım Atölyesi, Üretim Hattı…). Faaliyet: yapılan iş (forklift ile yükleme, kaynak…).
        - Tehlike: somut durum ya da kaynak. Risk: doğurabileceği sonuç (yaralanma, hastalık, ölüm), kısa; tehlikenin kopyası olmasın.
        - Mevcut Önlem sütununu BOŞ bırak.
        - Mevzuat: ilgili yönetmelik ya da standart adı; emin olmadığın madde numarasını yazma.
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        Sıra | Kategori | Kod | Bölüm | Faaliyet | Tehlike | Risk | Mevcut Önlem | Mevzuat
        Hücrelerde satır sonu kullanma.
        {$parcaKurali}
        METIN;
    }

    private static function talimat(?Firma $f, int $adet): string
    {
        $kategoriler = implode(', ', array_values(config('isg.talimat.kategoriler', [])));
        $kim = $f ? 'yukarıdaki işyerinde kullanılan' : 'ek bilgide yazan';

        return <<<METIN
        GÖREV: {$kim} makine, ekipman ya da işler için {$adet} adet güvenli çalışma talimatı hazırla. Ek bilgide talimat konuları yazıyorsa yalnız onları hazırla.
        - Başlık: "… Kullanma Talimatı" ya da "… Güvenli Çalışma Talimatı".
        - Kategori: şunlardan biri, aynen: {$kategoriler}
        - Açıklama: talimatın amacı, tek cümle.
        - KKD'ler: gereken kişisel koruyucu donanımlar, virgülle ayrılmış (ör. Baret, Koruyucu gözlük, Çelik burunlu iş ayakkabısı).
        - Maddeler: 10–20 kısa, uygulanabilir madde; çalışma öncesi kontrol, çalışma sırası, bakım-temizlik (enerji kesme / kilitleme), acil durum ve yasaklar sırasıyla. Maddeleri birbirinden " | " (boşluk, dik çizgi, boşluk) ile ayır; madde numarası yazma.
        - Mevzuata ve üretici kılavuzuna aykırı bir şey yazma; emin olmadığın sayısal değeri (basınç, mesafe) yazma.
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. Her talimat bir satır. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        Başlık | Kategori | Açıklama | KKD'ler (virgülle ayırın) | Maddeler (her satırda bir madde)
        Hücrelerde satır sonu kullanma (maddeleri " | " ile ayır).
        METIN;
    }

    private static function soruBankasi(?Firma $f, string $konu, int $adet): string
    {
        $konular = config('isg.soru_bankasi.konular', []);
        $konuSatiri = $konu !== '' && isset($konular[$konu])
            ? "Konu: \"{$konular[$konu]}\". Her soruda \"konu\" alanına aynen \"{$konu}\" yaz."
            : 'Konuları çeşitlendir. "konu" alanına şu anahtarlardan uygun olanı aynen yaz: '.implode(', ', array_keys($konular)).'.';
        $sektorler = implode(', ', array_keys(config('isg.risk_ai.sektorler', [])));
        $nace = $f && filled($f->nace_kodu) ? "\"nace\": [\"{$f->nace_kodu}\"]" : '"nace": []';
        $bugun = now()->toDateString();
        $ornekKonu = $konu !== '' && isset($konular[$konu]) ? $konu : 'genel_isg';

        return <<<METIN
        GÖREV: İSG eğitimi sınavları için {$adet} adet çoktan seçmeli soru hazırla.
        - {$konuSatiri}
        - Her sorunun 4 şıkkı olsun, yalnız biri doğru; şıklar birbirinden farklı ve makul uzunlukta. Doğru şıkkın yeri (A–D) dengeli dağılsın.
        - "gerekce": doğru cevabın neden doğru olduğu, 1–2 cümle.
        - "kaynaklar": en az bir gerçek, doğrulanabilir kaynak (kanun / yönetmelik adı, madde numarası emin değilsen boş bırak, url olarak https://www.mevzuat.gov.tr/ ya da ilgili resmi sayfa, "tarih": "{$bugun}"). Uydurma kaynak yazma.
        - "zorluk": kolay, orta ya da zor.
        - "kapsam.sektor": genel sorularda boş bırak (""); sektöre özgü soruda şunlardan biri: {$sektorler}.
        ÇIKTI: Yalnız tek bir JSON dizisi ver (```json kod bloğu içinde), açıklama ekleme. Her eleman şu yapıda olsun:
        {"konu": "{$ornekKonu}", "zorluk": "orta", "soru": "…", "secenekler": ["…", "…", "…", "…"], "dogru": "A", "gerekce": "…", "kapsam": {"sektor": "", {$nace}}, "kaynaklar": [{"ad": "6331 sayılı İş Sağlığı ve Güvenliği Kanunu", "url": "https://www.mevzuat.gov.tr/", "madde": "", "tarih": "{$bugun}"}]}
        Yanıt sınırına takılacaksan soruları parçalara böl: her yanıtı kendi başına geçerli, KAPANMIŞ bir JSON dizisi olarak ver; "devam" yazınca kalan soruları yeni bir dizi olarak ver (her parça ayrı dosya olarak yüklenecek).
        METIN;
    }
}
