<?php

namespace App\Support;

use App\Models\KurulToplantisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Kurulu toplantı tutanağı çıktısı — PDF (dompdf) ve Excel (PhpSpreadsheet).
 * isgpratik yardım/kurul-toplantisi rehberi — "PDF İçeriği" listesi.
 */
class KurulToplantisiUretici
{
    private const BASLIK_ARKA = 'F0F0F0';

    public static function pdf(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kurul-toplantisi', [
            'toplanti' => $toplanti,
            'firma' => $toplanti->firma,
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print($pdf->output()), self::dosyaAdi($toplanti, 'pdf'));
    }

    /** Aynı tutanak Excel olarak — kararlar tablosunda "Durum" sütunu yok, "Karar Metni" geniş. */
    public static function excel(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Tutanak');

        $satir = 1;

        $s->setCellValue("A{$satir}", 'İSG KURULU TOPLANTI TUTANAĞI');
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(14);
        $s->getStyle("A{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $satir++;

        $s->setCellValue("A{$satir}", $toplanti->firma?->unvan ?? '');
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $satir += 2;

        foreach ([
            'Toplantı No' => $toplanti->toplanti_no ?: '—',
            'Tarih' => $toplanti->tarih?->format('d.m.Y') ?: '—',
            'Saat' => $toplanti->saat ?: '—',
            'Toplantı Yeri' => $toplanti->yer ?: '—',
            'Toplantı Başkanı' => $toplanti->baskan ?: '—',
        ] as $etiket => $deger) {
            $s->setCellValue("A{$satir}", $etiket);
            $s->getStyle("A{$satir}")->getFont()->setBold(true);
            $s->setCellValue("B{$satir}", $deger);
            $satir++;
        }
        $satir++;

        // KATILIMCILAR
        $satir = self::bolumBasligi($s, $satir, 'KATILIMCILAR');
        $satir = self::tabloBasligi($s, $satir, ['#', 'Ad Soyad', 'Görev', 'Katılım']);
        foreach (($toplanti->katilimcilar ?? []) as $i => $k) {
            $s->fromArray([
                $i + 1,
                $k['ad_soyad'] ?? '—',
                $k['gorev'] ?? '—',
                ($k['katildi'] ?? false) ? 'Katıldı' : 'Katılmadı',
            ], null, "A{$satir}");
            $satir++;
        }
        $satir++;

        // GÜNDEM
        $satir = self::bolumBasligi($s, $satir, 'GÜNDEM');
        $satir = self::tabloBasligi($s, $satir, ['#', 'Gündem Maddesi']);
        foreach (($toplanti->gundem ?? []) as $i => $madde) {
            $s->fromArray([$i + 1, $madde], null, "A{$satir}");
            $satir++;
        }
        $satir++;

        // ALINAN KARARLAR — "Durum" sütunu yok
        $satir = self::bolumBasligi($s, $satir, 'ALINAN KARARLAR');
        $satir = self::tabloBasligi($s, $satir, ['#', 'İlgili Gündem', 'Karar Metni', 'Sorumlu', 'Termin']);
        foreach (($toplanti->kararlar ?? []) as $i => $k) {
            $s->fromArray([
                $i + 1,
                $k['gundem_maddesi'] ?? '—',
                $k['karar_metni'] ?? '—',
                $k['sorumlu'] ?? '—',
                $k['termin'] ?? '—',
            ], null, "A{$satir}");
            $s->getStyle("C{$satir}")->getAlignment()->setWrapText(true);
            $satir++;
        }

        // Sütun genişlikleri: Karar Metni geniş, Sorumlu dar.
        $s->getColumnDimension('A')->setWidth(5);
        $s->getColumnDimension('B')->setWidth(30);
        $s->getColumnDimension('C')->setWidth(70);
        $s->getColumnDimension('D')->setWidth(16);
        $s->getColumnDimension('E')->setWidth(14);

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, self::dosyaAdi($toplanti, 'xlsx'));
    }

    private static function bolumBasligi(Worksheet $s, int $satir, string $metin): int
    {
        $s->setCellValue("A{$satir}", $metin);
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(11);

        return $satir + 1;
    }

    /** @param array<int, string> $basliklar */
    private static function tabloBasligi(Worksheet $s, int $satir, array $basliklar): int
    {
        $s->fromArray($basliklar, null, "A{$satir}");

        $sonSutun = chr(ord('A') + count($basliklar) - 1);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getFont()->setBold(true);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BASLIK_ARKA);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN);

        return $satir + 1;
    }

    private static function dosyaAdi(KurulToplantisi $toplanti, string $uzanti): string
    {
        $ek = $toplanti->toplanti_no
            ? Str::slug(str_replace('/', '-', $toplanti->toplanti_no))
            : $toplanti->tarih?->format('Y-m-d');

        return 'kurul-toplantisi-'.Str::slug($toplanti->firma?->unvan ?? 'firma').'-'.$ek.'.'.$uzanti;
    }
}
