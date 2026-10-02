<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\OlayKaydi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
    public static function pdf(OlayKaydi $o, bool $imzali = true): StreamedResponse
    {
        $o->loadMissing(['firma.igu', 'firma.isyeriHekimi', 'dofRaporu']);

        $pdf = Pdf::loadView('pdf.olay-kaydi', [
            'kayit' => $o,
            'firma' => $o->firma,
            'imzali' => $imzali,
        ])->setPaper('a4');

        $ad = 'olay-kaydi-'.Str::slug($o->belge_no ?: ($o->firma?->unvan ?? 'firma')).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /** Balık Kılçığı (Ishikawa) kök neden analizi — tek sayfa A4 yatay. */
    public static function balikKilcigiPdf(OlayKaydi $o): StreamedResponse
    {
        $o->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.balik-kilcigi', [
            'kayit' => $o,
            'firma' => $o->firma,
        ])->setPaper('a4', 'landscape');

        $ad = 'balik-kilcigi-'.Str::slug($o->belge_no ?: ($o->firma?->unvan ?? 'firma')).'.pdf';

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
            'Belge No', 'Olay Tipi', 'Durum', 'Olay Tarihi', 'Saati', 'Yeri', 'Bölüm', 'Alan',
            'Sınıflandırma', 'Yapılan İş', 'Özet', 'Bildiren', 'Etkilenen Kişi', 'Olay Etkileri',
            'Sonuç Türü', 'Olasılık', 'Şiddet', 'Potansiyel Skor', 'Potansiyel Seviye',
            'Risk Analizinde', 'Kök Neden Kategorileri', '5N Neden Zinciri', 'Kök Neden',
            'Sistemsel Eksiklik', 'Düzeltici Faaliyet', 'DÖF No', 'Kaza Türü', 'SGK Bildirimi',
            'Kolluk Bildirimi', 'Kayıp Gün', 'İSG Uzmanı', 'İşyeri Hekimi', 'İşveren Vekili',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        $satir = 2;
        foreach ($firma->olayKayitlari()->with('dofRaporu')->latest('olay_tarihi')->latest()->get() as $o) {
            $s->fromArray([
                $o->belge_no,
                $o->tipEtiketi(),
                $o->durumEtiketi(),
                $o->olay_tarihi?->format('d.m.Y'),
                $o->olay_saati,
                $o->olay_yeri,
                $o->bolum,
                $o->alan,
                $o->siniflandirma ? $o->siniflandirmaEtiketi() : null,
                $o->yapilan_is,
                $o->olay_ozeti,
                $o->bildiren_ad_soyad,
                $o->etkilenen_ad_soyad,
                implode(', ', $o->etkiEtiketleri()),
                $o->sonucEtiketi(),
                $o->olasilikEtiketi(),
                $o->siddetEtiketi(),
                $o->potansiyel_skor,
                $o->potansiyelSeviye(),
                $o->risk_analizinde ? $o->riskAnalizindeEtiketi() : null,
                implode(', ', $o->kokNedenEtiketleri()),
                collect($o->nedenZinciri())->map(fn ($n, $i) => ($i + 1).'. '.$n)->implode("\n"),
                $o->kok_neden,
                $o->sistemsel_eksiklik,
                $o->duzeltici_faaliyet,
                $o->dofRaporu?->belge_no,
                $o->kaza_turu ? $o->kazaTuruEtiketi() : null,
                $o->sgk_bildirimi_yapildi ? trim('Evet '.($o->sgk_bildirim_tarihi?->format('d.m.Y') ?? '')) : 'Hayır',
                $o->kolluk_bildirimi_yapildi ? 'Evet' : 'Hayır',
                $o->kayip_gun_sayisi,
                $o->rapor_hazirlayan,
                $o->isyeri_hekimi,
                $o->isveren_vekili,
            ], null, 'A'.$satir);
            $satir++;
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
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
