<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firma personel listesi — Excel ve PDF rapor (isgsuite "Excel Rapor / PDF Rapor"
 * karşılığı). Excel başlıkları CalisanExcelIceAktarici ile birebir aynıdır: rapor
 * düzenlenip "Excel'den Toplu Yükle" ile geri yüklenebilir.
 *
 * $gorunum: 'aktif' | 'pasif' | 'tumu'
 */
class CalisanListesiUretici
{
    public const GORUNUMLER = ['aktif' => 'Aktif personel', 'pasif' => 'Pasif personel', 'tumu' => 'Tüm personel'];

    /** @return Collection<int, Calisan> */
    public static function calisanlar(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): Collection
    {
        return Calisan::query()
            ->where('firma_id', $firma->id)
            ->when($gorunum === 'aktif', fn ($q) => $q->where('aktif', true))
            ->when($gorunum === 'pasif', fn ($q) => $q->where('aktif', false))
            ->when(filled($sube), fn ($q) => $q->where('sube', $sube))
            ->orderBy('ad_soyad')
            ->get();
    }

    /** @return array{toplam: int, kadin: int, erkek: int, belirtilmemis: int, engelli: int} */
    public static function ozet(Collection $calisanlar): array
    {
        return [
            'toplam' => $calisanlar->count(),
            'kadin' => $calisanlar->where('cinsiyet', 'kadin')->count(),
            'erkek' => $calisanlar->where('cinsiyet', 'erkek')->count(),
            'belirtilmemis' => $calisanlar->filter(fn (Calisan $c) => blank($c->cinsiyet))->count(),
            'engelli' => $calisanlar->filter(fn (Calisan $c) => $c->engelliMi())->count(),
        ];
    }

    public static function excel(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): StreamedResponse
    {
        ExcelBellek::artir();

        $kitap = static::excelKitabi($firma, $gorunum, $sube);
        $yazici = new Xlsx($kitap);

        return response()->streamDownload(
            fn () => $yazici->save('php://output'),
            'personel-listesi-'.Str::slug($firma->unvan).'.xlsx',
        );
    }

    public static function excelKitabi(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): Spreadsheet
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('PERSONEL LİSTESİ');

        $basliklar = ['#', ...CalisanExcelIceAktarici::SABLON_BASLIKLARI];
        $s->fromArray($basliklar, null, 'A1');

        $satir = 2;
        foreach (static::calisanlar($firma, $gorunum, $sube) as $i => $c) {
            $s->fromArray([
                $i + 1,
                $c->ad_soyad,
                null, // TC metin olarak aşağıda yazılır (bilimsel gösterime dönmesin)
                $c->gorev,
                $c->departman,
                $c->sube,
                $c->cinsiyetEtiketi(),
                $c->ise_giris?->format('d.m.Y'),
                $c->isten_cikis?->format('d.m.Y'),
                $c->ozel_durum,
                $c->dogum_tarihi?->format('d.m.Y'),
                $c->kan_grubu,
                $c->telefon,
                $c->eposta,
                $c->agir_tehlikeli_iste ? 'Evet' : 'Hayır',
                $c->aktif ? 'Evet' : 'Hayır',
                $c->notlar,
            ], null, "A{$satir}");
            $s->setCellValueExplicit("C{$satir}", (string) $c->tc, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $satir++;
        }

        $son = $s->getHighestColumn();
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F4C5C');
        $s->getStyle('C:C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $s->freezePane('B2');
        $s->setAutoFilter("A1:{$son}".max(1, $satir - 1));

        foreach (range('A', $son) as $harf) {
            $s->getColumnDimension($harf)->setAutoSize(true);
        }

        return $kitap;
    }

    public static function pdf(Firma $firma, string $gorunum = 'aktif', ?string $sube = null): StreamedResponse
    {
        $calisanlar = static::calisanlar($firma, $gorunum, $sube);

        $pdf = Pdf::loadView('pdf.personel-listesi', [
            'firma' => $firma,
            'calisanlar' => $calisanlar,
            'ozet' => static::ozet($calisanlar),
            'gorunum' => self::GORUNUMLER[$gorunum] ?? '',
            'sube' => $sube,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'personel-listesi-'.Str::slug($firma->unvan).'.pdf',
        );
    }
}
