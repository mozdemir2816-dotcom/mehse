<?php

namespace App\Support;

use App\Models\OrtamOlcumu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ortam Ölçümleri çıktıları: Takip Listesi PDF'i (dompdf, A4 yatay) ve Ölçüm
 * Defteri Excel'i (güncel ölçümler + geçmiş ölçümler sayfası).
 */
class OrtamOlcumuUretici
{
    public static function pdf(OrtamOlcumu $olcum): StreamedResponse
    {
        $olcum->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ortam-olcumleri', [
            'olcum' => $olcum,
            'firma' => $olcum->firma,
            'sonuclar' => config('isg.ortam_olcum.sonuclar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'ortam-olcumleri-'.Str::slug($olcum->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    public static function excel(OrtamOlcumu $olcum): StreamedResponse
    {
        ExcelBellek::artir();
        $olcum->loadMissing('firma');

        $sonuclar = config('isg.ortam_olcum.sonuclar');
        $terminler = config('isg.ortam_olcum.termin_durumlari');
        $tarih = fn ($t) => filled($t) ? Carbon::parse($t)->format('d.m.Y') : null;

        $kitap = new Spreadsheet;

        $defter = $kitap->getActiveSheet();
        $defter->setTitle('Ölçüm Defteri');
        self::tablo($defter, [
            'Grup', 'Parametre', 'Yer / Ölçüm Noktası', 'Ölçüm Tarihi', 'Değer', 'Birim', 'Limit',
            'Sonuç', 'Laboratuvar', 'Rapor No', 'Periyot (ay)', 'Sonraki Ölçüm', 'Durum', 'Not',
        ], collect($olcum->olcumler ?? [])->map(fn (array $m) => [
            $m['grup'] ?? null,
            $m['parametre'] ?? null,
            $m['bolge'] ?? null,
            $tarih($m['olcum_tarihi'] ?? null),
            $m['olculen_deger'] ?? null,
            $m['birim'] ?? null,
            $m['sinir_deger'] ?? null,
            $sonuclar[$m['sonuc'] ?? 'bekliyor'] ?? null,
            $m['laboratuvar'] ?? null,
            $m['rapor_no'] ?? null,
            $m['periyot_ay'] ?? null,
            $tarih($m['sonraki_olcum_tarihi'] ?? null),
            $terminler[OrtamOlcumu::terminDurumu($m)],
            $m['not'] ?? null,
        ])->all());

        $gecmis = $kitap->createSheet();
        $gecmis->setTitle('Geçmiş Ölçümler');
        self::tablo($gecmis, [
            'Parametre', 'Yer / Ölçüm Noktası', 'Ölçüm Tarihi', 'Değer', 'Birim', 'Limit', 'Sonuç', 'Laboratuvar', 'Rapor No',
        ], collect($olcum->olcumler ?? [])->flatMap(fn (array $m) => collect($m['gecmis'] ?? [])->map(fn (array $g) => [
            $m['parametre'] ?? null,
            $m['bolge'] ?? null,
            $tarih($g['olcum_tarihi'] ?? null),
            $g['olculen_deger'] ?? null,
            $g['birim'] ?? null,
            $g['sinir_deger'] ?? null,
            $sonuclar[$g['sonuc'] ?? 'bekliyor'] ?? null,
            $g['laboratuvar'] ?? null,
            $g['rapor_no'] ?? null,
        ]))->all());

        $kitap->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'ort').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'ortam-olcum-defteri-'.Str::slug($olcum->firma?->unvan ?? 'firma').'.xlsx');
    }

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private static function tablo(Worksheet $s, array $basliklar, array $satirlar): void
    {
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($satirlar as $i => $satir) {
            $s->fromArray($satir, null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');
    }
}
