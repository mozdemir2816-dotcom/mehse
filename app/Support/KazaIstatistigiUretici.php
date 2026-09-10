<?php

namespace App\Support;

use App\Models\KazaIstatistigi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kaza İstatistikleri raporu — PDF (dompdf) ve Excel (PhpSpreadsheet).
 * Yıllık değerlendirme raporunun eki olarak kullanılır.
 */
class KazaIstatistigiUretici
{
    public static function pdf(KazaIstatistigi $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kaza-istatistikleri', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'aylar' => config('isg.kaza_istatistik.aylar'),
        ])->setPaper('a4');

        $ad = 'kaza-istatistikleri-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->yil.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    public static function excel(KazaIstatistigi $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');
        ExcelBellek::artir();

        $aylar = config('isg.kaza_istatistik.aylar');
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Kaza İstatistikleri');
        $s->getColumnDimension('A')->setWidth(22);
        foreach (['B', 'C', 'D', 'E'] as $sutun) {
            $s->getColumnDimension($sutun)->setWidth(18);
        }

        $r = 1;
        $s->setCellValue("A{$r}", 'KAZA İSTATİSTİKLERİ');
        $s->mergeCells("A{$r}:E{$r}");
        $s->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $r += 2;

        foreach ([
            ['Firma', $kayit->firma?->unvan ?? '—'],
            ['Yıl', (string) $kayit->yil],
            ['Standart', $kayit->standartEtiketi()],
            ['Ortalama Çalışan', (string) $kayit->toplamOrtalamaCalisan()],
            ['Toplam Çalışma Saati', number_format($kayit->toplamCalismaSaati(), 0, ',', '.')],
            ['Hesaba Dahil Kaza Sayısı', (string) $kayit->kazaSayisi()],
            ['Kayıp Zamanlı Kaza', (string) $kayit->kayipZamanliKazaSayisi()],
            ['Ölümlü Kaza', (string) $kayit->olumluKazaSayisi()],
            ['Toplam Kayıp Gün', (string) $kayit->toplamKayipGun()],
            ['Kaza Sıklık Oranı', $kayit->siklikOrani() !== null ? (string) $kayit->siklikOrani() : '— (çalışma saati girilmedi)'],
            ['Kaza Ağırlık Oranı', $kayit->agirlikOrani() !== null ? (string) $kayit->agirlikOrani() : '— (çalışma saati girilmedi)'],
        ] as [$etiket, $deger]) {
            $s->setCellValue("A{$r}", $etiket);
            $s->setCellValue("B{$r}", $deger);
            $s->getStyle("A{$r}")->getFont()->setBold(true);
            $r++;
        }
        $r++;

        // --- Aylık çalışma verileri ---
        $s->setCellValue("A{$r}", 'AYLIK ÇALIŞMA VERİLERİ');
        $s->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        $s->fromArray(['Ay', 'Ort. Çalışan', 'Çalışma Saati'], null, "A{$r}");
        $s->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
        $r++;

        foreach ($kayit->aylik_veriler ?? [] as $ay => $veri) {
            $s->setCellValue("A{$r}", $aylar[$ay] ?? ($ay + 1));
            $s->setCellValue("B{$r}", (int) ($veri['ort_calisan'] ?? 0));
            $s->setCellValue("C{$r}", (int) ($veri['calisma_saati'] ?? 0));
            $r++;
        }
        $s->setCellValue("A{$r}", 'TOPLAM');
        $s->setCellValue("C{$r}", $kayit->toplamCalismaSaati());
        $s->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
        $r += 2;

        // --- Kazalar ---
        $s->setCellValue("A{$r}", 'HESABA DAHİL KAZALAR');
        $s->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        $s->fromArray(['Tarih', 'Kaynak', 'Kayıp Gün', 'Ölümlü', 'Açıklama'], null, "A{$r}");
        $s->getStyle("A{$r}:E{$r}")->getFont()->setBold(true);
        $r++;

        foreach ($kayit->tumKazalar() as $k) {
            $s->setCellValue("A{$r}", $k['tarih'] ? \Illuminate\Support\Carbon::parse($k['tarih'])->format('d.m.Y') : '—');
            $s->setCellValue("B{$r}", $k['kaynak']);
            $s->setCellValue("C{$r}", $k['kayip_gunu']);
            $s->setCellValue("D{$r}", $k['olumlu'] ? 'Evet' : '');
            $s->setCellValue("E{$r}", $k['aciklama'] ?? '');
            $r++;
        }

        if ($kayit->tumKazalar()->isEmpty()) {
            $s->setCellValue("A{$r}", 'Bu yıl için kaza kaydı yok.');
            $r++;
        }
        $r++;

        $s->setCellValue("A{$r}", 'Sıklık Oranı = (Kaza Sayısı × '.number_format($kayit->carpan(), 0, ',', '.').') / Toplam Çalışma Saati');
        $r++;
        $s->setCellValue("A{$r}", 'Ağırlık Oranı = (Toplam Kayıp Gün × '.number_format($kayit->carpan(), 0, ',', '.').') / Toplam Çalışma Saati');
        $s->getStyle('A'.($r - 1).":A{$r}")->getFont()->setItalic(true)->setSize(8);

        $tmp = tempnam(sys_get_temp_dir(), 'kzi').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        $ad = 'kaza-istatistikleri-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->yil.'.xlsx';

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad);
    }
}
