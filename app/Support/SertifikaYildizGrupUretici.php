<?php

namespace App\Support;

use App\Models\Sertifika;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * "Yıldız Grup Eğitim Sertifikası" şablonu — kullanıcının kendi gerçek Excel
 * şablonunu ("resources/belge/sertifika-yildiz-grup.xlsx") BİREBİR kullanır
 * (aynı yöntem: [[AcilDurumWordUretici]]/[[AtamaYazisiWordUretici]] — şablonun
 * formatı, yazı tipleri, OSGB amblemi AYNEN korunur; yalnızca katılımcı/firma/
 * eğitim bilgileri, sağ üstteki FİRMA amblemi (K4, firma logosundan) ve
 * "Eğitim Konuları" (eğitim katılım formundaki genel/sağlık/teknik + seçili
 * sektörün işe özgü riskleri ve süreleri) doldurulur). Yalnız 'isg' (genel,
 * çoklu eğitici) sertifika tipiyle uyumludur — şablonun 4 sabit kategori
 * yapısı (Genel 4 / Sağlık 5 / Teknik 12 / İşe Özgü) yalnız bu tiple eşleşir.
 */
class SertifikaYildizGrupUretici
{
    private const SABLON_YOLU = 'belge/sertifika-yildiz-grup.xlsx';

    /** Şablonda kategori başlıklarını izleyen doldurulabilir madde satır aralıkları. */
    private const KATEGORI_SATIRLARI = [
        'genel_konular' => [44, 47],
        'saglik_konulari' => [49, 53],
        'teknik_konular' => [55, 66],
    ];

    private const ISYERINE_OZGU_SATIRLARI = [68, 83];

    private const TURKCE_HARFLER = ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'k', 'l'];

    public static function uygunMu(Sertifika $s): bool
    {
        return $s->tip === 'isg';
    }

    /** Seçilen biçime göre (xlsx | pdf) Yıldız Grup çıktısı. */
    public static function cikti(Sertifika $s, bool $imzali = true, string $bicim = 'xlsx'): ?StreamedResponse
    {
        return $bicim === 'pdf' ? self::pdf($s, $imzali) : self::indir($s, $imzali);
    }

    /** $imzali: false → matbu (kaşe görseli basılmaz, elle imzalanır). */
    public static function indir(Sertifika $s, bool $imzali = true): ?StreamedResponse
    {
        if (! self::uygunMu($s) || ! ($s->katilimcilar ?? [])) {
            return null;
        }

        $dosyalar = [];

        $s->loadMissing('firma');
        $calisanlar = $s->firma ? $s->firma->calisanlar()->get(['ad_soyad', 'tc', 'gorev']) : collect();

        foreach ($s->katilimcilar as $k) {
            $k['gorev'] = self::gorevBul($k, $calisanlar);
            $spreadsheet = self::doldur($s, $k, $imzali);
            $gecici = tempnam(sys_get_temp_dir(), 'ygs').'.xlsx';
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($gecici);
            $dosyalar[] = ['yol' => $gecici, 'ad' => Str::slug($k['ad_soyad'] ?? 'katilimci').'.xlsx'];
        }

        if (count($dosyalar) === 1) {
            $icerik = file_get_contents($dosyalar[0]['yol']);
            unlink($dosyalar[0]['yol']);

            return response()->streamDownload(fn () => print ($icerik), $dosyalar[0]['ad'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $zipYolu = tempnam(sys_get_temp_dir(), 'ygz').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE);

        foreach ($dosyalar as $d) {
            $zip->addFile($d['yol'], $d['ad']);
        }

        $zip->close();

        $icerik = file_get_contents($zipYolu);
        unlink($zipYolu);

        foreach ($dosyalar as $d) {
            @unlink($d['yol']);
        }

        $ad = 'sertifika-yildiz-grup-'.Str::slug($s->firma?->unvan ?: 'firma').'.zip';

        return response()->streamDownload(fn () => print ($icerik), $ad, ['Content-Type' => 'application/zip']);
    }

    /**
     * Katılımcının görevi: firma çalışan kaydı (T.C., yoksa ad soyad eşleşmesi) güncel ve tek doğru
     * kaynaktır; eğitim katılım kaydındaki kopya sonradan boş / eski kalabilir. Çalışan bulunamaz ya da
     * görevi boşsa kayıttaki görev kullanılır. Meslek kodu ("3123.02 - ") ve sondaki tire atılır.
     *
     * @param  array{ad_soyad?: string, tc?: ?string, gorev?: ?string}  $katilimci
     * @param  \Illuminate\Support\Collection<int, \App\Models\Calisan>  $calisanlar
     */
    public static function gorevBul(array $katilimci, $calisanlar): ?string
    {
        $tc = EgitimTakibi::tcAnahtari($katilimci['tc'] ?? null);
        $ad = EgitimTakibi::adAnahtari($katilimci['ad_soyad'] ?? null);

        // Aynı kişi mükerrer kayıtlıysa (biri T.C.'siz / görevsiz eski kayıt) görevi DOLU olan eşleşme seçilir:
        // önce aynı T.C.'liler, sonra aynı adlılar.
        $adaylar = $calisanlar->filter(fn ($c) => $tc !== '' && EgitimTakibi::tcAnahtari($c->tc) === $tc)
            ->concat($calisanlar->filter(fn ($c) => $ad !== '' && EgitimTakibi::adAnahtari($c->ad_soyad) === $ad));

        $calisan = $adaylar->first(fn ($c) => filled($c->gorev));

        $gorev = filled($calisan?->gorev) ? $calisan->gorev : ($katilimci['gorev'] ?? null);

        $gorev = trim(preg_replace('/\s+/u', ' ', (string) $gorev));
        $gorev = trim(preg_replace(['/^\d{4}\.\d{2}\s*-\s*/u', '/\s*-\s*$/u'], '', $gorev));

        return $gorev !== '' ? $gorev : null;
    }

    /** Açıklama paragrafındaki tarih(ler): "… tarihinde" (tek gün) / "… ve … tarihlerinde" (çok gün). */
    private static function tarihIfadesi(Sertifika $s): string
    {
        $tarihler = collect($s->egitim_tarihleri ?? [])
            ->filter()
            ->map(fn ($t) => Carbon::parse($t)->format('d/m/Y'))
            ->unique()
            ->values();

        if ($tarihler->isEmpty()) {
            $tarihler = collect([now()->format('d/m/Y')]);
        }

        return $tarihler->count() > 1
            ? $tarihler->slice(0, -1)->implode(', ').' ve '.$tarihler->last().' tarihlerinde'
            : $tarihler->first().' tarihinde';
    }

    /**
     * Aynı sertifikanın PDF çıktısı: Excel şablonunun hücre geometrisinden (sütun/satır ölçüleri, birleşik
     * hücreler, kenarlıklar, yazı tipleri, görseller, %75 yazdırma ölçeği, sayfa sonu ve ortalama) birebir
     * çizilir; her katılımcı için belge + eğitim konuları sayfası tek PDF'te döner.
     *
     * @param  bool  $imzali  false → matbu (kaşe/imza görseli basılmaz)
     */
    public static function pdf(Sertifika $s, bool $imzali = true): ?StreamedResponse
    {
        if (! self::uygunMu($s) || ! ($s->katilimcilar ?? [])) {
            return null;
        }

        $s->loadMissing('firma');
        $firma = $s->firma;
        $calisanlar = $firma ? $firma->calisanlar()->get(['ad_soyad', 'tc', 'gorev']) : collect();

        $katilimcilar = collect($s->katilimcilar)->map(fn (array $k) => [
            'ad' => TurkceMetin::buyuk($k['ad_soyad'] ?? '—'),
            'gorev' => TurkceMetin::buyuk(self::gorevBul($k, $calisanlar) ?? '—'),
        ])->all();

        // dompdf, @font-face yazı tiplerinin ölçü önbelleğini bu klasöre yazar (yoksa oluşturulur).
        \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('fonts'));

        // Şablon bir kez doldurulur (katılımcıya özgü yalnız ad/görev — yer tutucu); sayfa modeli bir kez çıkarılır.
        $kitap = self::doldur($s, ['ad_soyad' => '[[AD]]', 'gorev' => '[[GOREV]]'], $imzali);
        $sheet = $kitap->getSheetByName('Çıktı Sayfası');
        $model = ExcelSayfaCizici::sayfalar($sheet);
        $kagit = ExcelSayfaCizici::kagitBoyutu($sheet);
        $kenar = $sheet->getPageMargins();

        $goruntule = fn (string $mod, array $liste): string => Pdf::loadView('pdf.sertifika-yildiz-grup', [
            'mod' => $mod,
            'model' => $model,
            'katilimcilar' => $liste,
            'kagit' => $kagit,
            'ortalaYatay' => (bool) $sheet->getPageSetup()->getHorizontalCentered(),
            'ortalaDikey' => (bool) $sheet->getPageSetup()->getVerticalCentered(),
            'kenar' => ['sol' => $kenar->getLeft() * 72, 'ust' => $kenar->getTop() * 72],
        ])->setPaper([0, 0, $kagit[0], $kagit[1]])->output();

        try {
            if (count($katilimcilar) === 1) {
                $icerik = $goruntule('tam', $katilimcilar);
            } else {
                // Çok katılımcı: sabit sayfalar BİR kez çizilir, katılımcıya özgü yalnız ad/görev satırları ayrı bir
                // katmanda üretilip birleştirilir (57 katılımcıda süre ve bellek ~20 kat azalır).
                $icerik = self::birlestir($goruntule('statik', [['ad' => '', 'gorev' => '']]), $goruntule('dinamik', $katilimcilar), count($katilimcilar), $kagit);
            }
        } finally {
            ExcelSayfaCizici::temizle();
        }

        $ad = count($katilimcilar) === 1
            ? Str::slug($s->katilimcilar[0]['ad_soyad'] ?? 'katilimci').'.pdf'
            : 'sertifika-yildiz-grup-'.Str::slug($firma?->unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($icerik), $ad, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Sabit sayfalar (1. sayfa: belge, 2. sayfa: eğitim konuları) + katılımcı başına şeffaf ad/görev katmanı →
     * her katılımcı için [belge (+ad/görev), eğitim konuları] sayfa çifti.
     *
     * @param  array{0: float, 1: float}  $kagit
     */
    private static function birlestir(string $statik, string $dinamik, int $adet, array $kagit): string
    {
        $pdf = new \setasign\Fpdi\Fpdi('P', 'pt', $kagit);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCompression(true);

        $pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($statik));
        $belge = $pdf->importPage(1);
        $konular = $pdf->importPage(2);

        $pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($dinamik));
        $katman = [];
        for ($i = 1; $i <= $adet; $i++) {
            $katman[$i] = $pdf->importPage($i);
        }

        for ($i = 1; $i <= $adet; $i++) {
            $pdf->AddPage('P', $kagit);
            $pdf->useTemplate($belge);
            $pdf->useTemplate($katman[$i]);
            $pdf->AddPage('P', $kagit);
            $pdf->useTemplate($konular);
        }

        return $pdf->Output('S');
    }

    /** @param array{ad_soyad?: string, tc?: ?string, gorev?: ?string} $katilimci */
    private static function doldur(Sertifika $s, array $katilimci, bool $imzali = true): Spreadsheet
    {
        $s->loadMissing('firma');
        $firma = $s->firma;

        $spreadsheet = IOFactory::load(resource_path(self::SABLON_YOLU));
        $sheet = $spreadsheet->getSheetByName('Çıktı Sayfası');

        $sheet->setCellValue('D8', 'KATILIMCININ ADI : '.TurkceMetin::buyuk($katilimci['ad_soyad'] ?? '—'));
        $sheet->setCellValue('D9', 'GÖREVİ : '.TurkceMetin::buyuk($katilimci['gorev'] ?? '—'));

        $tarihIfadesi = self::tarihIfadesi($s);

        $paragraf = preg_replace(
            '/\d{2}\/\d{2}\/\d{4}(?:\s*(?:,|ve)\s*\d{2}\/\d{2}\/\d{4})*\s*tarih(?:inde|lerinde)/u',
            $tarihIfadesi,
            (string) $sheet->getCell('D10')->getValue(),
        );
        $sheet->setCellValue('D10', $paragraf);

        $sheet->setCellValue('F16', now()->format('d/m/Y'));
        $sheet->setCellValue('F17', ($s->sure_metni ?: '16 Ders Saati'));

        // Eğiticiler bu şablonda her zaman İGU + İşyeri Hekimi — ad ve kaşe firmaya
        // atanmış İSG Profesyonellerinden çekilir (yoksa sertifika snapshot'ına düşer).
        $iguKase = $firma?->igu?->kase_gorseli
            ?: ($s->egitici_igu_dahil ? $s->egitici_igu_kase : null)
            ?: ($s->egitici_igu_dahil || $firma?->igu ? $firma?->user?->kase_gorseli : null);
        $hekimKase = $firma?->isyeriHekimi?->kase_gorseli ?: ($s->egitici_hekim_dahil ? $s->egitici_hekim_kase : null);

        // Ayrı imza görseli yalnız kaşe yoksa (imzaEkle) — İGU kaydında yoksa hesap sahibinin Profilim'deki
        // imzası (RiskDegerlendirmesiUretici::hazirlayan ile aynı geri dönüş).
        $iguImza = $s->egitici_igu_dahil || $firma?->igu
            ? ($firma?->igu?->imza_gorseli ?: $firma?->user?->imza_gorseli)
            : null;
        $hekimImza = $firma?->isyeriHekimi?->imza_gorseli;

        // Eğitici adı basılmaz — kaşe ad / unvan taşır (kullanıcı kararı 03.10.2026).

        // Not: "Çalışanın İşyerinin Ünvanı" / "İşverenin Adı Soyadı" (G29/G30) BİLE
        // BİLE boş bırakılır — bu alan işveren tarafından kaşe/imza ile doldurulur.

        self::turSekilIsaretle($sheet, $s);
        if ($imzali) {
            $genislik = self::kaseEkle($sheet, 'G25', $iguKase);
            self::imzaEkle($sheet, 'G25', $iguImza, $genislik);
            $genislik = self::kaseEkle($sheet, 'K25', $hekimKase);
            self::imzaEkle($sheet, 'K25', $hekimImza, $genislik);
        }

        // Sağ üstte firma amblemi — çerçevenin iç çizgisinin ALTINA (çizginin üstüne binmez),
        // soldaki OSGB amblemiyle (D4, 15 px aşağı, 48 px) simetrik.
        self::firmaLogosuEkle($sheet, $firma?->logo);

        $icerik = $s->konu_icerigi ?? [];

        foreach (self::KATEGORI_SATIRLARI as $anahtar => [$ilk, $son]) {
            self::kategoriDoldur($sheet, $ilk, $son, collect($icerik[$anahtar] ?? [])->where('dahil', true)->values()->all());
        }

        $ozgu = $icerik['isyerine_ozgu'] ?? null;
        $ozguMaddeler = $ozgu ? collect($ozgu['maddeler'])->where('dahil', true)->values()->all() : [];
        self::kategoriDoldur($sheet, self::ISYERINE_OZGU_SATIRLARI[0], self::ISYERINE_OZGU_SATIRLARI[1], $ozguMaddeler);

        return $spreadsheet;
    }

    /** @param array<int, array{madde: string, dakika: int}> $maddeler */
    private static function kategoriDoldur(Worksheet $sheet, int $ilkSatir, int $sonSatir, array $maddeler): void
    {
        $satir = $ilkSatir;

        foreach ($maddeler as $i => $m) {
            if ($satir > $sonSatir) {
                break;
            }

            $harf = self::TURKCE_HARFLER[$i] ?? (string) ($i + 1);

            $rt = new RichText;
            $koşu = $rt->createTextRun($harf.')');
            $koşu->getFont()->setBold(true);
            $rt->createText($m['madde']);

            $sheet->getCell('E'.$satir)->setValue($rt);
            $sheet->setCellValue('L'.$satir, $m['dakika'].' Dk.');
            $satir++;
        }

        while ($satir <= $sonSatir) {
            $sheet->setCellValue('E'.$satir, '');
            $sheet->setCellValue('L'.$satir, '');
            $satir++;
        }
    }

    /**
     * Eğitim Türü (İlk Defa/Tekrar) ve Eğitim Şekli (Uzaktan/Yüz Yüze) seçimi
     * şablonda iki şekilde belirginleştirilir: seçili seçeneğin metni KALIN
     * yapılır VE hemen yanındaki (I sütunu) hücreye kalın siyah bir "X"
     * çarpı işareti yazılır — kullanıcı: "seçilen seçenekleri işaretle...
     * çarpı koyarak daha belirgin yapabilirsin." İki seçenek de metin olarak
     * GÖRÜNMEYE devam eder (şablon satırı silinmiyor).
     *
     * Şablonun kendi onay işareti GÖRSELİ (Resim 4/5) taşınmıyor — hedef
     * satırda karşılık gelen bir kutucuk olmadığından taşındığında metnin
     * karşısına denk gelmiyordu (kullanıcı geri bildirimi); bunun yerine
     * kaldırılıp yerine güvenilir bir metin işareti kullanılıyor.
     */
    private static function turSekilIsaretle(Worksheet $sheet, Sertifika $s): void
    {
        $turSatiri = $s->tur === 'tekrar' ? 19 : 18;
        $sekilSatiri = $s->sekil === 'uzaktan' ? 20 : 21;

        $kaldirilacaklar = [];

        foreach ($sheet->getDrawingCollection() as $cizim) {
            if (in_array($cizim->getCoordinates(), ['I18', 'I19', 'I20', 'I21'], true)) {
                $kaldirilacaklar[] = $cizim;
            }
        }

        foreach ($kaldirilacaklar as $cizim) {
            $cizim->setWorksheet(null, true);
        }

        foreach ([18, 19] as $satir) {
            self::secimIsaretiYaz($sheet, $satir, $satir === $turSatiri);
        }

        foreach ([20, 21] as $satir) {
            self::secimIsaretiYaz($sheet, $satir, $satir === $sekilSatiri);
        }
    }

    private static function secimIsaretiYaz(Worksheet $sheet, int $satir, bool $secili): void
    {
        $sheet->getStyle('F'.$satir)->getFont()->setBold($secili);
        $sheet->setCellValue('I'.$satir, $secili ? 'X' : '');
        $sheet->getStyle('I'.$satir)->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('000000');
        $sheet->getStyle('I'.$satir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /** @return int eklenen kaşenin piksel genişliği (yoksa 0) — imza yanına yerleşir. */
    private static function kaseEkle(Worksheet $sheet, string $hucre, ?string $kaseYolu): int
    {
        if (! $kaseYolu || ! ($tamYol = ExcelGorsel::yol(storage_path('app/public/'.$kaseYolu)))) {
            return 0;
        }

        $cizim = new Drawing;
        $cizim->setName('Kaşe');
        $cizim->setPath($tamYol);
        $cizim->setHeight(80);   // kaşe 2 kat büyük (kullanıcı isteği 03.10.2026)
        $cizim->setCoordinates($hucre);
        $cizim->setWorksheet($sheet);

        return (int) $cizim->getWidth();
    }

    /**
     * Ayrı imza görseli YALNIZ kaşe yoksa basılır — kullanıcıların kaşe
     * görselinde imza zaten birlikte (kullanıcı 04.10.2026: "kaşe bölümünde
     * hem imza hem kaşe beraber yüklü"); ikisi birden imzayı çift basardı.
     */
    private static function imzaEkle(Worksheet $sheet, string $hucre, ?string $imzaYolu, int $kaseGenisligi): void
    {
        if ($kaseGenisligi || ! $imzaYolu || ! ($tamYol = ExcelGorsel::yol(storage_path('app/public/'.$imzaYolu)))) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setName('İmza');
        $cizim->setPath($tamYol);
        $cizim->setHeight(60);
        $cizim->setCoordinates($hucre);
        $cizim->setOffsetY(8);
        $cizim->setWorksheet($sheet);
    }

    /**
     * Firma logosu: sağ üstte (L4), çerçevenin 4. satır üstündeki iç çizgisinin
     * altında kalacak şekilde 15 px aşağı kaydırılır (OSGB amblemi D4 ile aynı
     * düzen). Firma logosuz ise sağ üst boş kalır.
     */
    private static function firmaLogosuEkle(Worksheet $sheet, ?string $logoYolu): void
    {
        if (! $logoYolu || ! ($tamYol = ExcelGorsel::yol(storage_path('app/public/'.$logoYolu)))) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setName('Firma Amblemi');
        $cizim->setPath($tamYol);
        $cizim->setHeight(48);
        $cizim->setCoordinates('L4');   // sağ köşe — OSGB amblemine simetrik
        $cizim->setOffsetX(10);
        $cizim->setOffsetY(15);
        $cizim->setWorksheet($sheet);
    }
}
