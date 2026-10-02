<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KkdZimmet;
use App\Models\KkdZimmetFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KKD Takip çıktıları: zimmet sicili Excel'i (isgsuite "kkd-kayitlari" sütunları)
 * ve bir personelin teslimdeki tüm KKD'lerini tek tutanağa döken zimmet formu
 * (mevcut KKD Zimmet Formu PDF şablonu kullanılır, kayıt oluşturulmaz).
 */
class KkdTakipUretici
{
    /** @param  Collection<int, KkdZimmet>  $zimmetler */
    public static function excel(Firma $firma, Collection $zimmetler): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('KKD Kayıtları');

        $basliklar = [
            'No', 'Teslim', 'Personel', 'Bölüm', 'Kategori', 'Tür', 'Adet', 'Marka', 'Model', 'Beden',
            'Seri No', 'Yenileme', 'SKT', 'Kalan Gün', 'Durum', 'Takip', 'Teslim Eden', 'Risk / Kullanım Alanı', 'Açıklama',
        ];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        $satir = 2;
        foreach ($zimmetler as $z) {
            $s->fromArray([
                $z->zimmet_no,
                $z->teslim_tarihi?->format('d.m.Y'),
                $z->personel_ad_soyad,
                $z->bolum,
                $z->kategori ? $z->kategoriEtiketi() : null,
                $z->tur,
                $z->adet,
                $z->marka,
                $z->model,
                $z->beden,
                $z->seri_no,
                $z->yenileme_tarihi?->format('d.m.Y'),
                $z->son_kullanma?->format('d.m.Y'),
                $z->aktifMi() ? $z->kalanGun() : null,
                $z->durumEtiketi(),
                $z->aktifMi() ? $z->takipEtiketi() : null,
                $z->teslim_eden,
                $z->risk_alani,
                $z->aciklama,
            ], null, 'A'.$satir);
            $satir++;
        }

        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'kkd').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'kkd-kayitlari-'.Str::slug($firma->unvan).'.xlsx');
    }

    /**
     * Personelin teslimdeki tüm KKD'leri (aynı çalışan, yoksa aynı ad) tek
     * zimmet tutanağında. Tarih = en son teslim tarihi.
     */
    public static function personelZimmetFormu(KkdZimmet $zimmet): StreamedResponse
    {
        $zimmet->loadMissing('firma', 'calisan');

        $kalemler = KkdZimmet::query()
            ->where('firma_id', $zimmet->firma_id)
            ->where('durum', 'teslim_edildi')
            ->when(
                $zimmet->calisan_id,
                fn ($q) => $q->where('calisan_id', $zimmet->calisan_id),
                fn ($q) => $q->whereNull('calisan_id')->where('personel_ad_soyad', $zimmet->personel_ad_soyad),
            )
            ->orderBy('teslim_tarihi')
            ->get();

        if ($kalemler->isEmpty()) {
            $kalemler = collect([$zimmet]);
        }

        $form = new KkdZimmetFormu([
            'firma_id' => $zimmet->firma_id,
            'form_no' => $zimmet->zimmet_no,
            'teslim_tarihi' => $kalemler->max('teslim_tarihi'),
            'periyodik_kontrol_tarihi' => $kalemler->map->vade()->filter()->sort()->first(),
            'teslim_eden' => $zimmet->teslim_eden,
            'calisanlar' => [[
                'ad_soyad' => $zimmet->personel_ad_soyad,
                'tc' => $zimmet->calisan?->tc,
                'departman' => $zimmet->bolum ?: $zimmet->calisan?->gorev,
            ]],
            'kkdler' => $kalemler->map(fn (KkdZimmet $k) => [
                'ad' => collect([$k->tur, $k->markaModel() ?: null, $k->beden ? 'Beden '.$k->beden : null])->filter()->implode(' — '),
                'standart' => $k->standart() ?? '',
                'kategori' => $k->kategori,
                'donem' => $k->vade()?->format('d.m.Y'),
                'miktar' => $k->adet,
            ])->values()->all(),
        ]);
        $form->setRelation('firma', $zimmet->firma);

        $pdf = Pdf::loadView('pdf.kkd-zimmet-formu', ['form' => $form, 'firma' => $zimmet->firma])->setPaper('a4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'kkd-zimmet-'.Str::slug($zimmet->personel_ad_soyad ?: 'personel').'.pdf',
        );
    }
}
