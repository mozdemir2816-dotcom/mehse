<?php

namespace App\Support;

use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Yıllık Değerlendirme Raporu (İSG-YDR-01).
 *
 * 13.11.2026: 7 dikey sayfa (her sayfada künye + imza tekrarı, sayfa başına 6 satır)
 * → A4 YATAY, en fazla 4 sayfa. Künye yalnız ilk sayfada; tablo başlığı her sayfada
 * tekrar eder; sayfa altında belge no, paraf satırı ve "Sayfa X / Y"; genel sonuç ve
 * imza alanı sonda. Metinler, sütunlar, renkler ve "Doldurma" kuralı kullanıcının
 * şablonundan (resources/belge/yillik-degerlendirme.xlsx) okunur — şablon değişirse
 * rapor da değişir. Kayıtta karşılığı olmayan satır şablondaki taslak metniyle kalır.
 * Görevli ad soyadı yazılmaz (imza alanında kaşe kullanılır).
 */
class YillikDegerlendirmeExcelUretici
{
    private const SABLON = 'belge/yillik-degerlendirme.xlsx';

    /** Hedef: yatay A4'te 4 sayfa (36 satırlık plan + genel sonuç). */
    public const SAYFA = 4;

    /**
     * Bir sayfaya sığan satır yüksekliği toplamı (pt, ölçekten önce): yatay A4 yazdırılabilir
     * yükseklik ~530 pt; sütunlar genişliğe sığdırılınca ~%94 ölçek → ~560 pt; pay bırakıldı.
     */
    private const SAYFA_YUKSEKLIGI = 530;

    private const LACIVERT = '17365D';

    private const KUNYE = 'FFF2CC';

    private const ETIKET = 'E9EFF7';

    private const METIN = 'FFF9E6';

    /** Sütun genişlikleri (A No · B Tarih · C Yapılan çalışma · D Yapan · E Yöntem / kanıt · F Sonuç). */
    private const GENISLIK = ['A' => 5, 'B' => 12, 'C' => 24, 'D' => 20, 'E' => 24, 'F' => 72];

    public static function excel(YillikPlan $plan, ?Carbon $raporTarihi = null): StreamedResponse
    {
        $kitap = static::doldur($plan, $raporTarihi);
        $tmp = tempnam(sys_get_temp_dir(), 'ydr').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, static::dosyaAdi($plan));
    }

    public static function dosyaAdi(YillikPlan $plan): string
    {
        return 'yillik-degerlendirme-'.$plan->yil.'-'.Str::slug($plan->firma->unvan).'.xlsx';
    }

    /** İSG-YDR-01 çalışma kitabı (indirmeden; toplu ZIP de kullanır). */
    public static function doldur(YillikPlan $plan, ?Carbon $raporTarihi = null): Spreadsheet
    {
        ExcelBellek::artir();

        $firma = $plan->firma;
        $kunye = YillikDegerlendirmeVerisi::kunye($firma, $plan->yil);
        $raporTarihi ??= Carbon::today();
        $sablon = static::sablon();

        $satirlar = collect($plan->degerlendirmeler ?? []);
        $calisma = $satirlar->where('tur', '!=', 'genel')->values();
        $genel = $satirlar->where('tur', 'genel')->values();
        $bos = fn ($v) => filled($v) ? $v : '…';

        $kitap = new Spreadsheet;
        $kitap->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $kitap->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $s = $kitap->getActiveSheet()->setTitle('Yıllık Değerlendirme');
        foreach (self::GENISLIK as $c => $w) {
            $s->getColumnDimension($c)->setWidth($w);
        }

        // 1. Başlık
        $s->setCellValue('A1', $sablon['baslik']);
        $s->mergeCells('A1:F1');
        $s->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB(self::LACIVERT);
        $s->getRowDimension(1)->setRowHeight(26);

        // 2–5. Künye (yalnız ilk sayfada): sol A:C, sağ D:F
        $kunyeSatirlari = [
            ['İşyeri unvanı: '.$bos($firma->unvan), 'Rapor yılı: '.$plan->yil.' | Dönem: '.$kunye['donem']],
            ['SGK sicil no: '.$bos($firma->sgk_sicil_no), 'NACE / Faaliyet: '.$bos($kunye['nace'])],
            ['Adres: '.$bos($firma->adres), 'Tehlike sınıfı: '.$firma->tehlikeSinifiEtiketi().'  |  Telefon / E-posta: '.$bos(collect([$firma->telefon, $firma->eposta])->filter()->implode(' / '))],
            ['Çalışan: Erkek '.$kunye['erkek'].' / Kadın '.$kunye['kadin'].' / Toplam '.$kunye['toplam'].'  |  Genç '.$kunye['genc'].' / Çocuk '.$kunye['cocuk'],
                'Rapor tarihi: '.$raporTarihi->format('d.m.Y').'  |  '.$sablon['dokuman']],
        ];
        $r = 2;
        foreach ($kunyeSatirlari as [$sol, $sag]) {
            $s->setCellValue('A'.$r, $sol)->setCellValue('D'.$r, $sag);
            $s->mergeCells('A'.$r.':C'.$r);
            $s->mergeCells('D'.$r.':F'.$r);
            $s->getRowDimension($r)->setRowHeight(max(18, static::yukseklik([[$sol, 41], [$sag, 116]])));
            $r++;
        }
        static::kutu($s, 'A2:F'.($r - 1), self::KUNYE);

        // Sayfalama elle yapılır: tablo devam eden sayfaya başlık satırı eklenir; genel sonuç
        // + imza bloğu bölünmeden aynı sayfada kalır (Excel'in "üstte yinelenecek satırlar"ı
        // tablo olmayan son sayfaya da başlık basıyordu).
        $kullanilan = 26 + array_sum(array_map(fn ($i) => (float) $s->getRowDimension($i)->getRowHeight(), range(2, $r - 1)));
        $tabloBasligi = function () use ($s, $sablon, &$r, &$kullanilan) {
            $s->fromArray($sablon['kolonlar'], null, 'A'.$r);
            static::kutu($s, 'A'.$r.':F'.$r, self::LACIVERT);
            $s->getStyle('A'.$r.':F'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $s->getStyle('A'.$r.':F'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getRowDimension($r)->setRowHeight(28);
            $kullanilan += 28;
            $r++;
        };
        $yeniSayfa = function () use ($s, &$r, &$kullanilan) {
            $s->setBreak('A'.($r - 1), Worksheet::BREAK_ROW);
            $kullanilan = 0;
        };

        // 6. Tablo başlığı
        $tabloBasligi();

        // Çalışma satırları: kayıt varsa kayıttan, yoksa şablondaki taslak metinle
        $adet = max(count($sablon['satirlar']), $calisma->count());
        $ilk = $r;
        for ($i = 0; $i < $adet; $i++) {
            $d = $calisma[$i] ?? null;
            $t = $sablon['satirlar'][$i + 1] ?? ['… / … / …', '', '', '', ''];
            $deger = [
                $i + 1,
                $d ? (filled($d['tarih'] ?? null) ? static::tarih($d['tarih']) : '… / … / …') : $t[0],
                $d['calisma'] ?? $t[1],
                $d['yapan_kisi'] ?? $t[2],
                $d['yontem'] ?? $t[3],
                $d['sonuc'] ?? $t[4],
            ];
            $h = static::yukseklik([[$deger[1], 12], [$deger[2], 24], [$deger[3], 20], [$deger[4], 24], [$deger[5], 72]]);
            if ($kullanilan + $h > self::SAYFA_YUKSEKLIGI) {
                $yeniSayfa();
                $tabloBasligi();
            }
            $kullanilan += $h;
            $s->fromArray($deger, null, 'A'.$r);
            $s->getCell('B'.$r)->setValueExplicit((string) $deger[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $s->getRowDimension($r)->setRowHeight($h);
            if ($i % 2 === 1) {
                $s->getStyle('A'.$r.':F'.$r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F9FC');
            }
            $r++;
        }
        $s->getStyle('A'.$ilk.':F'.($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B7C3D3');
        $s->getStyle('A'.$ilk.':A'.($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle('B'.$ilk.':B'.($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Doldurma notu
        if ($sablon['not']) {
            $s->setCellValue('A'.$r, $sablon['not']);
            $s->mergeCells('A'.$r.':F'.$r);
            $s->getStyle('A'.$r)->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('475569');
            $s->getRowDimension($r)->setRowHeight(16);
            $kullanilan += 16;
            $r++;
        }

        // Genel sonuç + notlar + imza tek blok: sığmazsa yeni sayfada başlar.
        $genelMetin = fn (string $baslik, string $taslak) => ($g = $genel->firstWhere('calisma', $baslik)) && filled($g['sonuc'] ?? null) ? $g['sonuc'] : $taslak;
        $blok = 12 + 20 + 12 + 18 + 62
            + array_sum(array_map(fn ($b, $t) => max(20, static::yukseklik([[$genelMetin($b, $t), 116]])), array_keys($sablon['genel']), $sablon['genel']))
            + array_sum(array_map(fn ($n) => max(16, static::yukseklik([[$n, 157]])), $sablon['sonNotlar']));
        if ($kullanilan + $blok > self::SAYFA_YUKSEKLIGI) {
            $r++;
            $yeniSayfa();
        } else {
            $s->getRowDimension($r)->setRowHeight(12);
            $r++;
        }

        // Genel sonuç bölümü
        $s->setCellValue('A'.$r, $sablon['genelBaslik']);
        $s->mergeCells('A'.$r.':F'.$r);
        static::kutu($s, 'A'.$r.':F'.$r, self::LACIVERT);
        $s->getStyle('A'.$r)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s->getRowDimension($r)->setRowHeight(20);
        $r++;
        foreach ($sablon['genel'] as $baslik => $taslak) {
            $metin = $genelMetin($baslik, $taslak);
            $s->setCellValue('A'.$r, $baslik)->setCellValue('D'.$r, $metin);
            $s->mergeCells('A'.$r.':C'.$r);
            $s->mergeCells('D'.$r.':F'.$r);
            static::kutu($s, 'A'.$r.':C'.$r, self::ETIKET);
            static::kutu($s, 'D'.$r.':F'.$r, self::METIN);
            $s->getStyle('A'.$r)->getFont()->setBold(true);
            $s->getRowDimension($r)->setRowHeight(max(20, static::yukseklik([[$metin, 116]])));
            $r++;
        }

        foreach ($sablon['sonNotlar'] as $not) {
            $s->setCellValue('A'.$r, $not);
            $s->mergeCells('A'.$r.':F'.$r);
            $s->getStyle('A'.$r)->getFont()->setSize(8)->getColor()->setRGB('334155');
            $s->getRowDimension($r)->setRowHeight(max(16, static::yukseklik([[$not, 157]])));
            $r++;
        }

        // İmza alanı (kaşe için yüksek): İşveren A:C · İGU D:E · İşyeri hekimi F
        $s->getRowDimension($r)->setRowHeight(12);
        $r++;
        $imza = $sablon['imza'];
        foreach ([['A', 'C', $imza[0]], ['D', 'E', $imza[1]], ['F', 'F', $imza[2]]] as [$b, $e, $etiket]) {
            $s->setCellValue($b.$r, $etiket);
            $s->setCellValue($b.($r + 1), 'Tarih / Kaşe / İmza:');
            if ($b !== $e) {
                $s->mergeCells($b.$r.':'.$e.$r);
                $s->mergeCells($b.($r + 1).':'.$e.($r + 1));
            }
            $s->getStyle($b.$r)->getFont()->setBold(true);
            $s->getStyle($b.($r + 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $s->getStyle($b.$r.':'.$e.($r + 1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        }
        $s->getRowDimension($r)->setRowHeight(18);
        $s->getRowDimension($r + 1)->setRowHeight(62);
        $son = $r + 1;

        static::yazdirmaAyari($s, $son, $sablon['dokuman']);

        return $kitap;
    }

    /**
     * Şablondaki sabit metinler: başlık, sütunlar, 1..N çalışma satırının taslakları,
     * "Doldurma" notu, genel sonuç başlıkları + taslakları, son notlar, imza etiketleri.
     *
     * @return array{baslik: string, dokuman: string, kolonlar: array<int, string>, satirlar: array<int, array<int, string>>, not: ?string, genelBaslik: string, genel: array<string, string>, sonNotlar: array<int, string>, imza: array<int, string>}
     */
    private static function sablon(): array
    {
        $s = IOFactory::load(resource_path(self::SABLON))->getActiveSheet();
        $deger = fn (string $h) => trim((string) $s->getCell($h)->getCalculatedValue());

        $sonuc = ['baslik' => '', 'dokuman' => 'Doküman no: İSG-YDR-01  |  Revizyon: 00', 'kolonlar' => [], 'satirlar' => [], 'not' => null,
            'genelBaslik' => 'GENEL SONUÇ, İYİLEŞTİRME KARARLARI VE GELECEK YIL ÖNERİLERİ', 'genel' => [], 'sonNotlar' => [],
            'imza' => ['İşveren / İşveren Vekili', 'İş Güvenliği Uzmanı', 'İşyeri Hekimi']];
        $genelde = false;

        for ($r = 1; $r <= $s->getHighestRow(); $r++) {
            $a = $deger('A'.$r);

            if ($a === '') {
                continue;
            }
            if ($sonuc['baslik'] === '' && str_starts_with($a, 'İŞ SAĞLIĞI VE GÜVENLİĞİ')) {
                $sonuc['baslik'] = $a;
            } elseif (str_starts_with($a, 'Doküman no:')) {
                $sonuc['dokuman'] = $a;
            } elseif ($a === 'No' && ! $sonuc['kolonlar']) {
                $sonuc['kolonlar'] = array_map(fn ($c) => $deger($c.$r), ['A', 'B', 'C', 'D', 'E', 'F']);
            } elseif (ctype_digit($a)) {
                $sonuc['satirlar'][(int) $a] ??= array_map(fn ($c) => $deger($c.$r), ['B', 'C', 'D', 'E', 'F']);
            } elseif (str_starts_with($a, 'Doldurma:')) {
                $sonuc['not'] ??= $a;
            } elseif (str_starts_with($a, 'GENEL SONUÇ')) {
                $sonuc['genelBaslik'] = $a;
                $genelde = true;
            } elseif ($genelde && str_starts_with($a, 'İşveren')) {
                $sonuc['imza'] = [$a, $deger('D'.$r) ?: 'İş Güvenliği Uzmanı', $deger('F'.$r) ?: 'İşyeri Hekimi'];
                $genelde = false;
            } elseif ($genelde && str_starts_with($a, 'Ad soyad')) {
                continue;
            } elseif ($genelde && $deger('D'.$r) !== '' && $deger('D'.$r) !== $a) {
                $sonuc['genel'][$a] = $deger('D'.$r);
            } elseif ($genelde && $a === $deger('F'.$r)) {
                $sonuc['sonNotlar'][] = $a;
            }
        }

        return $sonuc;
    }

    /** Dolgu + ince kenarlık. */
    private static function kutu(Worksheet $s, string $aralik, string $renk): void
    {
        $s->getStyle($aralik)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($renk);
        $s->getStyle($aralik)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B7C3D3');
    }

    /**
     * [metin, sütun genişliği] çiftlerinden gereken satır yüksekliği (Arial 9: genişlik
     * biriminde ~1,15 karakter; satır ~11,5 pt).
     *
     * @param  array<int, array{0: mixed, 1: int}>  $metinler
     */
    private static function yukseklik(array $metinler): float
    {
        $satir = 1;
        foreach ($metinler as [$metin, $genislik]) {
            $sayi = 0;
            foreach (preg_split('/\R/u', (string) $metin) as $parca) {
                $sayi += max(1, (int) ceil(mb_strlen($parca) / max(1, $genislik * 1.15)));
            }
            $satir = max($satir, $sayi);
        }

        return $satir * 11.5 + 7;
    }

    /** "2026-05-03" → "03.05.2026"; dönem metni olduğu gibi. */
    private static function tarih(string $t): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) ? Carbon::parse($t)->format('d.m.Y') : str_replace('–', "–\n", $t);
    }

    private static function yazdirmaAyari(Worksheet $s, int $son, string $dokuman): void
    {
        $s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0)
            ->setPrintArea('A1:F'.$son)
            ->setHorizontalCentered(true);
        $s->getPageMargins()->setTop(0.35)->setBottom(0.55)->setLeft(0.3)->setRight(0.3)->setHeader(0.2)->setFooter(0.25);
        $dokumanKisa = preg_replace('/\s+\|\s+/', ' · ', $dokuman);
        $s->getHeaderFooter()->setOddFooter('&L&7'.$dokumanKisa.'&C&7Paraf — İşveren: ________   İGU: ________   İşyeri Hekimi: ________&R&7Sayfa &P / &N');
        $s->setShowGridlines(false);
    }
}
