<?php

namespace App\Support;

use App\Models\PkdKaydi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PKD Sicili çıktıları: tek kayıt için künye / özet formu PDF'i (PKD
 * dosyasının kapağı ve kontrol listesi olarak) ve sicil Excel'i.
 */
class PkdUretici
{
    public static function kunyePdf(PkdKaydi $p): StreamedResponse
    {
        $p->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.pkd-kunye', ['p' => $p, 'firma' => $p->firma])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'pkd-'.Str::slug($p->dokuman_no.'-'.$p->bolum).'.pdf',
        );
    }

    /** @param  Collection<int, PkdKaydi>  $kayitlar */
    public static function excel(Collection $kayitlar): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('PKD Sicili');

        $basliklar = [
            'Firma', 'Doküman No', 'Bölüm / Alan', 'Proses', 'Ortam', 'Zone', 'Tehlikeli Maddeler', 'Tutuşturucu Kaynaklar',
            'Önlemler', 'Revizyon', 'Doküman Tarihi', 'Sonraki Gözden Geçirme', 'Dosya', 'Durum', 'Sorumlu', 'Hazırlayan', 'Onaylayan', 'Not',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($kayitlar->values() as $i => $p) {
            $s->fromArray([
                $p->firma?->unvan,
                $p->dokuman_no,
                $p->bolum,
                $p->proses,
                $p->ortamEtiketi(),
                implode(', ', $p->etiketler('zonelar')),
                $p->tehlikeli_maddeler,
                implode(', ', $p->etiketler('tutusturucular')),
                implode(', ', $p->etiketler('onlemler')),
                $p->revizyon_no,
                $p->dokuman_tarihi?->format('d.m.Y'),
                $p->sonraki_gozden_gecirme?->format('d.m.Y'),
                $p->dosyaVarMi() ? 'Var' : 'Eksik',
                $p->durumEtiketi(),
                $p->sorumlu,
                $p->hazirlayan,
                $p->onaylayan,
                $p->notlar,
            ], null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'pkd').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'pkd-sicili-'.now()->format('Y-m-d').'.xlsx');
    }
}
