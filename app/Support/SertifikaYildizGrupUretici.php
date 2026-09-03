<?php

namespace App\Support;

use App\Models\Sertifika;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * "Yıldız Grup" sertifika şablonu — kullanıcının kendi gerçek Excel şablonunu
 * ("resources/belge/sertifika-yildiz-grup.xlsx") BİREBİR kullanır (aynı yöntem:
 * [[AcilDurumWordUretici]]/[[AtamaYazisiWordUretici]] — şablonun formatı,
 * yazı tipleri, logoları AYNEN korunur; yalnızca katılımcı/firma/eğitim
 * bilgileri ve "Eğitim Konuları" (config'teki genel/sağlık/teknik + seçili
 * sektörün işe özgü riskleri) doldurulur). Yalnız 'isg' (genel, çoklu
 * eğitici) sertifika tipiyle uyumludur — şablonun 4 sabit kategori yapısı
 * (Genel 4 / Sağlık 5 / Teknik 12 / İşe Özgü) yalnız bu tiple eşleşir.
 */
class SertifikaYildizGrupUretici
{
    private const SABLON_YOLU = 'belge/sertifika-yildiz-grup.xlsx';

    private const KATEGORI_SATIRLARI = [
        'genel_konular' => [47, 50],
        'saglik_konulari' => [52, 56],
        'teknik_konular' => [58, 69],
    ];

    private const ISYERINE_OZGU_SATIRLARI = [71, 84];

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

            return response()->streamDownload(fn () => print($icerik), $dosyalar[0]['ad'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $zipYolu = tempnam(sys_get_temp_dir(), 'ygz').'.zip';
        $zip = new ZipArchive();
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

        return response()->streamDownload(fn () => print($icerik), $ad, ['Content-Type' => 'application/zip']);
    }

    /** @param array{ad_soyad?: string, tc?: ?string, gorev?: ?string} $katilimci */
    private static function doldur(Sertifika $s, array $katilimci): Spreadsheet
    {
        $s->loadMissing('firma');
        $firma = $s->firma;

        $spreadsheet = IOFactory::load(resource_path(self::SABLON_YOLU));
        $sheet = $spreadsheet->getSheetByName('Çıktı Sayfası');

        $sheet->setCellValue('D8', '     GÖREVİ:'.TurkceMetin::buyuk($katilimci['gorev'] ?: '—'));
        $sheet->setCellValue('D9', TurkceMetin::buyuk($katilimci['ad_soyad'] ?? '—'));

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

            $rt = new RichText();
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

    private static function turSekilIsaretle(Worksheet $sheet, Sertifika $s): void
    {
        $turSatiri = $s->tur === 'tekrar' ? 19 : 18;
        $sekilSatiri = $s->sekil === 'uzaktan' ? 20 : 21;

        foreach ($sheet->getDrawingCollection() as $cizim) {
            if ($cizim->getCoordinates() === 'I19' || $cizim->getCoordinates() === 'I18') {
                $cizim->setCoordinates('I'.$turSatiri);
            }

            if ($cizim->getCoordinates() === 'I20' || $cizim->getCoordinates() === 'I21') {
                $cizim->setCoordinates('I'.$sekilSatiri);
            }
        }
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

        $cizim = new Drawing();
        $cizim->setPath($tamYol);
        $cizim->setHeight(40);
        $cizim->setCoordinates($hucre);
        $cizim->setWorksheet($sheet);
    }
}
