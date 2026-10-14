<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Risk Değerlendirmesi Excel (.xlsx) ve Word (.docx) çıktısı — PDF ile aynı veri
 * (RiskDegerlendirmesiUretici::veri: künye, yöntem ölçekleri, risk tablosu, ekip,
 * onay / imza sahipleri). Excel düzenlenebilir tablo için ek olarak Mevcut önlem
 * sütununu da taşır. Kullanıcı isteği 14.10.2026.
 */
class RiskDegerlendirmesiOfisUretici
{
    private const BASLIK_ARKA = 'E5E7EB';

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array{0: string, 1: \Closure(RiskMaddesi, int): mixed, 2: int}> [başlık, değer, Excel genişliği] */
    private static function sutunlar(RiskDegerlendirmesi $rd, bool $excel): array
    {
        $uc = RiskSkorlama::ucEksenliMi($rd->yontem);
        $f = $rd->yontem === 'fmea' ? 'D' : 'F';
        $s = fn ($v) => $v === null || $v === '' ? null : (float) $v;

        return array_values(array_filter([
            ['No', fn ($m, $i) => $i, 5],
            ['Bölüm', fn ($m) => $m->bolum, 18],
            ['Faaliyet', fn ($m) => $m->faaliyet, 18],
            ['Tehlike', fn ($m) => $m->tehlike, 30],
            ['Risk', fn ($m) => $m->risk, 24],
            $excel ? ['Mevcut önlem', fn ($m) => $m->mevcut_onlem, 30] : null,
            ['O', fn ($m) => $s($m->olasilik), 6],
            $uc ? [$f, fn ($m) => $s($m->frekans), 6] : null,
            ['Ş', fn ($m) => $s($m->siddet), 6],
            ['Puan', fn ($m) => $s($m->puan), 8],
            ['Düzey', fn ($m) => $m->duzey, 14],
            ['Öneri', fn ($m) => $m->oneri, 40],
            ['Sorumlu', fn ($m) => $m->sorumlu, 14],
            ['Termin', fn ($m) => $m->termin, 12],
            ['Son O', fn ($m) => $s($m->son_olasilik), 7],
            $uc ? ['Son '.$f, fn ($m) => $s($m->son_frekans), 7] : null,
            ['Son Ş', fn ($m) => $s($m->son_siddet), 7],
            ['Son Puan', fn ($m) => $s($m->son_puan), 8],
            ['Son Düzey', fn ($m) => $m->son_duzey, 14],
            ['Açıklama', fn ($m) => $m->aciklama, 20],
        ]));
    }

    /** @return array<int, array{0: string, 1: string}> */
    private static function kunye(RiskDegerlendirmesi $rd): array
    {
        $tarih = fn ($t) => $t ? $t->format('d.m.Y') : '—';

        return array_values(array_filter([
            ['Ticari Unvan', (string) $rd->firma_unvan],
            $rd->proje_adi ? ['İşyeri / Proje', $rd->proje_adi] : null,
            ['SGK Sicil No', $rd->firma_sgk_sicil_no ?: '—'],
            ['NACE Kodu', $rd->firma_nace ?: '—'],
            ['Tehlike Sınıfı', (string) $rd->firma?->tehlikeSinifiEtiketi()],
            ['Adres', $rd->firma_adres ?: '—'],
            ['Yöntem', config('isg.risk_yontemleri.'.$rd->yontem, $rd->yontem)],
            ['Belge No / Revizyon', trim($rd->belge_no.' / Rev. '.($rd->revizyon_no ?: '00'))],
            ['Rapor Tarihi', $tarih($rd->rapor_tarihi)],
            ['Geçerlilik Tarihi', $tarih($rd->gecerlilik_tarihi)],
        ]));
    }

    /**
     * Onay / imza satırları: [rol, ad, görseller (kaşe/imza, storage public yolu)].
     *
     * @return array<int, array{0: string, 1: string, 2: array<int, string>}>
     */
    private static function onaylar(array $v): array
    {
        $firma = $v['firma'];
        $imzali = $v['imzali'];
        $g = fn (...$yollar) => $imzali ? array_values(array_filter($yollar)) : [];

        return [
            ['İşveren / İşveren Vekili', (string) ($firma?->isveren_ad ?? ''), $g($firma?->isveren_kase_gorseli, $firma?->isveren_imza_gorseli)],
            ['İş Güvenliği Uzmanı', (string) ($v['uzman']->name ?? ''), $g($v['uzman']->kase_gorseli ?? null, $v['uzman']->imza_gorseli ?? null)],
            ['İşyeri Hekimi', (string) ($v['hekim']?->ad_soyad ?? ''), $g($v['hekim']?->kase_gorseli, $v['hekim']?->imza_gorseli)],
            ['Çalışan Temsilcisi', (string) ($v['temsilci']['ad'] ?? ''), $g($v['temsilci']['imza_gorseli'] ?? null)],
            ['Destek Elemanı', (string) ($v['destekElemani']['ad'] ?? ''), $g($v['destekElemani']['imza_gorseli'] ?? null)],
            ['Bilgi Sahibi Personel', (string) ($v['bilgiSahibiCalisan']['ad'] ?? ''), $g($v['bilgiSahibiCalisan']['imza_gorseli'] ?? null)],
        ];
    }

    private static function hex(?string $renk): ?string
    {
        return $renk ? strtoupper(ltrim($renk, '#')) : null;
    }

    private static function dosyaAdi(RiskDegerlendirmesi $rd, string $uzanti): string
    {
        return 'risk-degerlendirmesi-'.Str::slug($rd->firma_unvan ?: 'firma').'.'.$uzanti;
    }

    private static function sureyiAc(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Excel
    |--------------------------------------------------------------------------
    */

    public static function excel(RiskDegerlendirmesi $rd, bool $imzali = true): StreamedResponse
    {
        self::sureyiAc();
        $kitap = self::excelKitabi($rd, $imzali);
        $yazici = new Xlsx($kitap);

        return response()->streamDownload(fn () => $yazici->save('php://output'), self::dosyaAdi($rd, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function excelKitabi(RiskDegerlendirmesi $rd, bool $imzali = true): Spreadsheet
    {
        $v = RiskDegerlendirmesiUretici::veri($rd, $imzali);
        $kitap = new Spreadsheet;
        $kitap->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9);

        $sayfa = $kitap->getActiveSheet()->setTitle('Risk Değerlendirmesi');
        $sutunlar = self::sutunlar($rd, true);
        $son = Coordinate::stringFromColumnIndex(count($sutunlar));

        // Başlık + künye
        $sayfa->setCellValue('A1', 'RİSK DEĞERLENDİRMESİ');
        $sayfa->mergeCells("A1:{$son}1");
        $sayfa->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sayfa->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $satir = 3;
        foreach (self::kunye($rd) as [$etiket, $deger]) {
            $sayfa->setCellValue("A{$satir}", $etiket);
            $sayfa->mergeCells("A{$satir}:C{$satir}");
            $sayfa->setCellValue("D{$satir}", $deger);
            $sayfa->mergeCells("D{$satir}:{$son}{$satir}");
            $sayfa->getStyle("A{$satir}")->getFont()->setBold(true);
            $satir++;
        }

        // Risk tablosu
        $satir++;
        $baslikSatiri = $satir;
        foreach ($sutunlar as $i => [$baslik, , $genislik]) {
            $harf = Coordinate::stringFromColumnIndex($i + 1);
            $sayfa->setCellValue($harf.$satir, $baslik);
            $sayfa->getColumnDimension($harf)->setWidth($genislik);
        }
        $sayfa->getStyle("A{$satir}:{$son}{$satir}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $duzeyKolonlari = collect($sutunlar)->keys()->filter(fn ($i) => in_array($sutunlar[$i][0], ['Düzey', 'Son Düzey'], true));

        foreach ($rd->maddeler as $n => $m) {
            $satir++;
            foreach ($sutunlar as $i => [, $deger]) {
                $sayfa->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$satir, $deger($m, $n + 1));
            }

            foreach ($duzeyKolonlari as $i) {
                $puan = $sutunlar[$i][0] === 'Düzey' ? $m->puan : $m->son_puan;
                if ($puan) {
                    $renk = self::hex(RiskSkorlama::bant($rd->yontem, (float) $puan)['renk']);
                    $sayfa->getStyle(Coordinate::stringFromColumnIndex($i + 1).$satir)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $renk]],
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    ]);
                }
            }
        }

        $sayfa->getStyle("A{$baslikSatiri}:{$son}{$satir}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);
        $sayfa->setAutoFilter("A{$baslikSatiri}:{$son}{$satir}");
        $sayfa->freezePane('E'.($baslikSatiri + 1));

        // Ekip + onay
        $satir += 2;
        $satir = self::excelEkipVeOnay($sayfa, $v, $satir);

        $sayfa->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd($baslikSatiri, $baslikSatiri);
        $sayfa->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.4)->setBottom(0.4);
        $sayfa->getHeaderFooter()->setOddFooter('&L'.$rd->belge_no.'&RSayfa &P / &N');

        self::excelYontemSayfasi($kitap, $v, $rd);
        $kitap->setActiveSheetIndex(0);

        return $kitap;
    }

    private static function excelEkipVeOnay(Worksheet $sayfa, array $v, int $satir): int
    {
        $sayfa->setCellValue("A{$satir}", 'RİSK DEĞERLENDİRME EKİBİ VE ONAY');
        $sayfa->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(11);
        $satir++;

        $bas = $satir;
        foreach (['A' => 'Görevi', 'D' => 'Adı Soyadı', 'G' => 'İmza'] as $harf => $baslik) {
            $sayfa->setCellValue($harf.$satir, $baslik);
        }
        $sayfa->mergeCells("A{$satir}:C{$satir}");
        $sayfa->mergeCells("D{$satir}:F{$satir}");
        $sayfa->mergeCells("G{$satir}:J{$satir}");
        $sayfa->getStyle("A{$satir}:J{$satir}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
        ]);

        foreach (self::onaylar($v) as [$rol, $ad, $gorseller]) {
            $satir++;
            $sayfa->setCellValue("A{$satir}", $rol);
            $sayfa->setCellValue("D{$satir}", $ad);
            $sayfa->mergeCells("A{$satir}:C{$satir}");
            $sayfa->mergeCells("D{$satir}:F{$satir}");
            $sayfa->mergeCells("G{$satir}:J{$satir}");
            $sayfa->getRowDimension($satir)->setRowHeight($gorseller ? 48 : 30);

            $x = 4;
            foreach ($gorseller as $yol) {
                $tam = storage_path('app/public/'.$yol);
                if (! is_file($tam)) {
                    continue;
                }
                $resim = new Drawing;
                $resim->setPath($tam)->setHeight(56)->setCoordinates("G{$satir}")->setOffsetX($x)->setOffsetY(4)->setWorksheet($sayfa);
                $x += 110;
            }
        }

        $sayfa->getStyle("A{$bas}:J{$satir}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return $satir;
    }

    private static function excelYontemSayfasi(Spreadsheet $kitap, array $v, RiskDegerlendirmesi $rd): void
    {
        $sayfa = $kitap->createSheet()->setTitle('Yöntem');
        $met = $v['metodoloji'];
        $sayfa->getColumnDimension('A')->setWidth(22);
        $sayfa->getColumnDimension('B')->setWidth(12);
        $sayfa->getColumnDimension('C')->setWidth(14);
        $sayfa->getColumnDimension('D')->setWidth(80);

        $sayfa->setCellValue('A1', 'DEĞERLENDİRME YÖNTEMİ — '.config('isg.risk_yontemleri.'.$rd->yontem, $rd->yontem));
        $sayfa->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sayfa->setCellValue('A2', (string) ($met['aciklama'] ?? ''));
        $satir = 4;

        $olcek = function (string $baslik, array $degerler) use ($sayfa, &$satir): void {
            $sayfa->setCellValue("A{$satir}", $baslik);
            $sayfa->setCellValue("B{$satir}", 'Açıklama');
            $sayfa->getStyle("A{$satir}:D{$satir}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
            ]);
            $sayfa->mergeCells("B{$satir}:D{$satir}");
            foreach ($degerler as $puan => $aciklama) {
                $satir++;
                $sayfa->setCellValueExplicit("A{$satir}", str_replace('.', ',', (string) $puan), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sayfa->setCellValue("B{$satir}", $aciklama);
                $sayfa->mergeCells("B{$satir}:D{$satir}");
            }
            $satir += 2;
        };

        $etiket = $v['eksenEtiketleri'];
        $olcek($etiket['olasilik'] ?? 'Olasılık', $met['olasilik'] ?? []);
        if (isset($met['frekans'])) {
            $olcek($etiket['frekans'] ?? 'Frekans', $met['frekans']);
        }
        $olcek($etiket['siddet'] ?? 'Şiddet', $met['siddet'] ?? []);

        foreach (['A' => 'Düzey', 'B' => 'Kısaltma', 'C' => 'Puan aralığı', 'D' => 'Yapılacak eylem'] as $harf => $b) {
            $sayfa->setCellValue($harf.$satir, $b);
        }
        $sayfa->getStyle("A{$satir}:D{$satir}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
        ]);
        foreach ($met['bantlar'] ?? [] as $bant) {
            $satir++;
            $sayfa->setCellValue("A{$satir}", $bant['ad']);
            $sayfa->setCellValue("B{$satir}", $bant['kisa'] ?? '');
            $sayfa->setCellValue("C{$satir}", '≥ '.str_replace('.', ',', (string) $bant['min']));
            $sayfa->setCellValue("D{$satir}", $bant['eylem']);
            $sayfa->getStyle("A{$satir}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::hex($bant['renk'])]],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            ]);
        }
        $sayfa->getStyle('D1:D'.$satir)->getAlignment()->setWrapText(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Word
    |--------------------------------------------------------------------------
    */

    public static function word(RiskDegerlendirmesi $rd, bool $imzali = true): StreamedResponse
    {
        self::sureyiAc();
        $phpWord = self::wordBelgesi($rd, $imzali);

        $geciciYol = tempnam(sys_get_temp_dir(), 'mehse-risk').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($geciciYol);

        return response()->streamDownload(function () use ($geciciYol) {
            echo file_get_contents($geciciYol);
            @unlink($geciciYol);
        }, self::dosyaAdi($rd, 'docx'));
    }

    public static function wordBelgesi(RiskDegerlendirmesi $rd, bool $imzali = true): PhpWord
    {
        $v = RiskDegerlendirmesiUretici::veri($rd, $imzali);
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(8);
        $phpWord->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0]);

        $bolum = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 600, 'marginBottom' => 600, 'marginLeft' => 500, 'marginRight' => 500,
        ]);
        $bolum->addFooter()->addPreserveText($rd->belge_no.'     Sayfa {PAGE} / {NUMPAGES}', ['size' => 7, 'color' => '666666'], ['alignment' => Jc::END]);

        $bolum->addText('RİSK DEĞERLENDİRMESİ', ['bold' => true, 'size' => 15], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);

        // 1. Künye
        $bolum->addText('1. İŞYERİ KÜNYESİ', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $tablo = self::wordTablo($bolum);
        foreach (self::kunye($rd) as [$etiket, $deger]) {
            $tablo->addRow();
            $tablo->addCell(3500, ['bgColor' => self::BASLIK_ARKA])->addText($etiket, ['bold' => true]);
            $tablo->addCell(12000)->addText(self::metin($deger));
        }

        // 2. Yöntem
        $bolum->addTextBreak();
        self::wordYontem($bolum, $v);

        // 3. Risk tablosu
        $bolum->addPageBreak();
        $bolum->addText('3. TEHLİKELERİN TANIMLANMASI VE RİSK DEĞERLENDİRME TABLOSU', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $sutunlar = self::sutunlar($rd, false);
        $genislik = collect($sutunlar)->map(fn ($s) => $s[2])->all();
        $toplam = array_sum($genislik);
        $twip = fn (int $i) => (int) round($genislik[$i] / $toplam * 15800);

        $tablo = self::wordTablo($bolum);
        $tablo->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($sutunlar as $i => [$baslik]) {
            $tablo->addCell($twip($i), ['bgColor' => self::BASLIK_ARKA, 'valign' => 'center'])
                ->addText($baslik, ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER]);
        }

        foreach ($rd->maddeler as $n => $m) {
            $tablo->addRow(null, ['cantSplit' => true]);
            foreach ($sutunlar as $i => [$baslik, $deger]) {
                $stil = [];
                $yazi = ['size' => 7];
                if (in_array($baslik, ['Düzey', 'Son Düzey'], true)) {
                    $puan = $baslik === 'Düzey' ? $m->puan : $m->son_puan;
                    if ($puan) {
                        $stil['bgColor'] = self::hex(RiskSkorlama::bant($rd->yontem, (float) $puan)['renk']);
                        $yazi += ['bold' => true, 'color' => 'FFFFFF'];
                    }
                }
                $tablo->addCell($twip($i), $stil)->addText(self::metin($deger($m, $n + 1)), $yazi);
            }
        }

        // 4. Ekip + onay
        $bolum->addPageBreak();
        $bolum->addText('4. RİSK DEĞERLENDİRME EKİBİ', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $tablo = self::wordTablo($bolum);
        $tablo->addRow(null, ['tblHeader' => true]);
        $tablo->addCell(7000, ['bgColor' => self::BASLIK_ARKA])->addText('Ad Soyad', ['bold' => true]);
        $tablo->addCell(8800, ['bgColor' => self::BASLIK_ARKA])->addText('Unvan / Görev', ['bold' => true]);
        foreach ($v['ekipGosterim'] as $u) {
            $tablo->addRow(320);
            $tablo->addCell(7000)->addText(self::metin($u['ad'] ?? ''));
            $tablo->addCell(8800)->addText(self::metin($u['unvan'] ?? ''));
        }

        $bolum->addTextBreak();
        $bolum->addText('ONAY', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $tablo = self::wordTablo($bolum);
        $tablo->addRow(null, ['tblHeader' => true]);
        foreach (['Görevi' => 4000, 'Adı Soyadı' => 5000, 'İmza / Kaşe' => 6800] as $b => $g) {
            $tablo->addCell($g, ['bgColor' => self::BASLIK_ARKA])->addText($b, ['bold' => true]);
        }
        foreach (self::onaylar($v) as [$rol, $ad, $gorseller]) {
            $tablo->addRow(700, ['cantSplit' => true]);
            $tablo->addCell(4000, ['valign' => 'center'])->addText($rol, ['bold' => true]);
            $tablo->addCell(5000, ['valign' => 'center'])->addText(self::metin($ad));
            $hucre = $tablo->addCell(6800, ['valign' => 'center']);
            $kosu = $hucre->addTextRun();
            $eklendi = false;
            foreach ($gorseller as $yol) {
                $tam = storage_path('app/public/'.$yol);
                if (is_file($tam)) {
                    $kosu->addImage($tam, ['height' => 40]);
                    $kosu->addText('   ');
                    $eklendi = true;
                }
            }
            if (! $eklendi) {
                $kosu->addText('');
            }
        }

        return $phpWord;
    }

    private static function wordYontem(Section $bolum, array $v): void
    {
        $met = $v['metodoloji'];
        $etiket = $v['eksenEtiketleri'];
        $bolum->addText('2. DEĞERLENDİRME YÖNTEMİ', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $bolum->addText(self::metin($met['aciklama'] ?? ''), [], ['spaceAfter' => 80]);

        $olcek = function (string $baslik, array $degerler) use ($bolum): void {
            $tablo = self::wordTablo($bolum);
            $tablo->addRow(null, ['tblHeader' => true]);
            $tablo->addCell(2500, ['bgColor' => self::BASLIK_ARKA])->addText($baslik, ['bold' => true]);
            $tablo->addCell(13300, ['bgColor' => self::BASLIK_ARKA])->addText('Açıklama', ['bold' => true]);
            foreach ($degerler as $puan => $aciklama) {
                $tablo->addRow();
                $tablo->addCell(2500)->addText(str_replace('.', ',', (string) $puan), [], ['alignment' => Jc::CENTER]);
                $tablo->addCell(13300)->addText(self::metin($aciklama));
            }
            $bolum->addTextBreak();
        };

        $olcek($etiket['olasilik'] ?? 'Olasılık', $met['olasilik'] ?? []);
        if (isset($met['frekans'])) {
            $olcek($etiket['frekans'] ?? 'Frekans', $met['frekans']);
        }
        $olcek($etiket['siddet'] ?? 'Şiddet', $met['siddet'] ?? []);

        $tablo = self::wordTablo($bolum);
        $tablo->addRow(null, ['tblHeader' => true]);
        foreach (['Düzey' => 3500, 'Kısaltma' => 1300, 'Puan aralığı' => 1700, 'Yapılacak eylem' => 9300] as $b => $g) {
            $tablo->addCell($g, ['bgColor' => self::BASLIK_ARKA])->addText($b, ['bold' => true]);
        }
        foreach ($met['bantlar'] ?? [] as $bant) {
            $tablo->addRow();
            $tablo->addCell(3500, ['bgColor' => self::hex($bant['renk'])])->addText($bant['ad'], ['bold' => true, 'color' => 'FFFFFF']);
            $tablo->addCell(1300)->addText((string) ($bant['kisa'] ?? ''), ['bold' => true], ['alignment' => Jc::CENTER]);
            $tablo->addCell(1700)->addText('≥ '.str_replace('.', ',', (string) $bant['min']));
            $tablo->addCell(9300)->addText(self::metin($bant['eylem']));
        }
    }

    private static function wordTablo(Section $bolum): \PhpOffice\PhpWord\Element\Table
    {
        return $bolum->addTable(['borderSize' => 4, 'borderColor' => '9CA3AF', 'cellMargin' => 40, 'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED]);
    }

    /** Word metni: sayılar virgüllü, null boş; XML'de geçersiz kontrol karakterleri temizlenir. */
    private static function metin(mixed $deger): string
    {
        if (is_float($deger)) {
            $deger = rtrim(rtrim(number_format($deger, 1, ',', ''), '0'), ',');
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) ($deger ?? ''));
    }
}
