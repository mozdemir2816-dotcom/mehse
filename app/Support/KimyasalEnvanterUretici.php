<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kimyasal Ürün Envanter Listesi PDF'i (dompdf) — Kimyasal Maddelerle Çalışmalarda
 * İSG Yönetmeliği kapsamında istenen tehlikeli kimyasal envanteri.
 */
class KimyasalEnvanterUretici
{
    public static function pdf(Firma $firma): StreamedResponse
    {
        $urunler = $firma->kimyasalUrunler()->where('aktif', true)->orderBy('urun_adi')->get();

        $pdf = Pdf::loadView('pdf.kimyasal-envanter', [
            'firma' => $firma,
            'urunler' => $urunler,
            'ghsTanim' => config('isg.kimyasal.ghs'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kimyasal-envanter-'.Str::slug($firma->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** SDS / GBF sicili Excel'i (isgsuite "Excel Rapor" sütunları + GHS ve depolama). */
    public static function excel(Firma $firma): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('SDS Sicili');

        $basliklar = [
            'Ürün', 'CAS', 'Tedarikçi', 'Fiziksel Hal', 'Kullanım Alanı', 'Miktar', 'GHS / CLP',
            'SDS', 'SDS Tarihi', 'Sonraki Gözden Geçirme', 'Durum', 'Depolama', 'Açıklama',
        ];
        $son = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        $durum = ['gecikmis' => 'Gecikmiş', 'yaklasan' => 'Yaklaşıyor'];

        foreach ($firma->kimyasalUrunler()->where('aktif', true)->orderBy('urun_adi')->get()->values() as $i => $u) {
            $s->fromArray([
                $u->urun_adi,
                $u->cas_no,
                $u->tedarikci,
                config('isg.kimyasal.fiziksel_hal.'.$u->fiziksel_hal),
                $u->kullanim_alani,
                $u->miktar,
                implode(', ', $u->ghsEtiketleri()),
                $u->sdsVarMi() ? 'Var' : 'Eksik',
                $u->sds_tarihi?->format('d.m.Y'),
                $u->sonraki_gozden_gecirme?->format('d.m.Y'),
                $durum[$u->gozdenGecirmeDurumu()] ?? 'Güncel',
                $u->depolama,
                $u->aciklama,
            ], null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'sds').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'sds-sicili-'.Str::slug($firma->unvan ?? 'firma').'.xlsx');
    }
}
