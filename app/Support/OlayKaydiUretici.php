<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\OlayKaydi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Olay Kaydı çıktıları: tek kayıt için inceleme raporu PDF'i (dompdf) +
 * bir firmanın tüm olaylarını içeren "Olay Kayıt Defteri" Excel'i.
 */
class OlayKaydiUretici
{
    public static function pdf(OlayKaydi $o): StreamedResponse
    {
        $o->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.olay-kaydi', [
            'kayit' => $o,
            'firma' => $o->firma,
        ])->setPaper('a4');

        $ad = 'olay-kaydi-'.Str::slug($o->belge_no ?: ($o->firma?->unvan ?? 'firma')).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** Firmanın tüm olay kayıtları — tek sayfalık kayıt defteri Excel'i. */
    public static function defterExcel(Firma $firma): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Olay Kayıt Defteri');

        $basliklar = [
            'Belge No', 'Olay Tipi', 'Olay Tarihi', 'Saati', 'Yeri', 'Bildiren',
            'Etkilenen Kişi', 'Sonuç Türü', 'Olasılık', 'Şiddet', 'Potansiyel Skor',
            'Potansiyel Seviye', 'Kök Neden Kategorileri', '5N Neden Zinciri', 'Kök Neden',
            'Düzeltici Faaliyet', 'DÖF No', 'SGK Bildirimi', 'Kayıp Gün',
        ];
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle('A1:S1')->getFont()->setBold(true);
        $s->getStyle('A1:S1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        $satir = 2;
        foreach ($firma->olayKayitlari()->with('dofRaporu')->latest('olay_tarihi')->latest()->get() as $o) {
            $s->fromArray([
                $o->belge_no,
                $o->tipEtiketi(),
                $o->olay_tarihi?->format('d.m.Y'),
                $o->olay_saati,
                $o->olay_yeri,
                $o->bildiren_ad_soyad,
                $o->etkilenen_ad_soyad,
                $o->sonucEtiketi(),
                $o->olasilikEtiketi(),
                $o->siddetEtiketi(),
                $o->potansiyel_skor,
                $o->potansiyelSeviye(),
                implode(', ', $o->kokNedenEtiketleri()),
                collect($o->nedenZinciri())->map(fn ($n, $i) => ($i + 1).'. '.$n)->implode("\n"),
                $o->kok_neden,
                $o->duzeltici_faaliyet,
                $o->dofRaporu?->belge_no,
                $o->sgk_bildirimi_yapildi ? ('Evet '.($o->sgk_bildirim_tarihi?->format('d.m.Y') ?? '')) : 'Hayır',
                $o->kayip_gun_sayisi,
            ], null, 'A'.$satir);
            $satir++;
        }

        foreach (range('A', 'S') as $sutun) {
            $s->getColumnDimension($sutun)->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'olay').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'olay-kayit-defteri-'.Str::slug($firma->unvan).'.xlsx');
    }
}
