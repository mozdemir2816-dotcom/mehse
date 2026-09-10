<?php

namespace App\Support;

use App\Models\Sertifika;
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

    public static function indir(Sertifika $s): ?StreamedResponse
    {
        if (! self::uygunMu($s) || ! ($s->katilimcilar ?? [])) {
            return null;
        }

        $dosyalar = [];

        foreach ($s->katilimcilar as $k) {
            $spreadsheet = self::doldur($s, $k);
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

    /** @param array{ad_soyad?: string, tc?: ?string, gorev?: ?string} $katilimci */
    private static function doldur(Sertifika $s, array $katilimci): Spreadsheet
    {
        $s->loadMissing('firma');
        $firma = $s->firma;

        $spreadsheet = IOFactory::load(resource_path(self::SABLON_YOLU));
        $sheet = $spreadsheet->getSheetByName('Çıktı Sayfası');

        $sheet->setCellValue('D8', 'KATILIMCININ ADI : '.TurkceMetin::buyuk($katilimci['ad_soyad'] ?? '—'));
        $sheet->setCellValue('D9', 'GÖREVİ : '.TurkceMetin::buyuk($katilimci['gorev'] ?: '—'));

        $tarihler = collect($s->egitim_tarihleri ?? [])->filter()->map(fn ($t) => Carbon::parse($t)->format('d/m/Y'));
        $tarih1 = $tarihler->get(0, now()->format('d/m/Y'));
        $yeniTarihler = [$tarih1, $tarihler->get(1, $tarih1)];

        $paragraf = (string) $sheet->getCell('D10')->getValue();
        $sira = 0;
        $paragraf = preg_replace_callback(
            '/\d{2}\/\d{2}\/\d{4}/',
            function () use (&$sira, $yeniTarihler) {
                return $yeniTarihler[$sira++] ?? end($yeniTarihler);
            },
            $paragraf,
        );
        $sheet->setCellValue('D10', $paragraf);

        $sheet->setCellValue('F16', now()->format('d/m/Y'));
        $sheet->setCellValue('F17', ($s->sure_metni ?: '16 Ders Saati'));

        $sheet->setCellValue('G24', $s->egitici_igu_dahil ? $s->egitici_igu_adi : null);
        $sheet->setCellValue('K24', $s->egitici_hekim_dahil ? $s->egitici_hekim_adi : null);

        $sheet->setCellValue('G29', $firma?->unvan);
        $sheet->setCellValue('G30', $firma?->isveren_vekili ?: $firma?->isveren_ad);

        self::turSekilIsaretle($sheet, $s);
        self::kaseEkle($sheet, 'G25', $s->egitici_igu_dahil ? $s->egitici_igu_kase : null);
        self::kaseEkle($sheet, 'K25', $s->egitici_hekim_dahil ? $s->egitici_hekim_kase : null);

        // Sağ üstte firma amblemi (K4) — OSGB amblemi (D4) şablonda sabit.
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

    private static function kaseEkle(Worksheet $sheet, string $hucre, ?string $kaseYolu): void
    {
        if (! $kaseYolu) {
            return;
        }

        $tamYol = storage_path('app/public/'.$kaseYolu);

        if (! file_exists($tamYol)) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setPath($tamYol);
        $cizim->setHeight(40);
        $cizim->setCoordinates($hucre);
        $cizim->setWorksheet($sheet);
    }

    /**
     * Sertifikanın sağ üstüne (K4) firmanın yüklü logosunu yerleştirir —
     * şablondaki OSGB amblemiyle (D4) simetrik. Firma logosuz ise sağ üst boş
     * kalır (şablonun görünümü bozulmaz).
     */
    private static function firmaLogosuEkle(Worksheet $sheet, ?string $logoYolu): void
    {
        if (! $logoYolu) {
            return;
        }

        $tamYol = storage_path('app/public/'.$logoYolu);

        if (! file_exists($tamYol)) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setName('Firma Amblemi');
        $cizim->setPath($tamYol);
        $cizim->setHeight(58);
        $cizim->setCoordinates('K4');
        $cizim->setWorksheet($sheet);
    }
}
