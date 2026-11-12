<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Risk Değerlendirmesi Excel (.xlsx) ve Word (.docx) çıktısı — PDF ile aynı veri
 * (RiskDegerlendirmesiUretici::veri: künye, yöntem ölçekleri, risk tablosu, ekip,
 * onay / imza sahipleri). Kullanıcı isteği 14.10.2026. 08.11.2026: Excel PDF'in birebir karşılığı —
 * Kapak / Prosedür / Risk Değerlendirmesi sekmeleri, PDF ile aynı sütunlar (Mevcut önlem yok).
 */
class RiskDegerlendirmesiOfisUretici
{
    private const BASLIK_ARKA = 'E5E7EB';

    /** Risk tablosu başlık satırı (PDF ile aynı mor). */
    private const TABLO_BASLIK = '7C3AED';

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array{0: string, 1: \Closure(RiskMaddesi, int): mixed, 2: int}> [başlık, değer, Excel genişliği] */
    private static function sutunlar(RiskDegerlendirmesi $rd, bool $excel): array
    {
        // HAZOP: kaynak Excel'deki düzen, puan sütunları yok.
        if ($rd->yontem === 'hazop') {
            return HazopCiktisi::sutunlar();
        }

        $uc = RiskSkorlama::ucEksenliMi($rd->yontem);
        $f = $rd->yontem === 'fmea' ? 'D' : 'F';
        $s = fn ($v) => $v === null || $v === '' ? null : (float) $v;

        // Excel = PDF tablosu (kullanıcı 08.11.2026): aynı sütunlar, aynı sıra, aynı metinler.
        if ($excel) {
            $t = fn ($v) => $v === null || $v === '' ? '—' : (float) $v;

            return array_values(array_filter([
                ['No', fn ($m, $i) => $i, 5],
                ['Bölüm / Faaliyet', fn ($m) => trim($m->bolum.($m->faaliyet ? "\n".$m->faaliyet : '')), 16],
                ['Tehlike', fn ($m) => $m->tehlike, $uc ? 28 : 32],
                ['Risk', fn ($m) => $m->risk, $uc ? 24 : 28],
                ['O', fn ($m) => $t($m->olasilik), 5],
                $uc ? [$f, fn ($m) => $t($m->frekans), 5] : null,
                ['Ş', fn ($m) => $t($m->siddet), 5],
                ['Puan', fn ($m) => $t($m->puan), 7],
                ['Düzey', fn ($m) => $m->duzeyKisa(), 7],
                ['Öneri', fn ($m) => $m->oneriVeMevzuat() ?: '—', 42],
                ['Sorumlu', fn ($m) => $m->sorumluMetni(), 16],
                ['Termin', fn ($m) => $m->terminMetni($rd->yontem), 13],
                ['Son O', fn ($m) => $t($m->son_olasilik), 5],
                $uc ? ['Son '.$f, fn ($m) => $t($m->son_frekans), 5] : null,
                ['Son Ş', fn ($m) => $t($m->son_siddet), 5],
                ['Son Puan', fn ($m) => $m->son_puan ? (float) $m->son_puan : '—', 7],
                ['Son Düzey', fn ($m) => $m->son_duzey ? $m->sonDuzeyKisa() : '—', 7],
                ['Açıklama', fn ($m) => $m->aciklamaMetni(), 22],
            ]));
        }

        return array_values(array_filter([
            ['No', fn ($m, $i) => $i, 5],
            ['Bölüm', fn ($m) => $m->bolum, 18],
            ['Faaliyet', fn ($m) => $m->faaliyet, 18],
            ['Tehlike', fn ($m) => $m->tehlike, 30],
            ['Risk', fn ($m) => $m->risk, 24],
            ['O', fn ($m) => $s($m->olasilik), 6],
            $uc ? [$f, fn ($m) => $s($m->frekans), 6] : null,
            ['Ş', fn ($m) => $s($m->siddet), 6],
            ['Puan', fn ($m) => $s($m->puan), 8],
            ['Düzey', fn ($m) => $m->duzey, 14],
            ['Öneri', fn ($m) => $m->oneriVeMevzuat(), 40],
            ['Sorumlu', fn ($m) => $m->sorumluMetni(), 14],
            ['Termin', fn ($m) => $m->terminMetni(), 12],
            ['Son O', fn ($m) => $s($m->son_olasilik), 7],
            $uc ? ['Son '.$f, fn ($m) => $s($m->son_frekans), 7] : null,
            ['Son Ş', fn ($m) => $s($m->son_siddet), 7],
            ['Son Puan', fn ($m) => $s($m->son_puan), 8],
            ['Son Düzey', fn ($m) => $m->son_duzey, 14],
            ['Açıklama', fn ($m) => $m->aciklamaMetni(), 20],
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
            @set_time_limit(app()->runningInConsole() ? 0 : 300);
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

    /**
     * PDF ile aynı içerik ve sıra (kullanıcı 08.11.2026): 1. sekme Kapak, 2. sekme Prosedür (+ 1. İşyeri Künyesi,
     * 2. Değerlendirme Yöntemi), 3. sekme Risk Değerlendirme Tablosu (+ 4. Ekip, Onay). Sütunlar PDF'teki gibi
     * (Mevcut önlem yok, Bölüm / Faaliyet tek sütun, düzey renkli kısaltma); yazdırırken her sayfada üst bilgi
     * (firma / sicil / tarih), başlık satırı ve alt bilgide imza sahipleri + sayfa numarası.
     */
    public static function excelKitabi(RiskDegerlendirmesi $rd, bool $imzali = true): Spreadsheet
    {
        $v = RiskDegerlendirmesiUretici::veri($rd, $imzali);
        $kitap = new Spreadsheet;
        $kitap->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9);

        self::excelKapak($kitap->getActiveSheet()->setTitle('Kapak'), $v);
        self::excelProsedur($kitap->createSheet()->setTitle('Prosedür'), $v);
        self::excelRiskTablosu($kitap->createSheet()->setTitle('Risk Değerlendirmesi'), $v);

        foreach ($kitap->getAllSheets() as $sayfa) {
            self::excelUstAltBilgi($sayfa, $v);
        }
        $kitap->setActiveSheetIndex(0);

        return $kitap;
    }

    /** Excel'de boş / yok değer PDF'teki gibi "—". */
    private static function tire(mixed $deger): mixed
    {
        return $deger === null || $deger === '' ? '—' : $deger;
    }

    private static function cerceve(Worksheet $sayfa, string $aralik): void
    {
        $sayfa->getStyle($aralik)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }

    /** Birleşik hücrede uzun metin için satır yüksekliği (Excel birleşik hücrede otomatik yükseltmez). */
    private static function yukseklik(Worksheet $sayfa, int $satir, string $metin, int $satirBasinaKarakter, float $taban = 13.0): void
    {
        $satirSayisi = 0;
        foreach (preg_split('/\R/u', $metin) ?: [''] as $parca) {
            $satirSayisi += max(1, (int) ceil(mb_strlen($parca) / max(1, $satirBasinaKarakter)));
        }
        $sayfa->getRowDimension($satir)->setRowHeight(max($taban, $satirSayisi * 12.5));
    }

    private static function bolumBasligi(Worksheet $sayfa, int $satir, string $metin, string $son, int $boyut = 11): void
    {
        $sayfa->setCellValue("A{$satir}", $metin);
        $sayfa->mergeCells("A{$satir}:{$son}{$satir}");
        $sayfa->getStyle("A{$satir}")->getFont()->setBold(true)->setSize($boyut);
    }

    /** Her sayfanın üst bilgisi (firma / adres / sicil / tarih) + alt bilgisi (imza sahipleri + sayfa no) — PDF şeritleri. */
    private static function excelUstAltBilgi(Worksheet $sayfa, array $v): void
    {
        $rd = $v['rd'];
        $kac = fn (?string $s) => str_replace('&', '&&', (string) $s);
        $sol = Str::limit($kac(trim(($v['firma']?->unvan ?? $rd->firma_unvan).($rd->proje_adi ? ' - '.$rd->proje_adi : ''))), 110, '');
        $sag = 'SGK Sicil No: '.$kac($rd->firma_sgk_sicil_no ?: '—').' · Tarih: '.($rd->rapor_tarihi?->format('d.m.Y') ?? '');
        $sayfa->getHeaderFooter()->setOddHeader('&L&8&B'.$sol.'&R&8'.$sag);

        $nokta = '……………';
        $imzaci = collect([
            'İşveren / İşveren Vekili' => $v['isverenAdi'] ?? null,
            'İGU' => $v['iguAdi'] ?? null,
            'İşyeri Hekimi' => $v['hekimAdi'] ?? null,
            'Çalışan Temsilcisi' => $v['temsilci']['ad'] ?? null,
            'Destek Elemanı' => $v['destekElemani']['ad'] ?? null,
            'Bilgi Sahibi Personel' => $v['bilgiSahibiCalisan']['ad'] ?? null,
        ])->map(fn ($ad, $rol) => $rol.': '.$kac($ad ?: $nokta))->implode('  |  ');
        $sayfa->getHeaderFooter()->setOddFooter('&L&7'.Str::limit($imzaci, 200, '').'&R&8'.$kac($rd->belge_no).'  Sayfa &P / &N');
    }

    /** 1. sekme — PDF kapağı (Risk_Degerlendirmesi_Kapak şablonu). */
    private static function excelKapak(Worksheet $sayfa, array $v): void
    {
        $rd = $v['rd'];
        $firma = $v['firma'];
        $kapak = RiskDegerlendirmesiUretici::kapakBilgileri($rd);
        $bosTarih = '…… / …… / …………';
        foreach (['A' => 24, 'B' => 24, 'C' => 20, 'D' => 24] as $harf => $g) {
            $sayfa->getColumnDimension($harf)->setWidth($g);
        }

        $satir = 1;
        if ($firma?->logo && is_file($logo = storage_path('app/public/'.$firma->logo))) {
            $sayfa->getRowDimension(1)->setRowHeight(60);
            (new Drawing)->setPath($logo)->setHeight(70)->setCoordinates('B1')->setOffsetX(60)->setOffsetY(4)->setWorksheet($sayfa);
            $satir = 2;
        }
        foreach ([['İŞ SAĞLIĞI VE GÜVENLİĞİ', 12, 20], ['RİSK DEĞERLENDİRMESİ', 20, 30], [(string) ($rd->firma_unvan ?: $firma?->unvan), 13, 34]] as [$metin, $boyut, $yuk]) {
            $sayfa->setCellValue("A{$satir}", $metin);
            $sayfa->mergeCells("A{$satir}:D{$satir}");
            $sayfa->getStyle("A{$satir}")->applyFromArray([
                'font' => ['bold' => true, 'size' => $boyut],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sayfa->getRowDimension($satir)->setRowHeight($yuk);
            $satir++;
        }

        $baslik = function (string $metin) use ($sayfa, &$satir): void {
            $satir++;
            $sayfa->setCellValue("A{$satir}", $metin);
            $sayfa->mergeCells("A{$satir}:D{$satir}");
            $sayfa->getStyle("A{$satir}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::TABLO_BASLIK]],
            ]);
            $satir++;
        };
        $etiket = fn (string $aralik) => $sayfa->getStyle($aralik)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
        ]);

        // İŞYERİ BİLGİLERİ
        $baslik('İŞYERİ BİLGİLERİ');
        $ilk = $satir;
        foreach ([
            ['İşyeri / proje adı', $rd->proje_adi ?: ($rd->firma_unvan ?: 'Şantiye / Merkez İşyeri')],
            ['İşyeri adresi', (string) $rd->firma_adres],
            ['SGK işyeri sicil numarası', (string) $rd->firma_sgk_sicil_no],
            ['Faaliyet konusu / NACE kodu', $kapak['faaliyet']],
            ['Tehlike sınıfı / çalışan sayısı', $kapak['tehlike']],
            ['Kapsam / değerlendirilen bölümler', $kapak['kapsam']],
            ['Toplam sayfa sayısı', ''],
        ] as [$e, $d]) {
            $sayfa->setCellValue("A{$satir}", $e);
            $sayfa->setCellValueExplicit("B{$satir}", $d, DataType::TYPE_STRING);
            $sayfa->mergeCells("B{$satir}:D{$satir}");
            self::yukseklik($sayfa, $satir, $d, 62, 20);
            $satir++;
        }
        $etiket("A{$ilk}:A".($satir - 1));
        self::cerceve($sayfa, "A{$ilk}:D".($satir - 1));

        // DOKÜMAN BİLGİLERİ
        $baslik('DOKÜMAN BİLGİLERİ');
        $ilk = $satir;
        foreach ([
            ['Doküman numarası', $rd->belge_no ?: 'RD-……', 'Revizyon numarası', $rd->revizyon_no ?: '……'],
            ['Değerlendirme tarihi', $rd->rapor_tarihi?->format('d.m.Y') ?? $bosTarih, 'Revizyon tarihi', $bosTarih],
            ['Planlanan yenileme tarihi', $rd->gecerlilik_tarihi?->format('d.m.Y') ?? $bosTarih, 'Yöntem', $rd->yontemEtiketi()],
        ] as [$e1, $d1, $e2, $d2]) {
            $sayfa->setCellValue("A{$satir}", $e1);
            $sayfa->setCellValueExplicit("B{$satir}", (string) $d1, DataType::TYPE_STRING);
            $sayfa->setCellValue("C{$satir}", $e2);
            $sayfa->setCellValueExplicit("D{$satir}", (string) $d2, DataType::TYPE_STRING);
            $sayfa->getRowDimension($satir)->setRowHeight(20);
            $etiket("A{$satir}");
            $etiket("C{$satir}");
            $satir++;
        }
        self::cerceve($sayfa, "A{$ilk}:D".($satir - 1));

        // RİSK DEĞERLENDİRMESİ EKİBİ
        $baslik('RİSK DEĞERLENDİRMESİ EKİBİ');
        $ilk = $satir;
        $sayfa->fromArray(['Görevi', 'Adı soyadı', 'İmza'], null, "A{$satir}");
        $sayfa->mergeCells("C{$satir}:D{$satir}");
        $etiket("A{$satir}:D{$satir}");
        $nokta = '........................................';
        foreach ([
            ['İşveren / işveren vekili', $v['isverenAdi'] ?? ''],
            ['İş Güvenliği Uzmanı', $v['iguAdi'] ?? ''],
            ['İşyeri Hekimi', $v['hekimAdi'] ?? ''],
            ['Çalışan Temsilcisi', ($v['temsilci']['ad'] ?? null) ?: $nokta],
            ['Destek Elemanı', ($v['destekElemani']['ad'] ?? null) ?: $nokta],
            ['Bilgi Sahibi Personel', ($v['bilgiSahibiCalisan']['ad'] ?? null) ?: $nokta],
        ] as [$gorev, $ad]) {
            $satir++;
            $sayfa->setCellValue("A{$satir}", $gorev);
            $sayfa->setCellValue("B{$satir}", (string) $ad);
            $sayfa->mergeCells("C{$satir}:D{$satir}");
            $sayfa->getRowDimension($satir)->setRowHeight(30);
        }
        self::cerceve($sayfa, "A{$ilk}:D{$satir}");

        $sayfa->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(1);
        $sayfa->getPageMargins()->setLeft(0.5)->setRight(0.5)->setTop(0.6)->setBottom(0.6);
    }

    /** 2. sekme — prosedür metni + 1. İşyeri Künyesi + 2. Değerlendirme Yöntemi (PDF'in dikey bölümü). */
    private static function excelProsedur(Worksheet $sayfa, array $v): void
    {
        $rd = $v['rd'];
        foreach (['A' => 22, 'B' => 12, 'C' => 14, 'D' => 72] as $harf => $g) {
            $sayfa->getColumnDimension($harf)->setWidth($g);
        }
        $satir = 1;

        if ($prosedur = $v['prosedur']) {
            self::bolumBasligi($sayfa, $satir, (string) $prosedur->ad, 'D', 12);
            $satir += 2;
            foreach ((array) $prosedur->icerik as $blok) {
                $metin = (string) ($blok['metin'] ?? '');
                $sayfa->setCellValue("A{$satir}", $metin);
                $sayfa->mergeCells("A{$satir}:D{$satir}");
                $sayfa->getStyle("A{$satir}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)
                    ->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                if (($blok['tip'] ?? '') === 'baslik') {
                    $sayfa->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(10);
                    $sayfa->getRowDimension($satir)->setRowHeight(18);
                } else {
                    self::yukseklik($sayfa, $satir, $metin, 130);
                }
                $satir++;
            }
            $satir++;
        }

        // 1. İŞYERİ KÜNYESİ
        self::bolumBasligi($sayfa, $satir, '1. İŞYERİ KÜNYESİ', 'D', 12);
        $satir++;
        $ilk = $satir;
        foreach ([
            ['Ticari Unvan', (string) $rd->firma_unvan],
            ['SGK Sicil No', $rd->firma_sgk_sicil_no ?: '—'],
            ['NACE Kodu', $rd->firma_nace ?: '—'],
            ['Tehlike Sınıfı', (string) $v['firma']?->tehlikeSinifiEtiketi()],
            ['Adres', $rd->firma_adres ?: '—'],
        ] as [$e, $d]) {
            $sayfa->setCellValue("A{$satir}", $e);
            $sayfa->setCellValueExplicit("B{$satir}", $d, DataType::TYPE_STRING);
            $sayfa->mergeCells("B{$satir}:D{$satir}");
            $sayfa->getStyle("A{$satir}")->getFont()->setBold(true);
            self::yukseklik($sayfa, $satir, $d, 100, 16);
            $satir++;
        }
        self::cerceve($sayfa, "A{$ilk}:D".($satir - 1));

        // 2. DEĞERLENDİRME YÖNTEMİ — HAZOP'ta puanlama ölçeği yok (PDF'teki gibi).
        if ($rd->yontem !== 'hazop') {
            $satir++;
            self::excelYontem($sayfa, $v, $satir);
        }

        $sayfa->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sayfa->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.6)->setBottom(0.6);
    }

    private static function excelYontem(Worksheet $sayfa, array $v, int $satir): void
    {
        $met = $v['metodoloji'];
        self::bolumBasligi($sayfa, $satir, '2. DEĞERLENDİRME YÖNTEMİ', 'D', 12);
        $satir++;
        $aciklama = (string) ($met['aciklama'] ?? '');
        $sayfa->setCellValue("A{$satir}", $aciklama);
        $sayfa->mergeCells("A{$satir}:D{$satir}");
        $sayfa->getStyle("A{$satir}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        self::yukseklik($sayfa, $satir, $aciklama, 130);
        $satir += 2;

        $olcek = function (string $baslik, array $degerler) use ($sayfa, &$satir): void {
            $ilk = $satir;
            $sayfa->setCellValue("A{$satir}", $baslik);
            $sayfa->setCellValue("B{$satir}", 'Açıklama');
            $sayfa->mergeCells("B{$satir}:D{$satir}");
            $sayfa->getStyle("A{$satir}:D{$satir}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
            ]);
            foreach ($degerler as $puan => $aciklama) {
                $satir++;
                $sayfa->setCellValueExplicit("A{$satir}", str_replace('.', ',', (string) $puan), DataType::TYPE_STRING);
                $sayfa->setCellValue("B{$satir}", $aciklama);
                $sayfa->mergeCells("B{$satir}:D{$satir}");
                self::yukseklik($sayfa, $satir, (string) $aciklama, 105);
            }
            self::cerceve($sayfa, "A{$ilk}:D{$satir}");
            $satir += 2;
        };

        $etiket = $v['eksenEtiketleri'];
        $olcek('Olasılık', $met['olasilik'] ?? []);
        if (isset($met['frekans'])) {
            $olcek($etiket['frekans'] ?? 'Frekans', $met['frekans']);
        }
        $olcek('Şiddet', $met['siddet'] ?? []);

        $ilk = $satir;
        $sayfa->fromArray(['Düzey', 'Kısaltma', 'Puan aralığı', 'Yapılacak eylem'], null, "A{$satir}");
        $sayfa->getStyle("A{$satir}:D{$satir}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
        ]);
        foreach (self::bantlar($v) as $bant) {
            $satir++;
            $sayfa->fromArray([$bant['ad'], $bant['kisa'], '≥ '.str_replace('.', ',', (string) $bant['min']), $bant['eylem']], null, "A{$satir}");
            $sayfa->getStyle("A{$satir}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::hex($bant['renk']));
            $sayfa->getStyle("B{$satir}")->getFont()->setBold(true);
            $sayfa->getStyle("B{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            self::yukseklik($sayfa, $satir, (string) $bant['eylem'], 75);
        }
        self::cerceve($sayfa, "A{$ilk}:D{$satir}");
    }

    /** @return array<int, array<string, mixed>> PDF'teki bant listesi (kısaltmalı). */
    private static function bantlar(array $v): array
    {
        $yontem = $v['rd']->yontem;

        return collect($v['metodoloji']['bantlar'] ?? [])
            ->map(fn ($b) => $b + ['kisa' => RiskSkorlama::kisaltma($yontem, $b['ad'])])->all();
    }

    /** 3. sekme — 3. Risk Değerlendirme Tablosu + 4. Ekip + Onay (PDF'in yatay bölümü). */
    private static function excelRiskTablosu(Worksheet $sayfa, array $v): void
    {
        $rd = $v['rd'];
        $sutunlar = self::sutunlar($rd, true);
        $n = count($sutunlar);
        $son = Coordinate::stringFromColumnIndex($n);

        self::bolumBasligi($sayfa, 1, '3. TEHLİKELERİN TANIMLANMASI VE RİSK DEĞERLENDİRME TABLOSU', $son, 12);
        $satir = 2;
        if ($rd->yontem !== 'hazop') {
            $lejant = new RichText;
            $lejant->createTextRun('Düzey kısaltmaları:  ')->getFont()->setBold(true)->setSize(8);
            foreach (self::bantlar($v) as $bant) {
                $kisa = $lejant->createTextRun($bant['kisa'].' ');
                $kisa->getFont()->setBold(true)->setSize(8)->getColor()->setRGB(self::hex($bant['renk']));
                $lejant->createTextRun($bant['ad'].'    ')->getFont()->setSize(8);
            }
            $sayfa->setCellValue("A{$satir}", $lejant);
            $sayfa->mergeCells("A{$satir}:{$son}{$satir}");
            $satir++;
        }

        $baslikSatiri = ++$satir;
        foreach ($sutunlar as $i => [$baslik, , $genislik]) {
            $harf = Coordinate::stringFromColumnIndex($i + 1);
            $sayfa->setCellValue($harf.$satir, $baslik);
            $sayfa->getColumnDimension($harf)->setWidth($genislik);
        }
        $sayfa->getStyle("A{$satir}:{$son}{$satir}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::TABLO_BASLIK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $duzeyKolonlari = collect($sutunlar)->keys()->filter(fn ($i) => in_array($sutunlar[$i][0], ['Düzey', 'Son Düzey'], true));
        foreach ($rd->maddeler as $no => $m) {
            $satir++;
            foreach ($sutunlar as $i => [, $deger]) {
                $sayfa->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$satir, $deger($m, $no + 1));
            }
            foreach ($duzeyKolonlari as $i) {
                $puan = $sutunlar[$i][0] === 'Düzey' ? $m->puan : $m->son_puan;
                if ($puan) {
                    $sayfa->getStyle(Coordinate::stringFromColumnIndex($i + 1).$satir)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::hex(RiskSkorlama::bant($rd->yontem, (float) $puan)['renk'])]],
                        'font' => ['bold' => true],
                    ]);
                }
            }
        }
        $sayfa->getStyle("A{$baslikSatiri}:{$son}{$satir}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sayfa->setAutoFilter("A{$baslikSatiri}:{$son}{$satir}");
        $sayfa->freezePane('E'.($baslikSatiri + 1));

        self::excelEkipVeOnay($sayfa, $v, $satir + 2, $n);

        $sayfa->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd($baslikSatiri, $baslikSatiri);
        $sayfa->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.6)->setBottom(0.6)->setHeader(0.2)->setFooter(0.2);
    }

    /** 4. Risk Değerlendirme Ekibi + Onay (kaşe / imza görselleriyle) — PDF'in son sayfası. */
    private static function excelEkipVeOnay(Worksheet $sayfa, array $v, int $satir, int $sutunSayisi): void
    {
        $son = Coordinate::stringFromColumnIndex(max(6, $sutunSayisi));
        $ortaSon = Coordinate::stringFromColumnIndex(max(6, $sutunSayisi) >= 12 ? 8 : 4);

        self::bolumBasligi($sayfa, $satir, '4. RİSK DEĞERLENDİRME EKİBİ', $son);
        $satir++;
        $ilk = $satir;
        $sayfa->setCellValue("A{$satir}", 'Ad Soyad');
        $sayfa->setCellValue("E{$satir}", 'Unvan / Görev');
        $sayfa->mergeCells("A{$satir}:D{$satir}");
        $sayfa->mergeCells("E{$satir}:{$ortaSon}{$satir}");
        $sayfa->getStyle("A{$satir}:{$ortaSon}{$satir}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BASLIK_ARKA]],
        ]);
        foreach ($v['ekipGosterim'] ?? [] as $uye) {
            $satir++;
            $ad = filled($uye['ad']) ? $uye['ad'] : (filled($uye['unvan']) ? '........................................' : '');
            $sayfa->setCellValue("A{$satir}", $ad);
            $sayfa->setCellValue("E{$satir}", $uye['unvan']);
            $sayfa->mergeCells("A{$satir}:D{$satir}");
            $sayfa->mergeCells("E{$satir}:{$ortaSon}{$satir}");
            $sayfa->getRowDimension($satir)->setRowHeight(18);
        }
        self::cerceve($sayfa, "A{$ilk}:{$ortaSon}{$satir}");
        $satir++;
        $not = '6331 SK m.6 ve Risk Değerlendirmesi Yönetmeliği uyarınca risk değerlendirmesi işveren, iş güvenliği uzmanı, '
            .'işyeri hekimi, çalışan temsilcisi ve destek elemanlarından oluşan bir ekip tarafından gerçekleştirilir.';
        $sayfa->setCellValue("A{$satir}", $not);
        $sayfa->mergeCells("A{$satir}:{$son}{$satir}");
        $sayfa->getStyle("A{$satir}")->getFont()->setSize(8)->getColor()->setRGB('666666');
        $sayfa->getStyle("A{$satir}")->getAlignment()->setWrapText(true);
        $sayfa->getRowDimension($satir)->setRowHeight(24);

        // ONAY — 2 satır × 3 kutu; kutu = sütunların üçte biri.
        $satir += 2;
        self::bolumBasligi($sayfa, $satir, 'ONAY', $son);
        $toplam = max(6, $sutunSayisi);
        $genislik = intdiv($toplam, 3);
        $onaylar = self::onaylar($v);
        foreach (array_chunk($onaylar, 3) as $sira) {
            $satir++;
            $ilk = $satir;
            foreach ($sira as $k => [$rol, $ad, $gorseller]) {
                $bas = Coordinate::stringFromColumnIndex($k * $genislik + 1);
                $bit = Coordinate::stringFromColumnIndex($k === 2 ? $toplam : ($k + 1) * $genislik);
                $sayfa->setCellValue("{$bas}{$satir}", mb_strtoupper($rol, 'UTF-8')."\n\n\n\n".($ad !== '' ? $ad : '........................................'));
                $sayfa->mergeCells("{$bas}{$satir}:{$bit}{$satir}");
                $sayfa->getStyle("{$bas}{$satir}")->getAlignment()->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                $x = 10;
                foreach ($gorseller as $yol) {
                    if (! is_file($tam = storage_path('app/public/'.$yol))) {
                        continue;
                    }
                    (new Drawing)->setPath($tam)->setHeight(40)->setCoordinates("{$bas}{$satir}")->setOffsetX($x)->setOffsetY(16)->setWorksheet($sayfa);
                    $x += 90;
                }
            }
            $sayfa->getRowDimension($satir)->setRowHeight(78);
            self::cerceve($sayfa, "A{$ilk}:".Coordinate::stringFromColumnIndex($toplam).$satir);
            $sayfa->getStyle("A{$ilk}:".Coordinate::stringFromColumnIndex($toplam).$satir)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }
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

        // PDF ile aynı düzen: künye + yöntem dikey, risk tablosu + ekip / onay yatay (yeni bölüm).
        $altBilgi = fn (Section $b) => $b->addFooter()->addPreserveText($rd->belge_no.'     Sayfa {PAGE} / {NUMPAGES}', ['size' => 7, 'color' => '666666'], ['alignment' => Jc::END]);
        $bolum = $phpWord->addSection(['marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 500, 'marginRight' => 500]);
        $altBilgi($bolum);

        $bolum->addText('RİSK DEĞERLENDİRMESİ', ['bold' => true, 'size' => 15], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);

        // 1. Künye
        $bolum->addText('1. İŞYERİ KÜNYESİ', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $tablo = self::wordTablo($bolum);
        foreach (self::kunye($rd) as [$etiket, $deger]) {
            $tablo->addRow();
            $tablo->addCell(3000, ['bgColor' => self::BASLIK_ARKA])->addText($etiket, ['bold' => true]);
            $tablo->addCell(7900)->addText(self::metin($deger));
        }

        // 2. Yöntem (HAZOP'ta puanlama ölçeği yok)
        if ($rd->yontem !== 'hazop') {
            $bolum->addTextBreak();
            self::wordYontem($bolum, $v);
        }

        // 3. Risk tablosu
        $bolum = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 600, 'marginBottom' => 600, 'marginLeft' => 500, 'marginRight' => 500,
        ]);
        $altBilgi($bolum);
        $bolum->addText('3. TEHLİKELERİN TANIMLANMASI VE RİSK DEĞERLENDİRME TABLOSU', ['bold' => true, 'size' => 11], ['spaceAfter' => 60]);
        $sutunlar = self::sutunlar($rd, false);
        $genislik = collect($sutunlar)->map(fn ($s) => $s[2])->all();
        $toplam = array_sum($genislik);
        $twip = fn (int $i) => (int) round($genislik[$i] / $toplam * 15800);

        $tablo = self::wordTablo($bolum);
        $tablo->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($sutunlar as $i => [$baslik]) {
            $tablo->addCell($twip($i), ['bgColor' => self::TABLO_BASLIK, 'valign' => 'center'])
                ->addText($baslik, ['bold' => true, 'size' => 7, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        }

        foreach ($rd->maddeler as $n => $m) {
            $tablo->addRow(null, ['cantSplit' => true]);
            foreach ($sutunlar as $i => [$baslik, $deger]) {
                $stil = ['valign' => 'center'];
                $yazi = ['size' => 7];
                if (in_array($baslik, ['Düzey', 'Son Düzey'], true)) {
                    $puan = $baslik === 'Düzey' ? $m->puan : $m->son_puan;
                    if ($puan) {
                        $stil['bgColor'] = self::hex(RiskSkorlama::bant($rd->yontem, (float) $puan)['renk']);
                        $yazi += ['bold' => true, 'color' => 'FFFFFF'];
                    }
                }
                $tablo->addCell($twip($i), $stil)->addText(self::metin($deger($m, $n + 1)), $yazi, ['alignment' => Jc::CENTER]);
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
            $tablo->addCell(2000, ['bgColor' => self::BASLIK_ARKA])->addText($baslik, ['bold' => true]);
            $tablo->addCell(8900, ['bgColor' => self::BASLIK_ARKA])->addText('Açıklama', ['bold' => true]);
            foreach ($degerler as $puan => $aciklama) {
                $tablo->addRow();
                $tablo->addCell(2000)->addText(str_replace('.', ',', (string) $puan), [], ['alignment' => Jc::CENTER]);
                $tablo->addCell(8900)->addText(self::metin($aciklama));
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
        foreach (['Düzey' => 2600, 'Kısaltma' => 1100, 'Puan aralığı' => 1500, 'Yapılacak eylem' => 5700] as $b => $g) {
            $tablo->addCell($g, ['bgColor' => self::BASLIK_ARKA])->addText($b, ['bold' => true]);
        }
        foreach ($met['bantlar'] ?? [] as $bant) {
            $tablo->addRow();
            $tablo->addCell(2600, ['bgColor' => self::hex($bant['renk'])])->addText($bant['ad'], ['bold' => true, 'color' => 'FFFFFF']);
            $tablo->addCell(1100)->addText((string) ($bant['kisa'] ?? ''), ['bold' => true], ['alignment' => Jc::CENTER]);
            $tablo->addCell(1500)->addText('≥ '.str_replace('.', ',', (string) $bant['min']));
            $tablo->addCell(5700)->addText(self::metin($bant['eylem']));
        }
    }

    private static function wordTablo(Section $bolum): Table
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
