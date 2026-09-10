<?php

namespace App\Support;

use App\Models\PeriyodikKontrol;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekipman & Periyodik Kontrol "Müfettiş Teftiş Paketi" — PDF (dompdf, A4 yatay)
 * ve Excel (PhpSpreadsheet). Firmanın tüm iş ekipmanları ve vize durumları.
 */
class PeriyodikKontrolUretici
{
    public static function pdf(PeriyodikKontrol $kontrol): StreamedResponse
    {
        $kontrol->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.periyodik-kontrol', [
            'kontrol' => $kontrol,
            'firma' => $kontrol->firma,
            'ekipmanlar' => $kontrol->ekipmanlar(),
            'sonuclar' => config('isg.periyodik_kontrol.sonuclar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'periyodik-kontrol-teftis-paketi-'.Str::slug($kontrol->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    public static function excel(PeriyodikKontrol $kontrol): StreamedResponse
    {
        $kontrol->loadMissing('firma');
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Periyodik Kontrol');

        $basliklar = [
            'Kategori', 'Ekipman', 'Tip', 'Seri No / Plaka', 'Marka / Model', 'Konum',
            'Kapasite', 'Yasal Standart', 'Periyot (Ay)', 'Son Muayene', 'Sonraki Vize',
            'Kalan Gün', 'Vize Durumu', 'Muayene Yapan', 'Rapor No', 'Sonuç', 'Notlar',
        ];
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle('A1:Q1')->getFont()->setBold(true);
        $s->getStyle('A1:Q1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
        $s->getStyle('A1:Q1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = 2;
        foreach ($kontrol->ekipmanlar() as $e) {
            $s->fromArray([
                $e->kategoriAdi(),
                $e->ekipman_adi,
                $e->tip ?: '—',
                $e->seri_no ?: '—',
                $e->marka_model ?: '—',
                $e->konum ?: '—',
                $e->kapasite ?: '—',
                $e->yasal_standart ?: '—',
                $e->muayene_periyodu_ay,
                $e->son_muayene_tarihi?->format('d.m.Y') ?? '—',
                $e->sonraki_vize_tarihi?->format('d.m.Y') ?? '—',
                $e->kalanGun() ?? '—',
                $e->vizeDurumEtiketi(),
                $e->muayene_yapan ?: '—',
                $e->rapor_no ?: '—',
                $e->sonucEtiketi(),
                $e->ozel_notlar ?: '',
            ], null, "A{$r}");
            $r++;
        }

        $s->getStyle('A1:Q'.max(1, $r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'Q') as $sutun) {
            $s->getColumnDimension($sutun)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pkt').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        $ad = 'periyodik-kontrol-teftis-paketi-'.Str::slug($kontrol->firma?->unvan ?? 'firma').'.xlsx';

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad);
    }
}
