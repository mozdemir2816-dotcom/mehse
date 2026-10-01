<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yıllık Eğitim Planı ve Yıllık Çalışma Planı Excel çıktıları — kullanıcının
 * VİZYON firması için hazırladığı gerçek şablonlar BİREBİR kullanılır
 * (resources/belge/yillik-egitim-plani.xlsx, yillik-calisma-plani.xlsx):
 * biçim, birleştirmeler, yazdırma ayarları, OSGB (Yıldız Grup) logosu korunur;
 * yalnız firma bilgileri, satırlar, P/G işaretleri, firma logosu ve imza/kaşe
 * alanı doldurulur. Satır sayısı şablondakinden farklıysa ilgili bölüm
 * genişletilir/daraltılır ve birleştirmeler yeniden kurulur.
 */
class YillikPlanExcelUretici
{
    private const EGITIM_SABLONU = 'belge/yillik-egitim-plani.xlsx';

    private const CALISMA_SABLONU = 'belge/yillik-calisma-plani.xlsx';

    /** Eğitim planı şablonundaki bölümler: [ilk satır, son satır] — alttan üste işlenir. */
    private const EGITIM_BOLUMLERI = [
        'genel' => [7, 10],
        'saglik' => [11, 15],
        'teknik' => [16, 26],
        'ise_ozgu' => [27, 32],
        'diger' => [33, 39],
    ];

    /** Eğitim planında ay sütunları: Ocak 1. hafta = E (5); her ay 4 hafta. */
    private const EGITIM_ILK_SUTUN = 5;

    /** Şablonda "P" hücrelerinin dolgu rengi. */
    private const P_DOLGU = '00B0F0';

    /** Çalışma planı: veri satırları 8..45 (38 satır), imza satırı 46; Ocak P = F (6), G = G (7). */
    private const CALISMA_ILK_SATIR = 8;

    private const CALISMA_SON_SATIR = 45;

    private const CALISMA_ILK_SUTUN = 6;

    /** Çalışma planında P/G hücre yazı boyutu (şablonun çoğunluk satırları). */
    private const CALISMA_PG_PUNTO = 6.2;

    /*
    |--------------------------------------------------------------------------
    | Yıllık Eğitim Planı
    |--------------------------------------------------------------------------
    */

    public static function egitim(YillikPlan $plan, bool $imzali = true): StreamedResponse
    {
        return self::indir(self::egitimDoldur($plan, $imzali), self::dosyaAdi($plan, 'yillik-egitim-plani'));
    }

    public static function egitimDoldur(YillikPlan $plan, bool $imzali = true): Spreadsheet
    {
        $plan->loadMissing(['firma.igu', 'firma.isyeriHekimi']);
        $firma = $plan->firma;

        $kitap = IOFactory::load(resource_path(self::EGITIM_SABLONU));
        $s = $kitap->getSheet(0);

        $s->setCellValue('C1', $plan->yil.' YILLIK EĞİTİM PLANI');
        $s->setCellValue('H1', 'FİRMA ADI: '.self::buyuk($firma->unvan)
            ."\nFİRMA ADRESİ: ".self::buyuk(self::adres($firma))
            ."\nFİRMA SGK SİCİL NO: ".($firma->sgk_sicil_no ?: '—'));
        $s->getStyle('H1')->getAlignment()->setWrapText(true);
        $s->setCellValue('AQ1', 'Hazırlanma Tarihi:'.self::hazirlanmaTarihi($plan)->format('d.m.Y'));

        $gruplar = collect($plan->egitimler ?? [])->groupBy(fn (array $e) => self::egitimBolumu($e['kategori'] ?? null));
        $bilgi = YillikPlanSablonu::egitimBilgileri();

        // Alttan üste: alt bölümün satır ekleme/silmesi üst bölümlerin satırlarını kaydırmaz.
        $sonuclar = [];
        foreach (array_reverse(self::EGITIM_BOLUMLERI, true) as $kat => [$ilk, $son]) {
            $maddeler = $gruplar->get($kat, collect())->values()->all();
            $fark = self::egitimBolumuYaz($s, $kat, $ilk, $son, $maddeler, $bilgi['bolum_aciklamalari'][$kat] ?? null);
            $sonuclar[$kat] = [$ilk, $son + $fark];
        }

        // Üst bölümlerdeki değişiklikler alttakileri kaydırdı — gerçek konumlar:
        $birikim = 0;
        foreach (self::EGITIM_BOLUMLERI as $kat => [$ilk, $son]) {
            $uzunluk = $sonuclar[$kat][1] - $sonuclar[$kat][0];
            $sonuclar[$kat] = [$ilk + $birikim, $ilk + $birikim + $uzunluk];
            $birikim += ($uzunluk + 1) - ($son - $ilk + 1);
        }

        $katilanSatir = $sonuclar['diger'][1] + 1;
        $sureSatir = $katilanSatir + 1;
        $s->setCellValue("C{$katilanSatir}", $bilgi['katilanlar'] ?? 'TÜM PERSONEL');

        [$toplam, $sureler] = self::egitimSureleri($firma, $bilgi);
        $s->setCellValue("C{$sureSatir}", $toplam);
        $s->setCellValue("D{$sureSatir}", $sureler['genel']);
        $s->setCellValue("R{$sureSatir}", $sureler['saglik']);
        $s->setCellValue("AF{$sureSatir}", $sureler['teknik']);
        $s->setCellValue("AV{$sureSatir}", $sureler['ise_ozgu']);

        // Yazdırma: 5. Diğer Eğitimler ikinci sayfada başlar (şablondaki gibi).
        foreach (array_keys($s->getBreaks()) as $hucre) {
            $s->setBreak($hucre, Worksheet::BREAK_NONE);
        }
        $s->setBreak('A'.$sonuclar['ise_ozgu'][1], Worksheet::BREAK_ROW);
        $s->getPageSetup()->setPrintArea("A1:BC{$sureSatir}");

        self::cizimleriTemizle($s, ['BA1'], $sureSatir);
        self::logoEkle($s, $firma->logo, 'BA1', 88, 35, 4);
        self::egitimImzaAltbilgisi($s, $firma, $imzali);

        return $kitap;
    }

    /**
     * Bir eğitim bölümünü yazar; satır sayısı şablondan farklıysa bölümü
     * genişletir/daraltır. Dönüş: eklenen (+) / silinen (−) satır sayısı.
     *
     * @param  array<int, array<string, mixed>>  $maddeler
     */
    private static function egitimBolumuYaz(Worksheet $s, string $kat, int $ilk, int $son, array $maddeler, ?string $bolumAciklamasi): int
    {
        $sablonSayisi = $son - $ilk + 1;
        $gereken = max(1, count($maddeler));
        $satirBasi = $kat === 'diger';
        $etiket = $s->getCell("A{$ilk}")->getValue();
        $sablonAciklama = $s->getCell("BA{$ilk}")->getValue();
        $yukseklik = $s->getRowDimension($son)->getRowHeight();

        self::birlesimleriCoz($s, $ilk, $son);

        if ($gereken > $sablonSayisi) {
            $s->insertNewRowBefore($son + 1, $gereken - $sablonSayisi);
            for ($r = $son + 1; $r <= $ilk + $gereken - 1; $r++) {
                $s->getRowDimension($r)->setRowHeight($yukseklik);
            }
        } elseif ($gereken < $sablonSayisi) {
            $s->removeRow($ilk + $gereken, $sablonSayisi - $gereken);
        }

        $yeniSon = $ilk + $gereken - 1;
        $sonSutun = Coordinate::stringFromColumnIndex(self::EGITIM_ILK_SUTUN + 47);

        for ($r = $ilk; $r <= $yeniSon; $r++) {
            foreach (['B', 'C', 'D'] as $c) {
                $s->setCellValue("{$c}{$r}", null);
            }
            foreach ($s->rangeToArray("E{$r}:{$sonSutun}{$r}", null, false, false, true)[$r] as $c => $_) {
                $s->setCellValue("{$c}{$r}", null);
            }
            // Şablonda P hücreleri mavi dolgulu (Vizyon'un aylarında) — dolguyu
            // sıfırla, aşağıda yalnız P konan hücrelere yeniden uygulanır.
            $s->getStyle("E{$r}:{$sonSutun}{$r}")->getFill()->setFillType(Fill::FILL_NONE);
            $s->setCellValue("BA{$r}", null);
        }

        foreach ($maddeler as $i => $m) {
            $r = $ilk + $i;
            $s->setCellValue("B{$r}", $m['konu'] ?? '');
            $s->setCellValue("C{$r}", $m['hedef'] ?? '');
            $s->setCellValue("D{$r}", self::buyuk((string) ($m['egitici'] ?? '')));
            $hafta = max(1, min(4, (int) ($m['hafta'] ?? 2)));

            foreach (($m['aylar'] ?? []) as $ay => $durum) {
                if ($durum !== 'bos' && $ay >= 0 && $ay <= 11) {
                    $hucre = Coordinate::stringFromColumnIndex(self::EGITIM_ILK_SUTUN + $ay * 4 + $hafta - 1).$r;
                    $s->setCellValue($hucre, 'P');
                    $s->getStyle($hucre)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::P_DOLGU);
                }
            }

            if ($satirBasi) {
                $s->setCellValue("BA{$r}", $m['aciklama'] ?? '');
            }
        }

        $s->mergeCells("A{$ilk}:A{$yeniSon}");
        $s->setCellValue("A{$ilk}", $etiket);

        if ($satirBasi) {
            for ($r = $ilk; $r <= $yeniSon; $r++) {
                $s->mergeCells("BA{$r}:BC{$r}");
            }
        } else {
            $s->mergeCells("BA{$ilk}:BC{$yeniSon}");
            $s->setCellValue("BA{$ilk}", $bolumAciklamasi ?: $sablonAciklama);
        }

        return $gereken - $sablonSayisi;
    }

    /** @return array{0: string, 1: array<string, string>} toplam süre + bölüm süreleri (tehlike sınıfına göre) */
    private static function egitimSureleri(Firma $firma, array $bilgi): array
    {
        // Şablon metinleri çok tehlikeli (16 saat) içindir; diğer sınıflarda
        // toplam süre (8/12 saat) dört bölüme eşit bölünür.
        if ($firma->tehlike_sinifi === 'cok_tehlikeli' || blank($firma->tehlike_sinifi)) {
            return [$bilgi['toplam_sure'] ?? '16 SAAT', $bilgi['bolum_sureleri']];
        }

        $toplam = $firma->tehlike_sinifi === 'tehlikeli' ? 12 : 8;
        $bolum = $toplam / 4;

        return ["{$toplam} SAAT", [
            'genel' => "GENEL KONULAR: {$bolum} SAAT / 1 SAAT",
            'saglik' => "SAĞLIK KONULARI: {$bolum} SAAT / 1 SAAT",
            'teknik' => "TEKNİK KONULAR: {$bolum} SAAT / 1 SAAT",
            'ise_ozgu' => "İŞE ÖZGÜ RİSKLER: {$bolum} SAAT / {$bolum} SAAT",
        ]];
    }

    /**
     * Şablonda imza bloğu sayfa alt bilgisinde bir görsel (her sayfanın altında).
     * Aynı üç sütunlu tabloyu (İGU / İşyeri Hekimi / İşveren) firmanın kaşe
     * görselleriyle yeniden çizip alt bilgi görseli olarak koyar.
     */
    private static function egitimImzaAltbilgisi(Worksheet $s, Firma $firma, bool $imzali): void
    {
        $yol = ImzaSeridiCizici::ciz([
            ['baslik' => 'İŞ GÜVENLİĞİ UZMANI', 'kase' => $imzali ? $firma->igu?->kase_gorseli : null],
            ['baslik' => 'İŞYERİ HEKİMİ', 'kase' => $imzali ? $firma->isyeriHekimi?->kase_gorseli : null],
            ['baslik' => 'İŞVEREN/İŞVEREN VEKİLİ', 'kase' => $imzali ? ($firma->isveren_kase_gorseli ?: $firma->isveren_imza_gorseli) : null],
        ]);

        $hf = $s->getHeaderFooter();
        $cizim = new HeaderFooterDrawing;
        $cizim->setName('İmza şeridi');
        $cizim->setPath($yol);
        // Şablondaki alt bilgi görselinin boyutu (1113×58 pt).
        $cizim->setWidth(1113);
        $cizim->setHeight(58);
        $hf->addImage($cizim, HeaderFooter::IMAGE_FOOTER_CENTER);
    }

    private static function egitimBolumu(?string $kategori): string
    {
        return match ($kategori) {
            'genel', 'saglik', 'teknik', 'ise_ozgu' => $kategori,
            default => 'diger',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Çalışma Planı
    |--------------------------------------------------------------------------
    */

    public static function calisma(YillikPlan $plan): StreamedResponse
    {
        return self::indir(self::calismaDoldur($plan), self::dosyaAdi($plan, 'yillik-calisma-plani'));
    }

    public static function calismaDoldur(YillikPlan $plan): Spreadsheet
    {
        $plan->loadMissing('firma');
        $firma = $plan->firma;

        $kitap = IOFactory::load(resource_path(self::CALISMA_SABLONU));
        $s = $kitap->getSheetByName('Yıllık Çalışma Planı');
        $ozet = $kitap->getSheetByName('Aylık Özet');

        // Şablondaki "ISGYillikPlanTablosu" Excel tablosu (A7:S45) geçersiz:
        // başlık satırında aylar için tekrarlanan "P"/"G" var (tablo başlıkları
        // benzersiz olmalı) → Excel dosyayı "onarım" ile açıyor, otomasyon hiç
        // açamıyor. Biçim hücre stillerinde olduğundan tablo nesnesi kaldırılır.
        foreach ($s->getTableNames() as $tablo) {
            $s->removeTableByName($tablo);
        }

        $tarih = self::hazirlanmaTarihi($plan);
        $s->setCellValue('C1', 'İŞ SAĞLIĞI VE GÜVENLİĞİ YILLIK ÇALIŞMA PLANI – '.$plan->yil);
        $s->setCellValue('C2', self::buyuk($firma->unvan));
        $s->setCellValue('C3', self::buyuk(self::adres($firma)));
        $s->setCellValue('C4', $firma->sgk_sicil_no ?: '');
        $s->setCellValue('C5', $plan->yil);
        $s->setCellValue('AE3', ExcelTarih::PHPToExcel($tarih->copy()->startOfDay()));
        $s->setCellValue('AD6', 'Hazırlanma Tarihi:'.$tarih->format('d.m.Y'));

        $faaliyetler = array_values($plan->faaliyetler ?? []);
        $sonSatir = self::CALISMA_SON_SATIR;
        $fazla = count($faaliyetler) - ($sonSatir - self::CALISMA_ILK_SATIR + 1);

        if ($fazla > 0) {
            $yukseklik = $s->getRowDimension($sonSatir)->getRowHeight();
            $s->insertNewRowBefore($sonSatir + 1, $fazla);
            for ($r = $sonSatir + 1; $r <= $sonSatir + $fazla; $r++) {
                $s->getRowDimension($r)->setRowHeight($yukseklik);
            }
            $sonSatir += $fazla;
        }

        $sonSutun = Coordinate::stringFromColumnIndex(self::CALISMA_ILK_SUTUN + 23);
        for ($r = self::CALISMA_ILK_SATIR; $r <= $sonSatir; $r++) {
            foreach ($s->rangeToArray("A{$r}:AE{$r}", null, false, false, true)[$r] as $c => $_) {
                $s->setCellValue("{$c}{$r}", null);
            }
        }

        foreach ($faaliyetler as $i => $f) {
            $r = self::CALISMA_ILK_SATIR + $i;
            $s->setCellValue("A{$r}", $i + 1);
            $s->setCellValue("B{$r}", self::buyuk((string) ($f['ana_konu'] ?? '')));
            $s->setCellValue("C{$r}", $f['faaliyet'] ?? '');
            $s->setCellValue("D{$r}", $f['frekans'] ?? '');
            $s->setCellValue("E{$r}", $f['sorumlu'] ?? '');

            foreach (($f['aylar'] ?? []) as $ay => $durum) {
                if ($ay < 0 || $ay > 11 || $durum === 'bos') {
                    continue;
                }
                $s->setCellValue(Coordinate::stringFromColumnIndex(self::CALISMA_ILK_SUTUN + 2 * $ay).$r, 'P');
                if ($durum === 'tamamlandi') {
                    $s->setCellValue(Coordinate::stringFromColumnIndex(self::CALISMA_ILK_SUTUN + 2 * $ay + 1).$r, 'G');
                }
            }

            // Şablonda bazı satırların ay hücreleri farklı yazı boyutunda — P/G tek tip.
            $s->getStyle("F{$r}:{$sonSutun}{$r}")->getFont()->setSize(self::CALISMA_PG_PUNTO)->setBold(false);

            $not = trim((string) ($f['yasal_gereklilik'] ?? ''));
            if (filled($f['kayit_notu'] ?? null)) {
                $not .= ($not !== '' ? "\n" : '').'Kayıt/Not: '.$f['kayit_notu'];
            }
            $s->setCellValue("AD{$r}", $not);
            $s->setCellValue("AE{$r}", YillikPlan::faaliyetDurumu($f['aylar'] ?? []));
        }

        $s->getPageSetup()->setPrintArea('A1:AE'.($sonSatir + 1));

        // Şablonun alt bilgisinde satır sonu "_x000a_" olarak kaçırılmış, Excel
        // bunu düz metin basıyordu — gerçek satır sonuyla aynı metin.
        $s->getHeaderFooter()->setOddFooter(
            '&L&"Calibri,Regular"&8 İŞVEREN / İŞVEREN VEKİLİ'."\n".'Ad Soyad / İmza'
            .'&C&"Calibri,Regular"&8 İŞ GÜVENLİĞİ UZMANI'."\n".'Ad Soyad / İmza'
            .'&R&"Calibri,Regular"&8 İŞYERİ HEKİMİ'."\n".'Ad Soyad / İmza'
        );

        self::calismaOzetiGuncelle($ozet, $plan->yil, $faaliyetler, $sonSatir);
        self::cizimleriTemizle($s, ['AD1'], $sonSatir + 1);
        self::logoEkle($s, $firma->logo, 'AD1', 41, 64, 0);

        return $kitap;
    }

    /**
     * "Aylık Özet" sayfası: başlıktaki yıl, ana konu listesi (plan sırasıyla) ve
     * COUNTIFS aralıkları (satır eklendiyse) güncellenir. Şablonda 22 ana konu
     * satırı (4..25) + GENEL TOPLAM (26) var.
     *
     * @param  array<int, array<string, mixed>>  $faaliyetler
     */
    private static function calismaOzetiGuncelle(Worksheet $o, int $yil, array $faaliyetler, int $sonSatir): void
    {
        $o->setCellValue('A1', $yil.' İSG YILLIK ÇALIŞMA PLANI – AYLIK ÖZET');

        $konular = collect($faaliyetler)->map(fn ($f) => self::buyuk((string) ($f['ana_konu'] ?? '')))
            ->filter()->unique()->values()->take(22);

        for ($r = 4; $r <= 25; $r++) {
            $o->setCellValue("A{$r}", $konular[$r - 4] ?? null);
        }

        if ($sonSatir === self::CALISMA_SON_SATIR) {
            return;
        }

        foreach ($o->getCoordinates() as $hucre) {
            $v = $o->getCell($hucre)->getValue();
            if (is_string($v) && str_starts_with($v, '=') && str_contains($v, '$45')) {
                $o->setCellValue($hucre, str_replace('$45', '$'.$sonSatir, $v));
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    /**
     * Hazırlanma tarihi: firmanın sözleşme başlangıcı plan yılı içindeyse o
     * (bkz. evrak tarih bazı = sözleşme), değilse yılın ilk günü.
     */
    private static function hazirlanmaTarihi(YillikPlan $plan): Carbon
    {
        $baslangic = $plan->firma?->sozlesme_baslangic;

        return $baslangic && (int) $baslangic->year === (int) $plan->yil
            ? Carbon::parse($baslangic)
            : Carbon::create($plan->yil, 1, 1);
    }

    private static function adres(Firma $firma): string
    {
        return collect([$firma->adres, $firma->ilce, $firma->il])->filter()->implode(' ') ?: '—';
    }

    /** Türkçe büyük harf (i→İ önce çevrilir; mb_strtoupper tek başına bozar). */
    private static function buyuk(?string $metin): string
    {
        return mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], (string) $metin), 'UTF-8');
    }

    /**
     * Şablondaki firmaya özgü görselleri (VİZYON logosu, kaşe/imza görselleri)
     * kaldırır; OSGB logosu (A1) kalır. $tut: silinecek logo hücresi dışındaki
     * her şey $sonSatir'ın altındaysa (imza görselleri) silinir.
     *
     * @param  array<int, string>  $logoHucreleri
     */
    private static function cizimleriTemizle(Worksheet $s, array $logoHucreleri, int $sonSatir): void
    {
        $koleksiyon = $s->getDrawingCollection();

        foreach ($koleksiyon as $i => $cizim) {
            $hucre = $cizim->getCoordinates();
            [, $satir] = Coordinate::coordinateFromString($hucre);

            if (in_array($hucre, $logoHucreleri, true) || (int) $satir > $sonSatir) {
                unset($koleksiyon[$i]);
            }
        }
    }

    private static function logoEkle(Worksheet $s, ?string $logoYolu, string $hucre, int $yukseklik, int $ofsetX, int $ofsetY): void
    {
        $tamYol = $logoYolu ? storage_path('app/public/'.$logoYolu) : null;

        if (! $tamYol || ! file_exists($tamYol)) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setName('Firma logosu');
        $cizim->setPath($tamYol);
        $cizim->setHeight($yukseklik);
        $cizim->setCoordinates($hucre);
        $cizim->setOffsetX($ofsetX);
        $cizim->setOffsetY($ofsetY);
        $cizim->setWorksheet($s);
    }

    private static function birlesimleriCoz(Worksheet $s, int $ilk, int $son): void
    {
        foreach (array_keys($s->getMergeCells()) as $aralik) {
            [$bas, $bit] = Coordinate::rangeBoundaries($aralik);
            // Sütun A veya BA:BC ve satırları tamamen bölüm içinde olan birleşimler.
            if ($bas[1] >= $ilk && $bit[1] <= $son && in_array($bas[0], [1, 53], true)) {
                $s->unmergeCells($aralik);
            }
        }
    }

    private static function dosyaAdi(YillikPlan $plan, string $on): string
    {
        return $on.'-'.Str::slug($plan->firma?->unvan ?? 'firma').'-'.$plan->yil.'.xlsx';
    }

    private static function indir(Spreadsheet $kitap, string $ad): StreamedResponse
    {
        $yazici = IOFactory::createWriter($kitap, 'Xlsx');

        return response()->streamDownload(fn () => $yazici->save('php://output'), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
