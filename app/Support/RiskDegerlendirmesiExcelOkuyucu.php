<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Risk Sihirbazı "Risk Değerlendirmenizden Yükleyin" yöntemi — kullanıcının
 * KENDİ Excel dosyasını, hiçbir sabit şablona uydurmadan okur.
 *
 * Gerçek risk değerlendirme dosyalarının ortak iskeleti (kullanıcının matriks/
 * klasöründeki 12 dosya + İnşaat Fine-Kinney üzerinde ölçüldü):
 *  1. Üstte künye bandı (1-6 satır): firma adı, işveren, hekim, İGU, tarih.
 *  2. Bazen bir süper-başlık (RİSK DEĞERLENDİRME / MEVCUT DURUM RİSK).
 *  3. Ana başlık satırı: SIRA · BÖLÜM/ALAN · FAALİYET · TEHLİKE · RİSK · … · ÖNERİLER · SORUMLU · TERMİN
 *  4. 1 VEYA 2 satırlık alt başlık: "OLASILIK/ŞİDDET/RİSK/ÖNCELİK" ya da
 *     "O / Ş / RİSK SKORU / ÖNEM" + "OLASILIK (1-5) / ŞİDDET (1-5)".
 *  5. İKİNCİ puan bloğu: "İYİLEŞTİRME SONRASI RİSK" / "TEDBİRLER SONRASI" /
 *     "ÖNLEMLER SONRASI RİSK" — aynı O/Ş(/F) sütunları tekrar (rezidüel risk).
 *
 * Bu yüzden: her sayfada başlık bandı (ana + 0-2 alt satır) aranır, sütunlar
 * ana + alt satırların BİRLEŞİK metniyle eşlenir; ilk O/Ş(/F) üçlüsü mevcut
 * riske, ikincisi (varsa) `son_*` alanlarına yazılır. "Risk Skoru / Öncelik
 * Sırası / Önem Derecesi" gibi HESAPLANAN sütunlar hiç eşlenmez. Puan sütunu
 * yalnız adı uyuyor VE altındaki veri sayısalsa kabul edilir.
 */
class RiskDegerlendirmesiExcelOkuyucu
{
    /** Bu kelimeleri içeren başlıklar HESAPLANAN çıktı sütunudur — hiç eşlenmez. */
    private const YOKSAY_KELIMELERI = [
        'riskskoru', 'riskpuani', 'riskseviyesi', 'riskduzeyi', 'riskderecesi',
        'onemderecesi', 'onceliksirasi', 'onemsirasi',
    ];

    private const BASLIK_TARAMA_SATIR_LIMIT = 25;

    private const BOS_SATIR_TOLERANSI = 25;

    /** Şişmiş satır/sütun boyutlu dosyalarda (ör. Coordinate WWF ≈ 16.000, 42.000 satır) tarama tavanları. */
    private const MUTLAK_SUTUN_TAVANI = 90;

    private const MUTLAK_SATIR_TAVANI = 6000;

    /**
     * @return array{basarili: int, adaylar: array<int, array<string, mixed>>, hatalar: array<int, string>}
     */
    public static function oku(string $dosyaYolu): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        // Şişmiş boyutlu dosyalar (ör. Altın Yakut: 42.000 satır × 16.000 sütun,
        // hepsi boş hücre) filtresiz yüklenince yalnız load() 40+ sn sürüyor.
        // Boş hücreleri parse ETMEMEK bunu ~2 sn'ye indirir; okuyucu zaten
        // null'ı boş kabul eder. (setReadFilter bu dosyalarda tersine ~40 sn
        // ekliyor — hızlı toplu okuma yolunu kapattığından — kullanılmıyor.)
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $kitap = $reader->load($dosyaYolu);

        // 1. Geçiş — her sayfada başlık bandını bul.
        $sayfalar = [];

        foreach ($kitap->getAllSheets() as $sheet) {
            $maxRow = min($sheet->getHighestRow(), self::MUTLAK_SATIR_TAVANI);
            $maxCol = static::etkinSutunSayisi($sheet, $maxRow);
            [$veriBaslangic, $sutunlar] = static::baslikBandiBul($sheet, $maxRow, $maxCol);

            if ($veriBaslangic !== null && in_array('tehlike', $sutunlar, true)) {
                // Veri taramasını makul bir aralıkla sınırla — şişmiş satır boyutlu
                // dosyalarda (42.000 satır) tüm sayfayı dolaşmak dakikalar sürüyor.
                $maxRow = min($maxRow, $veriBaslangic + 3000);
                $sayfalar[] = compact('sheet', 'veriBaslangic', 'sutunlar', 'maxRow', 'maxCol');
            }
        }

        if (! $sayfalar) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Tanıdık bir başlık satırı bulunamadı (en az "Tehlike" sütunu gerekli, dosyanın hiçbir sayfasında).']];
        }

        // Herhangi bir sayfada O/Ş(/F) puan sütunu varsa, yalnız o sayfaları oku —
        // böylece "içindekiler / özet / arama" gibi yardımcı sayfalar elenir. Hiçbir
        // sayfada puan yoksa (kullanıcı puanları AI'ye bıraktıysa) hepsi okunur.
        $puanliVar = collect($sayfalar)->contains(fn ($s) => static::puanSutunuVar($s['sutunlar']));

        if ($puanliVar) {
            $sayfalar = array_values(array_filter($sayfalar, fn ($s) => static::puanSutunuVar($s['sutunlar'])));
        }

        $adaylar = [];

        foreach ($sayfalar as $s) {
            foreach (static::sayfaAdaylari($s['sheet'], $s['veriBaslangic'], $s['sutunlar'], $s['maxRow'], $s['maxCol']) as $a) {
                $adaylar[] = $a;
            }
        }

        if (! $adaylar) {
            return ['basarili' => 0, 'adaylar' => [], 'hatalar' => ['Başlık satırı bulundu ama altında okunabilir risk maddesi yok.']];
        }

        return ['basarili' => count($adaylar), 'adaylar' => $adaylar, 'hatalar' => []];
    }

    private static function puanSutunuVar(array $sutunlar): bool
    {
        return (bool) array_intersect(['olasilik', 'frekans', 'siddet'], $sutunlar);
    }

    /**
     * getHighestColumn() bazı gerçek dosyalarda (kopyala-yapıştır / eski format)
     * binlerce boş sütun döndürür. Başlık bölgesindeki gerçek son dolu sütunu
     * bulup makul bir tavan uygular — 20×16000 hücrelik tarama yerine 20×~30.
     */
    private static function etkinSutunSayisi($sheet, int $maxRow): int
    {
        $ham = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $tavan = min($ham, self::MUTLAK_SUTUN_TAVANI);
        $enSag = 1;
        $tara = min($maxRow, self::BASLIK_TARAMA_SATIR_LIMIT + 8);

        for ($r = 1; $r <= $tara; $r++) {
            for ($c = $tavan; $c > $enSag; $c--) {
                $v = $sheet->getCell([$c, $r])->getValue();

                if ($v !== null && $v !== '') {
                    $enSag = $c;

                    break;
                }
            }
        }

        return min($enSag + 3, $tavan);
    }

    /** @return array<int, array<string, mixed>> */
    private static function sayfaAdaylari($sheet, int $veriBaslangic, array $sutunlar, int $maxRow, int $maxCol): array
    {
        $puanAlanlari = ['olasilik', 'frekans', 'siddet', 'son_olasilik', 'son_frekans', 'son_siddet'];
        $adaylar = [];
        $bosSayaci = 0;

        for ($r = $veriBaslangic; $r <= $maxRow; $r++) {
            $veri = [];
            $doluMu = false;

            foreach ($sutunlar as $c => $alan) {
                $deger = static::hucreDegeri($sheet, $c, $r);

                if ($deger === '' || $deger === null || (is_string($deger) && str_starts_with($deger, '#'))) {
                    continue;
                }

                $doluMu = true;

                if (in_array($alan, $puanAlanlari, true)) {
                    $veri[$alan] = is_numeric($deger) ? (float) $deger : ($veri[$alan] ?? null);

                    continue;
                }

                $yeni = is_string($deger) ? trim($deger) : (string) $deger;
                $veri[$alan] = isset($veri[$alan]) ? $veri[$alan].' — '.$yeni : $yeni;
            }

            if (! $doluMu) {
                if (++$bosSayaci >= self::BOS_SATIR_TOLERANSI) {
                    break;
                }

                continue;
            }

            $bosSayaci = 0;

            if (blank($veri['tehlike'] ?? null)) {
                continue;
            }

            $adaylar[] = [
                'anahtar' => 'exc-'.substr(md5(($veri['tehlike'] ?? '').$r.uniqid('', true)), 0, 10),
                'kaynak' => 'excel',
                'tehlike_id' => null,
                'bolum' => $veri['bolum'] ?? null,
                'faaliyet' => $veri['faaliyet'] ?? null,
                'tehlike' => $veri['tehlike'],
                'risk' => $veri['risk'] ?? null,
                'mevcut_onlem' => $veri['mevcut_onlem'] ?? null,
                'oneri' => $veri['oneri'] ?? null,
                'mevzuat' => $veri['mevzuat'] ?? null,
                'sorumlu' => $veri['sorumlu'] ?? null,
                'termin' => $veri['termin'] ?? null,
                'aciklama' => $veri['aciklama'] ?? null,
                'olasilik' => $veri['olasilik'] ?? null,
                'frekans' => $veri['frekans'] ?? null,
                'siddet' => $veri['siddet'] ?? null,
                'son_olasilik' => $veri['son_olasilik'] ?? null,
                'son_frekans' => $veri['son_frekans'] ?? null,
                'son_siddet' => $veri['son_siddet'] ?? null,
            ];
        }

        return $adaylar;
    }

    /**
     * Başlık bandını bulur. Strateji: önce ANA başlık satırını bul (tek başına
     * en çok tanıdık alan + "tehlike" içeren satır), sonra altındaki 0-2 alt
     * başlık satırını (O/Ş/RİSK SKORU…) ve — bölüm/faaliyet başlıkları bir üst
     * "süper" satırda kalmışsa (Altın Yakut deseni) — üstteki 1 satırı ekler.
     *
     * @return array{0: int|null, 1: array<int, string>} [veri başlangıç satırı, sütun eşlemesi]
     */
    private static function baslikBandiBul($sheet, int $maxRow, int $maxCol): array
    {
        $tara = min($maxRow, self::BASLIK_TARAMA_SATIR_LIMIT);

        // 1) Ana başlık satırı adayı: yalnız o satırla eşleme yapıldığında en çok
        //    alan üreten VE "tehlike" içeren satır.
        $anaSatir = null;
        $anaPuan = 0;

        for ($r = 1; $r <= $tara; $r++) {
            if (static::satirVeriGibi($sheet, $r, $maxCol)) {
                continue;
            }

            $tekil = static::sutunlariEslestir($sheet, [$r], null, $maxCol, $r + 1);

            if (! in_array('tehlike', $tekil, true)) {
                continue;
            }

            if (count($tekil) > $anaPuan) {
                $anaPuan = count($tekil);
                $anaSatir = $r;
            }
        }

        if ($anaSatir === null) {
            return [null, []];
        }

        // 2) Alt başlık satırları (ardışık, en çok 2).
        $band = [$anaSatir];

        if (static::altBaslikMi($sheet, $anaSatir + 1, $maxCol)) {
            $band[] = $anaSatir + 1;

            if (static::altBaslikMi($sheet, $anaSatir + 2, $maxCol)) {
                $band[] = $anaSatir + 2;
            }
        }

        $veriBaslangic = end($band) + 1;

        // 3) Bölüm/faaliyet başlığı bir üst "süper" satırda kalmış olabilir
        //    (Altın Yakut: R3'te "BÖLÜM / ÜNİTE", ana bant R4-R5). Süper satır
        //    yalnız bölüm/faaliyet BOŞLUĞUNU doldurmak için kullanılır — tüm
        //    sütunlara karıştırılmaz (yoksa "PLANLANAN / ÖNERİLEN FAALİYET"
        //    süper etiketi öneri sütununu bölüm/faaliyet sanır).
        $ustSatir = null;
        $ust = $anaSatir - 1;

        if ($ust >= 1 && ! static::satirVeriGibi($sheet, $ust, $maxCol) && ! static::kunyeSatiriMi($sheet, $ust, $maxCol)) {
            $anaEsleme = static::sutunlariEslestir($sheet, $band, null, $maxCol, $veriBaslangic);
            $eksik = ! in_array('bolum', $anaEsleme, true) || ! in_array('faaliyet', $anaEsleme, true);
            $ustEsleme = static::sutunlariEslestir($sheet, [$ust], null, $maxCol, $veriBaslangic);
            $ustKatki = in_array('bolum', $ustEsleme, true) || in_array('faaliyet', $ustEsleme, true);

            if ($eksik && $ustKatki) {
                $ustSatir = $ust;
            }
        }

        return [$veriBaslangic, static::sutunlariEslestir($sheet, $band, $ustSatir, $maxCol, $veriBaslangic)];
    }

    /**
     * Satır veri satırı gibi mi? Tüm gerçek risk analizi şablonlarında 1. veya
     * 2. sütun "SIRA NO / RİSK NO / NO" olup veri satırlarında küçük bir pozitif
     * tam sayı içerir; başlık satırlarında metin ya da boştur. (Uzun-metin
     * sezgisi, "DÜZELTİCİ, KORUYUCU VE ÖNLEYİCİ TEDBİRLER" gibi uzun başlık
     * etiketlerini yanlışlıkla veri sanıyordu — kullanılmıyor.)
     */
    private static function satirVeriGibi($sheet, int $row, int $maxCol): bool
    {
        foreach ([1, 2] as $c) {
            $v = $sheet->getCell([$c, $row])->getValue();

            if (is_numeric($v) && (float) $v > 0 && (float) $v == (int) $v && (int) $v <= 100000) {
                return true;
            }
        }

        return false;
    }

    /** Firma künyesi satırı mı? (Firma Adı / İşveren / Hekim / Tarih …) */
    private static function kunyeSatiriMi($sheet, int $row, int $maxCol): bool
    {
        for ($c = 1; $c <= $maxCol; $c++) {
            $v = $sheet->getCell([$c, $row])->getValue();

            if (is_string($v) && preg_match('/(firma ad|işveren|isveren|hekim|çalışan temsilci|calisan temsilci|destek eleman|hazırlan|hazirlan|yayın tarih|yayin tarih|revizyon|doküman kod|dokuman kod|geçerlilik|gecerlilik)/iu', $v)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bir satır "alt başlık" (Olasılık O1 / Frekans F1 / Şiddet S1 …) satırı mı?
     * Ölçüt: en az 2 puan/derece anahtar kelimesi VE en çok bir uzun (>45) hücre
     * — gerçek veri satırında tehlike/öneri/mevcut cümleleri birden çok sütunda uzundur.
     */
    private static function altBaslikMi($sheet, int $row, int $maxCol): bool
    {
        $isaret = 0;
        $uzunHucre = 0;

        for ($c = 1; $c <= $maxCol; $c++) {
            $deger = $sheet->getCell([$c, $row])->getValue();

            if (! is_string($deger) || trim($deger) === '') {
                continue;
            }

            if (mb_strlen(trim($deger)) > 45) {
                $uzunHucre++;
            }

            if (preg_match('/(olasilik|olasılık|ihtimal|frekans|maruziyet|siddet|şiddet|puan|skor|seviye|derece|önem|onem|öncelik|oncelik)/iu', $deger)) {
                $isaret++;
            }
        }

        return $isaret >= 2 && $uzunHucre <= 1;
    }

    /**
     * @param  array<int, int>  $anaBand  ana + alt başlık satır numaraları
     * @param  int|null  $ustSatir  yalnız bölüm/faaliyet boşluğunu doldurmak için taranan süper satır
     * @return array<int, string> sütun indeksi => alan adı
     */
    private static function sutunlariEslestir($sheet, array $anaBand, ?int $ustSatir, int $maxCol, int $veriBaslangic): array
    {
        $sutunlar = [];
        $metinDolu = [];
        $puanDolu = [];
        $puanBasladi = false;

        for ($c = 1; $c <= $maxCol; $c++) {
            $parcalar = [];

            foreach ($anaBand as $satir) {
                $parcalar[] = (string) $sheet->getCell([$c, $satir])->getValue();
            }

            $n = static::normalize(implode(' ', $parcalar));

            if ($n !== '') {
                // Hesaplanan çıktı sütunları (Risk Skoru / Öncelik Sırası / Önem Derecesi).
                $yoksayildi = false;

                foreach (self::YOKSAY_KELIMELERI as $yoksay) {
                    if (str_contains($n, $yoksay)) {
                        $yoksayildi = true;

                        break;
                    }
                }

                if ($yoksayildi) {
                    continue;
                }

                // --- Puan sütunları (yalnız altındaki veri sayısalsa) ---
                $eksen = static::puanEkseni($n);

                if ($eksen !== null) {
                    if (static::sutunSayisalMi($sheet, $c, $veriBaslangic)) {
                        $puanBasladi = true;

                        if (! isset($puanDolu[$eksen])) {
                            $sutunlar[$c] = $eksen;
                            $puanDolu[$eksen] = true;
                        } elseif (! isset($puanDolu['son_'.$eksen])) {
                            $sutunlar[$c] = 'son_'.$eksen;
                            $puanDolu['son_'.$eksen] = true;
                        }
                    }

                    continue;
                }

                // --- Metin sütunları (ilk eşleşen kazanır) ---
                $alan = static::metinAlani($n, $puanBasladi, fn () => static::sutunSayisalMi($sheet, $c, $veriBaslangic));

                if ($alan !== null && ! isset($metinDolu[$alan])) {
                    $sutunlar[$c] = $alan;
                    $metinDolu[$alan] = true;

                    continue;
                }
            }

            // Ana bant bu sütunu boş bıraktı — süper satır bölüm/faaliyet taşıyor mu?
            if ($ustSatir !== null && ! isset($sutunlar[$c])) {
                $nu = static::normalize((string) $sheet->getCell([$c, $ustSatir])->getValue());
                $bf = $nu === '' ? null : static::bolumVeyaFaaliyet($nu);

                if ($bf !== null && ! isset($metinDolu[$bf])) {
                    $sutunlar[$c] = $bf;
                    $metinDolu[$bf] = true;
                }
            }
        }

        return $sutunlar;
    }

    /** Başlık metni bir puan eksenine mi işaret ediyor? (O1 / Frekans / ŞİDDET (1-5) …) */
    private static function puanEkseni(string $n): ?string
    {
        if (str_contains($n, 'olasilik') || str_contains($n, 'ihtimal')) {
            return 'olasilik';
        }

        if (str_contains($n, 'frekans') || str_contains($n, 'maruziyet')) {
            return 'frekans';
        }

        if (str_contains($n, 'siddet')) {
            return 'siddet';
        }

        return null;
    }

    /**
     * @param  callable():bool  $sayisalMi  sütunun altındaki verinin sayısal olup olmadığı (tembel)
     */
    private static function metinAlani(string $n, bool $puanBasladi, callable $sayisalMi): ?string
    {
        if (($bf = static::bolumVeyaFaaliyet($n)) !== null) {
            return $bf;
        }

        if (str_contains($n, 'tehlike') || str_contains($n, 'hazard')) {
            return 'tehlike';
        }

        // Risk / sonuç — yalnız puan bloğundan ÖNCE ve metin sütunuysa (hesaplanan
        // "RİSK" skor sütunu olasılık/şiddetten sonra gelir ve sayısaldır).
        if (! $puanBasladi && ! $sayisalMi()
            && (str_contains($n, 'risk') || str_contains($n, 'sonuc') || str_contains($n, 'zarar'))) {
            return 'risk';
        }

        if (str_contains($n, 'mevzuat') || str_contains($n, 'yasaldayanak') || str_contains($n, 'yonetmelik')
            || str_contains($n, 'kanun') || str_contains($n, 'standartdayanak')) {
            return 'mevzuat';
        }

        // Mevcut önlem / mevcut durum (mevcut skoru açıklar) — "önlem" içerdiği
        // için önericiden ÖNCE bakılır ("Mevcut Önlem" öneri sanılmasın).
        if (str_contains($n, 'mevcutonlem') || str_contains($n, 'mevcuttedbir') || str_contains($n, 'mevcutdurum')
            || str_contains($n, 'mevcutkontrol') || str_contains($n, 'gunceldurum') || str_contains($n, 'mevcutuygulama')) {
            return 'mevcut_onlem';
        }

        // Planlanan / önerilen tedbir.
        if (str_contains($n, 'oneri') || str_contains($n, 'onlem') || str_contains($n, 'tedbir')
            || str_contains($n, 'duzeltici') || str_contains($n, 'onleyici')
            || str_contains($n, 'alinmasigereken') || str_contains($n, 'alinacak')
            || str_contains($n, 'planlananfaaliyet') || str_contains($n, 'aksiyon')) {
            return 'oneri';
        }

        if (str_contains($n, 'sorumlu')) {
            return 'sorumlu';
        }

        if (str_contains($n, 'termin') || str_contains($n, 'gerceklestirmetarihi') || str_contains($n, 'tedbirtarihi')) {
            return 'termin';
        }

        if (str_contains($n, 'aciklama') || str_contains($n, 'gerceklesme')) {
            return 'aciklama';
        }

        return null;
    }

    /**
     * "Bölüm / Ünite", "Alan", "Faaliyet Alanı (Ana Kategori)", "Alt Faaliyet"
     * gibi başlıkları bölüm ↔ faaliyet olarak ayırır. "faaliyet alanı" (ana
     * kategori) bölüm sayılır; "faaliyet alan" (i'siz — iş kalemi) faaliyet.
     */
    private static function bolumVeyaFaaliyet(string $n): ?string
    {
        if (str_contains($n, 'anakategori') || str_contains($n, 'faaliyetalani')) {
            return 'bolum';
        }

        if (str_contains($n, 'altfaaliyet')) {
            return 'faaliyet';
        }

        if (str_contains($n, 'faaliyet') || str_contains($n, 'proses') || str_contains($n, 'imalat')
            || str_contains($n, 'surec') || str_contains($n, 'operasyon') || str_contains($n, 'ismakinesi')) {
            return 'faaliyet';
        }

        if ($n === 'alan' || str_contains($n, 'bolum') || str_contains($n, 'departman') || str_contains($n, 'unite')
            || str_contains($n, 'kisim') || str_contains($n, 'lokasyon') || str_contains($n, 'calismaalani')) {
            return 'bolum';
        }

        return null;
    }

    /** Verinin başladığı satırdan itibaren ilk birkaç dolu hücre sayısal mı? */
    private static function sutunSayisalMi($sheet, int $col, int $veriBaslangic): bool
    {
        $kontrolEdilen = 0;

        for ($r = $veriBaslangic; $r <= $veriBaslangic + 25 && $kontrolEdilen < 5; $r++) {
            $deger = $sheet->getCell([$col, $r])->getValue();

            if ($deger === null || $deger === '') {
                continue;
            }

            $kontrolEdilen++;

            if (! is_numeric($deger)) {
                return false;
            }
        }

        return $kontrolEdilen > 0;
    }

    /** Tek hücrenin değeri — formül değilse hesap motorunu hiç çalıştırmaz (hız). */
    private static function hucreDegeri($sheet, int $col, int $row)
    {
        $hucre = $sheet->getCell([$col, $row]);
        $ham = $hucre->getValue();

        if (! is_string($ham) || ! str_starts_with($ham, '=')) {
            return $ham;
        }

        try {
            return $hucre->getCalculatedValue();
        } catch (\Throwable $e) {
            // PhpSpreadsheet'in çözemediği formül (ör. _xludf.MAXIFS) — atla.
            return null;
        }
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);
        $metin = mb_strtolower($metin);

        return preg_replace('/[^a-z0-9]/', '', $metin) ?? '';
    }
}
