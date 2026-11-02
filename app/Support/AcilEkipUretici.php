<?php

namespace App\Support;

use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Acil Durum Ekipleri / Destek Elemanları — Excel listesi ve PDF ekip çizelgesi. */
class AcilEkipUretici
{
    /** @param  Collection<int, AcilEkip>  $ekipler */
    public static function excel(Firma $firma, Collection $ekipler): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Destek Elemanları');

        $basliklar = ['Ekip', 'Ekip Durumu', 'Ad Soyad', 'Üyelik', 'Lider', 'Görev', 'Bölüm', 'Sicil No', 'Telefon', 'Vardiya', 'Belge No', 'Belge Tarihi', 'Geçerlilik Sonu', 'Belge Durumu'];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9F2EF');

        $ozet = AcilEkipDurumu::ozet($firma, $ekipler);
        $durumAd = ['tam' => 'Tam', 'eksik' => 'Eksik', 'kritik' => 'Kritik'];
        $satir = 2;

        foreach ($ekipler as $e) {
            $d = $ozet['durumlar'][$e->id];
            $uyeler = $e->uyeler->isEmpty() ? collect([null]) : $e->uyeler;

            foreach ($uyeler as $u) {
                /** @var AcilEkipUyesi|null $u */
                $s->fromArray([
                    $e->ad, $durumAd[$d['durum']].' (asıl '.$d['asil'].' / asgari '.$d['min'].')',
                    $u?->ad_soyad ?? '(üye atanmamış)', $u?->uyelikEtiketi(), $u?->lider ? 'Evet' : null,
                    $u?->gorev, $u?->bolum, $u?->sicil_no, $u?->telefon, $u?->vardiya, $u?->belge_no,
                    $u?->belge_tarihi?->format('d.m.Y'), $u?->belgeBitisTarihi()?->format('d.m.Y'),
                    $u ? AcilEkipUyesi::BELGE_DURUMLARI[$u->belgeDurumu()] : null,
                ], null, 'A'.$satir);
                $satir++;
            }
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'ekp').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'acil-durum-ekipleri-'.Str::slug($firma->unvan).'.xlsx');
    }

    /** @param  Collection<int, AcilEkip>  $ekipler */
    /**
     * Destek elemanı görevlendirme ve tebellüğ belgeleri (02.11.2026) — her üye için tek sayfa
     * (Acil Durumlar Yön. Md.11). T.C. kimlik no kayıtlı çalışandan; yoksa elle doldurulacak noktalı satır.
     * $uyeId verilirse yalnız o üyenin belgesi.
     */
    public static function tebellugPdf(Firma $firma, Collection $ekipler, ?int $uyeId = null): ?StreamedResponse
    {
        $uyeler = $ekipler
            ->flatMap(fn (AcilEkip $e) => $e->uyeler->each(fn (AcilEkipUyesi $u) => $u->setRelation('ekip', $e)))
            ->when($uyeId, fn ($c) => $c->where('id', $uyeId))
            ->values();

        if ($uyeler->isEmpty()) {
            return null;
        }
        $uyeler = (new \Illuminate\Database\Eloquent\Collection($uyeler->all()))->load('calisan');   // T.C. kimlik no

        $pdf = Pdf::loadView('pdf.acil-ekip-tebellug', [
            'firma' => $firma,
            'uyeler' => $uyeler,
            'isveren' => \App\Support\FirmaImza::isveren($firma),
            'uzman' => \App\Support\FirmaImza::uzman($firma),
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        $ad = 'destek-elemani-tebellug-'.Str::slug($firma->unvan).($uyeId ? '-'.Str::slug($uyeler->first()->ad_soyad) : '').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    public static function pdf(Firma $firma, Collection $ekipler): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.acil-durum-ekipleri', [
            'firma' => $firma,
            'ekipler' => $ekipler,
            'ozet' => AcilEkipDurumu::ozet($firma, $ekipler),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(fn () => print ($pdf->output()), 'acil-durum-ekipleri-'.Str::slug($firma->unvan).'.pdf');
    }
}
