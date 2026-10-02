<?php

namespace App\Support;

use App\Models\SahaBulgusu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hızlı Saha Bulgusu çıktıları: tek bulgu için tespit tutanağı (fotoğraflı
 * PDF) ve bulgu listesi Excel'i.
 */
class SahaBulgusuUretici
{
    public static function pdf(SahaBulgusu $b): StreamedResponse
    {
        $b->loadMissing('firma.igu');

        $pdf = Pdf::loadView('pdf.saha-bulgusu', ['b' => $b, 'firma' => $b->firma])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), 'saha-bulgusu-'.Str::slug($b->bulgu_no).'.pdf');
    }

    /** @param  Collection<int, SahaBulgusu>  $bulgular */
    public static function excel(Collection $bulgular): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Saha Bulguları');

        $basliklar = [
            'Bulgu No', 'Tarih', 'İşyeri', 'Bölüm', 'Gözlem Konumu', 'Kategori', 'Tehlike', 'Uygunsuzluk', 'Mevcut Önlemler',
            'Olasılık', 'Şiddet', 'Skor', 'Seviye', 'Aksiyon', 'Sorumlu', 'Termin', 'Durum', 'Kapanış', 'Kapanış Notu', 'Konum (GPS)', 'Kaynak',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($bulgular->values() as $i => $b) {
            $s->fromArray([
                $b->bulgu_no,
                $b->created_at?->format('d.m.Y H:i'),
                $b->firma?->unvan,
                $b->bolum,
                $b->gozlem_konumu,
                $b->kategori,
                $b->tehlike,
                $b->uygunsuzluk,
                $b->mevcut_onlemler,
                $b->olasilik,
                $b->siddet,
                $b->skor(),
                $b->seviyeEtiketi(),
                $b->aksiyon,
                $b->sorumlu,
                $b->termin?->format('d.m.Y'),
                config('isg.saha_bulgu.durumlar.'.$b->durum, $b->durum),
                $b->kapanis_tarihi?->format('d.m.Y'),
                $b->kapanis_notu,
                $b->konumLinki(),
                $b->kaynak === 'ai' ? 'AI taslağı + uzman' : 'Manuel',
            ], null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'sbg').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'saha-bulgulari-'.now()->format('Y-m-d').'.xlsx');
    }
}
