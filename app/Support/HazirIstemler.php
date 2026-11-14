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
        'saha_gozlem' => [
            'ad' => 'Saha gözlem raporu',
            'aciklama' => 'Sahada çektiğiniz fotoğraflardaki uygunsuzluklar; Fine-Kinney puanlı bulgu tablosu.',
            'cikti' => 'Excel tablosu',
            'yukle' => "Saha → Gözlem Raporu (AI) → firma seç → 'Excel'den Bulgu Ekle'",
            'firma' => true,
            'adet' => 15,
        ],
        'dof' => [
            'ad' => 'DÖF (düzeltici-önleyici faaliyet)',
            'aciklama' => 'Tespit ettiğiniz uygunsuzluklar için öneri, öncelik, sorumlu ve terminli DÖF tablosu.',
            'cikti' => 'Excel tablosu',
            'yukle' => "Saha → DÖF → firma seç → 'Word/Excel'den Aktar'",
            'firma' => true,
            'adet' => 20,
        ],
        'kontrol_karti' => [
            'ad' => 'Kontrol kartı (fotoğraftan)',
            'aciklama' => 'Bir alanın ya da ekipmanın fotoğrafı üzerinden numaralı işaretli kontrol listesi; uygunsuzlukta Fine-Kinney önce/sonra.',
            'cikti' => 'JSON (sayfaya yapıştırılır)',
            'yukle' => "Saha → Kontrol Kartı → firma seç → 'Gemini'den Yükle'",
            'firma' => false,
            'adet' => 12,
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
            'saha_gozlem' => static::sahaGozlem($adet),
            'dof' => static::dof(),
            'kontrol_karti' => static::kontrolKarti((string) ($s['sablon'] ?? ''), $adet),
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

        if ($tur === 'saha_gozlem') {
            return [
                "Gemini'de yeni bir sohbet açın, Pro (Düşünme) modelini seçin. Önce saha fotoğraflarını sohbete ekleyin (en fazla 10), sonra istemi yapıştırıp gönderin.",
                "Gemini'nin verdiği tabloyu kopyalayıp boş bir Excel sayfasına A1 hücresinden yapıştırın, .xlsx olarak kaydedin.",
                "Yükleyin: {$yukle}. Fotoğraflar için iki yol var: (a) dosyalara 1.jpg, 2.jpg… diye Gemini'deki sırayla ad verip aynı pencerede seçin — Fotoğraf No'ya göre maddelere yerleşir; (b) fotoğrafı Excel'de maddenin satırına yapıştırın. Her uygunsuzluğun yeri fotoğrafta kırmızı çemberle kendiliğinden işaretlenir ve raporda işaretli fotoğraf kullanılır. Maddeler onay bekler; her birini kontrol edin.",
            ];
        }

        if ($tur === 'kontrol_karti') {
            return [
                "Gemini'de yeni bir sohbet açın, Pro (Düşünme) modelini seçin. Alanın ya da ekipmanın fotoğrafını (tek, genel görünüm) sohbete ekleyin, sonra istemi yapıştırıp gönderin.",
                "Gemini'nin verdiği JSON kod bloğunun tamamını kopyalayın (kod bloğunun sağ üstündeki kopyala düğmesi).",
                "Yükleyin: {$yukle} — cevabı yapıştırın, aynı fotoğrafı seçin. Alan sistemde yoksa kontrol listesi yeni şablon olarak kaydedilir; maddeleri kontrol edip kaydedin.",
            ];
        }

        if ($tur === 'dof') {
            return [
                "Gemini'de yeni bir sohbet açın, Pro (Düşünme) modelini seçin. Tespitlerinizi ek bilgiye yazın; isterseniz fotoğrafları da sohbete ekleyin, sonra istemi yapıştırın.",
                "Gemini'nin verdiği tabloyu kopyalayıp boş bir Excel sayfasına A1 hücresinden yapıştırın, .xlsx olarak kaydedin.",
                "Yükleyin: {$yukle}. Fotoğraf kullandıysanız: dosyalara 1.jpg, 2.jpg… diye ad verip aynı pencerede seçin (Fotoğraf No'ya göre yerleşir) ya da fotoğrafı Excel'de maddenin satırına yapıştırın. Her uygunsuzluğun yeri fotoğrafta madde numaralı kırmızı çemberle kendiliğinden işaretlenir. Sonra DÖF raporunu alın.",
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
        $mevcutlar = static::mevcutMaddeler($kategori);

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
        METIN.$mevcutlar;
    }

    /**
     * Klasör kütüphanede zaten varsa içindeki maddeler isteme eklenir; Gemini yalnız eksikleri yazar
     * (13.11.2026 — aynı ekipman / iş için mükerrer madde üretilmesin).
     */
    private static function mevcutMaddeler(string $kategori): string
    {
        $k = \App\Models\TehlikeKategorisi::query()->whereRaw('LOWER(ad) = ?', [mb_strtolower($kategori)])->first();
        if (! $k) {
            return '';
        }

        $maddeler = $k->tehlikeler()->orderBy('id')->limit(200)->pluck('tehlike')
            ->map(fn ($t) => '- '.\Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $t)), 140))
            ->implode(PHP_EOL);

        return $maddeler === '' ? '' : PHP_EOL.PHP_EOL.'BU KLASÖRDE ZATEN OLAN MADDELER — bunları ve anlamca aynı olanları TEKRAR YAZMA, yalnız eksik kalan tehlikeleri yaz:'.PHP_EOL.$maddeler;
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
        - Maddeler: 10–20 kısa, uygulanabilir madde; çalışma öncesi kontrol, çalışma sırası, bakım-temizlik (enerji kesme / kilitleme), acil durum ve yasaklar sırasıyla. Maddeleri birbirinden " • " (madde işareti) ile ayır; dik çizgi (|) KULLANMA, madde numarası yazma.
        - Mevzuata ve üretici kılavuzuna aykırı bir şey yazma; emin olmadığın sayısal değeri (basınç, mesafe) yazma.
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. Her talimat bir satır. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        Başlık | Kategori | Açıklama | KKD'ler (virgülle ayırın) | Maddeler (her satırda bir madde)
        Hücrelerde satır sonu kullanma (maddeleri " • " ile ayır).
        METIN;
    }

    private static function sahaGozlem(int $adet): string
    {
        $olcek = fn (string $e) => static::olcekMetni('fine_kinney', $e);

        return <<<METIN
        GÖREV: Bu sohbete eklediğim saha fotoğraflarını bir iş güvenliği uzmanı gözüyle incele ve İSG uygunsuzluklarını (en fazla {$adet} madde) tablo olarak çıkar. Ek bilgide not yazdıysam onları da madde yap.
        - Yalnız fotoğrafta gerçekten görülen uygunsuzlukları yaz; tahmin ya da fotoğrafta olmayan bir şeyi uydurma. Aynı uygunsuzluğu tekrarlama.
        - Fotoğraf No: uygunsuzluğun görüldüğü fotoğrafın sırası (1, 2, 3…). Ek bilgiden gelen maddede boş bırak.
        - Bina / Bölge: görülebiliyorsa alan adı (Depo, Üretim Hattı, Çatı…), görülmüyorsa boş.
        - Fotoğraftaki Konum: uygunsuzluğun fotoğraftaki yeri, kısa ("sol alt, zemindeki kablo").
        - Kategori: kısa etiket (Yüksekte Çalışma, Elektrik Güvenliği, KKD Eksikliği, Düzen/Temizlik, Makine Koruyucusu, Yangın Güvenliği, Kimyasal…).
        - Tespit: uygunsuzluğu açık ve somut anlat (1–2 cümle).
        - Öneriler: 1–3 somut düzeltici faaliyet; aralarına " • " koy, dik çizgi (|) KULLANMA.
        - Yasal Gerekçe: ilgili yönetmelik ya da standart adı; emin olmadığın madde numarasını yazma.
        - Puanlar Fine-Kinney ölçeğinde, YALNIZ şu değerlerle — Olasılık: {$olcek('olasilik')} · Frekans: {$olcek('frekans')} · Şiddet: {$olcek('siddet')}. Gerçekçi puanla; her maddeyi en kötü senaryoyla puanlama.
        - İşaret X / İşaret Y: uygunsuzluğun fotoğrafta işaretlenecek alanının MERKEZİ, fotoğrafın sol üst köşesine göre yüzde (0–100; X soldan sağa, Y yukarıdan aşağı). Eksik olanı değil, eksikliğin görüldüğü yeri göster (ör. "merdiven kenarında korkuluk yok" → korkuluğun olması gereken merdiven kenarı). Sistem bu noktaya madde numaralı kırmızı çember çizip raporda o fotoğrafı kullanır; dikkatli ve doğru ver. Aynı fotoğrafta birden fazla uygunsuzluk varsa her biri kendi yerini göstersin; çemberler aynı noktaya konmasın.
        - İşaret Çap: çemberin içine alacağı alanın genişliği, fotoğraf genişliğinin yüzdesi (5–60; küçük nesne 8–15, merdiven/raf gibi büyük alan 25–45). Ek bilgiden gelen, fotoğrafı olmayan maddede üçünü de boş bırak.
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        Fotoğraf No | Bina / Bölge | Fotoğraftaki Konum | Kategori | Tespit | Öneriler | Yasal Gerekçe | Olasılık | Frekans | Şiddet | İşaret X | İşaret Y | İşaret Çap
        Hücrelerde satır sonu kullanma. Puan hücrelerine yalnız sayı yaz (ondalıkta virgül).
        METIN;
    }

    private static function dof(): string
    {
        $bugun = now()->format('d.m.Y');
        $tarih = fn (int $gun) => now()->addDays($gun)->format('d.m.Y');

        return <<<METIN
        GÖREV: Ek bilgide yazdığım (ve varsa bu sohbete eklediğim fotoğraflarda görülen) her uygunsuzluk için bir DÖF (düzeltici-önleyici faaliyet) satırı hazırla. Uygunsuzluk ekleme ya da çıkarma; her tespit bir satır.
        - Tespit: uygunsuzluğu açık, somut ve tarafsız bir dille yaz (1–2 cümle).
        - Öneri: kontrol hiyerarşisine göre (önce ortadan kaldırma ve mühendislik, sonra idari önlem, en son KKD) somut, uygulanabilir düzeltici faaliyet; 1–2 cümle.
        - Öncelik: Kritik, Yüksek, Orta ya da Düşük. Hayati tehlike, yüksekten düşme, enerji altında açık iletken, korumasız hareketli parça → Kritik.
        - Sorumlu: rol yaz (İşveren / İşveren Vekili, Bakım Sorumlusu, Bölüm Şefi, Ustabaşı, İSG Uzmanı, İşyeri Hekimi…); kişi adı yazma.
        - Termin: GG.AA.YYYY biçiminde tarih. Bugün {$bugun}; Kritik → {$tarih(7)}, Yüksek → {$tarih(30)}, Orta → {$tarih(90)}, Düşük → {$tarih(180)}.
        - Dayanak: ilgili yönetmelik ya da standart adı; emin olmadığın madde numarasını yazma.
        - Fotoğraf No: uygunsuzluğun görüldüğü fotoğrafın bu sohbete eklenme sırası (1, 2, 3…); fotoğrafı olmayan maddede boş bırak.
        - İşaret X / İşaret Y: uygunsuzluğun fotoğrafta işaretlenecek alanının MERKEZİ, fotoğrafın sol üst köşesine göre yüzde (0–100; X soldan sağa, Y yukarıdan aşağı). Eksik olanı değil, eksikliğin görüldüğü yeri göster (ör. "merdiven kenarında korkuluk yok" → korkuluğun olması gereken merdiven kenarı). Sistem bu noktaya madde numaralı kırmızı çember çizer. Aynı fotoğrafta birden fazla uygunsuzluk varsa her biri kendi yerini göstersin; çemberler aynı noktaya konmasın.
        - İşaret Çap: çemberin içine alacağı alanın genişliği, fotoğraf genişliğinin yüzdesi (5–60; küçük nesne 8–15, merdiven/raf gibi büyük alan 25–45). Fotoğrafı olmayan maddede üçünü de boş bırak.
        ÇIKTI: Yalnız bir TABLO ver, açıklama ekleme. İlk satır başlık; sütunlar tam olarak şu sırada ve şu adlarla:
        Fotoğraf No | Tespit | Öneri | Öncelik | Sorumlu | Termin | Dayanak | İşaret X | İşaret Y | İşaret Çap
        Hücrelerde satır sonu kullanma. İşaret hücrelerine yalnız sayı yaz.
        METIN;
    }

    /** Kontrol kartı: seçilen şablonun maddeleri ya da (boşsa) Gemini'nin hazırlayacağı yeni liste (14.11.2026). */
    private static function kontrolKarti(string $sablonAnahtari, int $adet): string
    {
        $sablon = \App\Models\KontrolKarti::sablon($sablonAnahtari);
        $olcek = fn (string $o) => implode(', ', array_map(
            fn ($k, $v) => "{$k} ({$v})",
            array_keys(config("isg.risk_fine_kinney.{$o}", [])),
            config("isg.risk_fine_kinney.{$o}", []),
        ));

        if ($sablon) {
            $liste = collect($sablon['maddeler'])->map(fn ($m, $i) => ($i + 1).". kod={$m['kod']} | {$m['baslik']} | {$m['kontrol']}")->implode("
");
            $gorev = <<<METIN
            GÖREV: Bu sohbete eklediğim fotoğraf "{$sablon['ad']}" alanına ait. Aşağıdaki kontrol listesinin HER maddesini fotoğrafa bakarak değerlendir. Maddeleri aynı sırayla ve aynı "kod" ile ver; madde ekleme ya da çıkarma.
            KONTROL LİSTESİ:
            {$liste}
            METIN;
            $alan = "\"alan_adi\": \"{$sablon['ad']}\", \"sablon\": \"{$sablonAnahtari}\"";
        } else {
            $gorev = <<<METIN
            GÖREV: Bu sohbete eklediğim fotoğraftaki alanı ya da ekipmanı tanı ve onun için bir İSG kontrol kartı hazırla. Önce "alan_adi"nı kısa ve genel yaz (ör. "Kompresör Odası", "Akülü Forklift Şarj Alanı", "Elektrik Ana Panosu"). Bu alan için {$adet} maddelik bir kontrol listesi oluştur: fotoğrafta görülen uygunsuzluklar ve bu tür bir alanda her zaman kontrol edilmesi gereken temel maddeler. Her madde kısa bir başlık ("baslik", 2–4 kelime) ve evet/hayır ile cevaplanabilen bir kontrol sorusu ("kontrol") içersin. Bu liste ileride aynı alanın diğer kontrollerinde şablon olarak kullanılacak; firmaya özgü değil, genel yaz.
            METIN;
            $alan = '"alan_adi": "…", "sablon": ""';
        }

        return <<<METIN
        {$gorev}
        KURALLAR:
        - "durum": "uygun", "uygunsuz", "kapsam_disi" (alanda bu konu yok) ya da fotoğrafta değerlendirilemiyorsa "" (boş). Fotoğrafta görülmeyen bir şeyi uygunsuz sayma, uydurma.
        - "isaret": maddenin fotoğrafta görüldüğü noktanın konumu, fotoğrafın sol üst köşesine göre yüzde: {"x": 0–100 (soldan sağa), "y": 0–100 (yukarıdan aşağı)}. Fotoğrafta görülmüyorsa null.
        - "uygunsuz" maddelerde doldur: "tespit" (fotoğrafta görülen uygunsuzluk, 1 cümle), "olasi_sonuc" (olası kaza/zarar), "etkilenenler", "mevcut_onlem" (görülen bir önlem yoksa "Yok"), "oneri" (kontrol hiyerarşisine göre somut ek önlem, 1–2 cümle), "sorumlu" (rol: İşveren / İşveren Vekili, Bakım Sorumlusu, Bölüm Şefi…; kişi adı yazma), "hedef_gun" (sayı: hayati risk 7, yüksek 15–30, diğer 30–90).
        - Fine-Kinney (mevcut durum): "olasilik", "frekans", "siddet"; ek önlem uygulandıktan sonra beklenen: "yeni_olasilik", "yeni_frekans", "yeni_siddet". YALNIZ şu değerleri kullan —
          Olasılık: {$olcek('olasilik')}
          Frekans: {$olcek('frekans')}
          Şiddet: {$olcek('siddet')}
        - "uygun" ve "kapsam_disi" maddelerde tespit/öneri/risk alanlarını boş bırak ("").
        - "yonetmelik": ilgili Türk yönetmeliğinin tam adı; "standart": ilgili TS / TS EN / TS EN ISO standardı ve kısa konusu. Emin olmadığın madde ya da standart numarasını yazma; yoksa boş bırak.
        ÇIKTI: Yalnız tek bir JSON nesnesi ver (```json kod bloğu içinde), açıklama ekleme. Yapı:
        {{$alan}, "maddeler": [{"kod": "…", "baslik": "…", "kontrol": "…", "durum": "uygunsuz", "isaret": {"x": 42, "y": 61}, "tespit": "…", "olasi_sonuc": "…", "etkilenenler": "…", "mevcut_onlem": "Yok", "olasilik": 3, "frekans": 6, "siddet": 30, "oneri": "…", "sorumlu": "…", "hedef_gun": 15, "yeni_olasilik": 0.5, "yeni_frekans": 6, "yeni_siddet": 30, "yonetmelik": "…", "standart": "…"}]}
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
