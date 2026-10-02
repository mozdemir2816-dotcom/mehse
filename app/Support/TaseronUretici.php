<?php

namespace App\Support;

use App\Models\Taseron;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Taşeron / Alt İşveren çıktıları: tek firma için Uygunluk Raporu PDF'i ve
 * listedeki tüm taşeronların özet Excel'i.
 */
class TaseronUretici
{
    public static function uygunlukPdf(Taseron $t): StreamedResponse
    {
        $t->loadMissing(['firma.igu', 'calisanlar', 'belgeler', 'isIzinleri']);

        $pdf = Pdf::loadView('pdf.taseron-uygunluk', ['t' => $t, 'firma' => $t->firma])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'taseron-uygunluk-'.Str::slug($t->unvan).'.pdf',
        );
    }

    /** @param  Collection<int, Taseron>  $taseronlar */
    public static function listeExcel(Collection $taseronlar): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Taşeronlar');

        $basliklar = [
            'İşyeri', 'Tür', 'Ünvan', 'Faaliyet', 'Vergi No', 'SGK Sicil No', 'Sözleşme No',
            'Sözleşme Başlangıç', 'Sözleşme Bitiş', 'Yetkili', 'Telefon', 'Aktif Çalışan',
            'Belge', 'Durum', 'Eksikler',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        $satir = 2;
        foreach ($taseronlar as $t) {
            $eksik = $t->eksikler();
            $s->fromArray([
                $t->firma?->unvan,
                $t->turEtiketi(),
                $t->unvan,
                $t->faaliyet,
                $t->vergi_no,
                $t->sgk_sicil_no,
                $t->sozlesme_no,
                $t->sozlesme_baslangic?->format('d.m.Y'),
                $t->sozlesme_bitis?->format('d.m.Y'),
                $t->yetkili,
                $t->telefon,
                $t->calisanlar->where('aktif', true)->count(),
                $t->belgeler->count(),
                ! $t->aktif ? 'Pasif' : ($eksik ? 'Eksik var' : 'Uygun'),
                implode("\n", $eksik),
            ], null, 'A'.$satir);
            $satir++;
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'tas').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'taseronlar-'.now()->format('Y-m-d').'.xlsx');
    }
}
