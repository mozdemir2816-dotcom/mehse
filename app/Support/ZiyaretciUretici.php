<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\Ziyaretci;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ziyaretçi Yönetimi çıktıları: QR'lı geçiş kartı PDF'i, acil durum için
 * "İçerideki Ziyaretçiler" listesi ve ziyaretçi defteri Excel'i.
 */
class ZiyaretciUretici
{
    public static function kartPdf(Ziyaretci $z): StreamedResponse
    {
        $z->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.ziyaretci-karti', ['z' => $z, 'firma' => $z->firma])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'ziyaretci-karti-'.Str::slug($z->kart_no.'-'.$z->ad_soyad).'.pdf',
        );
    }

    /** @param  Collection<int, Ziyaretci>  $iceridekiler */
    public static function iceridekilerPdf(Firma $firma, Collection $iceridekiler): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.ziyaretci-iceridekiler', ['firma' => $firma, 'liste' => $iceridekiler])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'icerideki-ziyaretciler-'.Str::slug($firma->unvan).'-'.now()->format('Y-m-d-Hi').'.pdf',
        );
    }

    /** @param  Collection<int, Ziyaretci>  $liste */
    public static function defterExcel(Collection $liste): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Ziyaretçi Defteri');

        $basliklar = [
            'Kart No', 'İşyeri', 'Ad Soyad', 'Kurum', 'Telefon', 'Ziyaret Amacı', 'Ziyaret Edilen',
            'Geçerlilik Başlangıcı', 'Geçerlilik Bitişi', 'Giriş', 'Çıkış', 'İSG Bilgilendirme', 'Verilen KKD', 'Durum', 'Not',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($liste->values() as $i => $z) {
            $s->fromArray([
                $z->kart_no,
                $z->firma?->unvan,
                $z->ad_soyad,
                $z->kurum,
                $z->telefon,
                $z->ziyaret_amaci,
                $z->ziyaret_edilen,
                $z->gecerlilik_baslangic->format('d.m.Y H:i'),
                $z->gecerlilik_bitis->format('d.m.Y H:i'),
                $z->giris_zamani?->format('d.m.Y H:i'),
                $z->cikis_zamani?->format('d.m.Y H:i'),
                $z->isg_bilgilendirme ? 'Evet' : 'Hayır',
                $z->verilen_kkd,
                $z->durumEtiketi(),
                $z->notlar,
            ], null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'zyr').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'ziyaretci-defteri-'.now()->format('Y-m-d').'.xlsx');
    }
}
