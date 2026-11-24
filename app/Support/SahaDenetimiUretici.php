<?php

namespace App\Support;

use App\Models\SahaDenetimi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Saha Denetimi (F-070) PDF ve Excel (.xlsx) üretimi.
 */
class SahaDenetimiUretici
{
    /** @param  bool  $imzali  false = imzasız (matbu): kaşe/imza görselleri basılmaz */
    public static function pdf(SahaDenetimi $d, bool $imzali = true): StreamedResponse
    {
        $d->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.saha-denetimi', [
            'imzali' => $imzali,
            'denetim' => $d,
            'firma' => $d->firma,
        ])->setPaper('a4');

        // Sağ altta "Sayfa X / Y" — tek render, page_script ile (bkz. KurulToplantisiUretici).
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Serif', 'normal');
        $canvas->page_script(function (int $sayfa, int $toplam) use ($canvas, $font): void {
            $canvas->text($canvas->get_width() - 78, $canvas->get_height() - 28, "Sayfa {$sayfa} / {$toplam}", $font, 7.5, [0.42, 0.39, 0.36]);
        });

        $ad = 'saha-denetimi-'.Str::slug($d->firma?->unvan ?? 'firma').'-r'.$d->revizyon.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** Saha Denetimi (F-070) Excel (.xlsx) raporu — künye, uyum yüzdesi ve renkli madde tablosu. */
    public static function excel(SahaDenetimi $d): StreamedResponse
    {
        $kitap = self::excelKitabi($d);
        $ad = 'saha-denetimi-'.Str::slug($d->firma?->unvan ?? 'firma').'-r'.$d->revizyon.'.xlsx';

        return response()->streamDownload(function () use ($kitap): void {
            $writer = new Xlsx($kitap);
            $writer->save('php://output');
        }, $ad, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public static function excelKitabi(SahaDenetimi $d): Spreadsheet
    {
        $d->loadMissing('firma');
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Saha Denetimi');

        $s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $s->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $s->getPageSetup()->setFitToWidth(1);
        $s->getPageSetup()->setFitToHeight(0);

        $s->mergeCells('A1:H1');
        $s->setCellValue('A1', 'SAHA DENETİM VE DEĞERLENDİRME RAPORU ('.$d->belgeAdi().')');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F294A');
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(26);

        $s->setCellValue('A2', 'Firma:');
        $s->mergeCells('B2:D2');
        $s->setCellValue('B2', (string) ($d->firma?->unvan ?: '—'));
        $s->setCellValue('E2', 'Denetim Tarihi:');
        $s->setCellValue('F2', $d->denetim_tarihi?->format('d.m.Y') ?? '—');
        $s->setCellValue('G2', 'Uygunluk:');
        $s->setCellValue('H2', ($d->uygunluk_yuzdesi !== null ? '%'.number_format($d->uygunluk_yuzdesi, 1, ',', '.') : '—').' ('.$d->sonucEtiketi().')');
        $s->getStyle('A2:H2')->getFont()->setSize(9);
        foreach (['A2', 'E2', 'G2'] as $c) {
            $s->getStyle($c)->getFont()->setBold(true);
        }

        $basliklar = ['No', 'Kategori / Bölüm', 'Denetim Maddesi', 'Sonuç', 'Kritik', 'Bulgu / Açıklama / Öneri', 'Termin', 'Fotoğraf Adedi'];
        foreach ($basliklar as $idx => $b) {
            $col = chr(65 + $idx);
            $s->setCellValue("{$col}4", $b);
        }
        $s->getStyle('A4:H4')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $s->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $s->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(4)->setRowHeight(22);

        $satir = 5;
        $durumEtiket = ['uygun' => 'Uygun', 'uygun_degil' => 'Uygun Değil', 'uygulanmaz' => 'Uygulanmaz'];
        $durumRenk = ['uygun' => 'DCFCE7', 'uygun_degil' => 'FEE2E2', 'uygulanmaz' => 'F1F5F9'];

        foreach (array_values($d->cevaplar ?? []) as $i => $c) {
            $sonucKod = (string) ($c['sonuc'] ?? '');
            $fotoSayi = count(SahaDenetimi::maddeFotolari((array) $c));

            $s->setCellValue("A{$satir}", $i + 1);
            $s->setCellValue("B{$satir}", (string) ($c['kategori'] ?? $c['bolum'] ?? '—'));
            $s->setCellValue("C{$satir}", (string) ($c['madde'] ?? $c['soru'] ?? '—'));
            $s->setCellValue("D{$satir}", $durumEtiket[$sonucKod] ?? ($sonucKod ?: '—'));
            $s->setCellValue("E{$satir}", ! empty($c['kritik']) ? 'EVET' : 'Hayır');
            $s->setCellValue("F{$satir}", trim((string) (($c['aciklama'] ?? '').' '.($c['oneri'] ?? ''))) ?: '—');
            $s->setCellValue("G{$satir}", ! empty($c['termin']) ? (string) $c['termin'] : '—');
            $s->setCellValue("H{$satir}", $fotoSayi);

            if (isset($durumRenk[$sonucKod])) {
                $s->getStyle("D{$satir}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($durumRenk[$sonucKod]);
            }
            $s->getStyle("A{$satir}:H{$satir}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $s->getStyle("A{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle("D{$satir}:E{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle("H{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $satir++;
        }

        $sonSatir = max(5, $satir - 1);
        $s->getStyle("A4:H{$sonSatir}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $s->setAutoFilter("A4:H{$sonSatir}");

        $genislikler = ['A' => 6, 'B' => 22, 'C' => 42, 'D' => 15, 'E' => 9, 'F' => 42, 'G' => 13, 'H' => 13];
        foreach ($genislikler as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        return $kitap;
    }
}
