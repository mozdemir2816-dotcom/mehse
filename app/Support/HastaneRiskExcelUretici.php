<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hastane risk formunu (Bursa Şehir Hastanesi) kaynak Excel dosyasını kalıp alarak üretir:
 * künye bandı, logo, sütun genişlikleri, stiller ve alt bilgi (özet + ekip bloğu) aynen
 * korunur; yalnız veri satırları sistemdeki maddelerden yeniden yazılır.
 *
 * Tek düzeltme: sayfa A4 yatay, genişliğe sığdırılır (yüksekliği otomatik) ve her satır tek
 * sayfaya sığar — Excel'in 409,5 pt satır sınırını aşacak uzunlukta metinlerde o satırın
 * yazı boyutu küçültülür.
 */
class HastaneRiskExcelUretici
{
    private const SUTUNLAR = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];

    /** Metin taşıyan (yüksekliği belirleyen) sütunlar. */
    private const METIN_SUTUNLARI = ['B' => 'tehlike', 'C' => 'risk', 'D' => 'etki_alani', 'E' => 'mevcut_onlem', 'K' => 'oneri', 'Q' => 'sorumlu', 'R' => 'termin', 'S' => 'aciklama'];

    public static function excel(RiskDegerlendirmesi $rd): StreamedResponse
    {
        $icerik = static::olustur($rd);
        $ad = 'risk-degerlendirmesi-'.Str::slug($rd->proje_adi ?: ($rd->firma_unvan ?: 'hastane')).'.xlsx';

        return response()->streamDownload(fn () => print ($icerik), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Kaynak şablon dosyası + sayfa adı (yoksa varsayılan hastane şablonu). */
    public static function sablon(RiskDegerlendirmesi $rd): array
    {
        $veri = $rd->sablon_verisi ?: [];
        $varsayilan = config('hastane_risk.varsayilan_sablon');

        if (! empty($veri['dosya']) && HastaneRiskSablonu::yol($veri['dosya'])) {
            return ['dosya' => $veri['dosya'], 'sayfa' => $veri['sayfa'] ?? null];
        }

        return $varsayilan;
    }

    /** Excel (xlsx) içeriğini döndürür. */
    public static function olustur(RiskDegerlendirmesi $rd): string
    {
        $sablon = static::sablon($rd);
        $kitap = HastaneRiskSablonu::yukle(HastaneRiskSablonu::yol($sablon['dosya']));
        $ws = ($sablon['sayfa'] ? $kitap->getSheetByName($sablon['sayfa']) : null) ?? $kitap->getSheet(0);

        // Yalnız bu sayfa kalır (diğer sayfalar başka birimlerin / yardımcı tablolardır).
        for ($i = $kitap->getSheetCount() - 1; $i >= 0; $i--) {
            if ($kitap->getSheet($i) !== $ws) {
                $kitap->removeSheetByIndex($i);
            }
        }
        $kitap->setActiveSheetIndex(0);

        $duzen = HastaneRiskSablonu::duzen($ws) ?? throw new \RuntimeException('Hastane şablonunun düzeni çözülemedi.');
        static::yaz($ws, $duzen, $rd->loadMissing('maddeler')->maddeler);
        static::sayfaDuzeni($ws, $duzen, $ws->getHighestRow());

        $yol = tempnam(sys_get_temp_dir(), 'hrx');
        IOFactory::createWriter($kitap, 'Xlsx')->save($yol);
        $kitap->disconnectWorksheets();
        $icerik = (string) file_get_contents($yol);
        @unlink($yol);

        return $icerik;
    }

    /**
     * @param  array<string, int|null>  $duzen
     * @param  Collection<int, RiskMaddesi>  $maddeler
     */
    private static function yaz(Worksheet $ws, array $duzen, Collection $maddeler): void
    {
        $protoVeri = $duzen['proto_veri'] ?? $duzen['ilk'];
        $protoBant = $duzen['proto_bant'] ?? null;

        // Kalıp satırların ve alt bilginin stil/değer/yükseklik anlık görüntüsü (silmeden ÖNCE).
        $stilVeri = static::stiller($ws, $protoVeri);
        $stilBant = $protoBant ? static::stiller($ws, $protoBant) : $stilVeri;
        $yukVeri = $ws->getRowDimension($protoVeri)->getRowHeight();
        $yukBant = $protoBant ? $ws->getRowDimension($protoBant)->getRowHeight() : 20;
        $altBilgi = static::altBilgiAl($ws, $duzen);
        $zengin = static::zenginMetinler($ws, $duzen);

        // Künye bandı dışındaki resimler (veri hücrelerine yapışmış 0 genişlikli kalıntılar) satırlar
        // yeniden yazılınca yanlış yere düşer — logo ve künye resimleri kalır.
        $resimler = $ws->getDrawingCollection();
        foreach ($resimler->getArrayCopy() as $i => $resim) {
            if ((int) preg_replace('/^[A-Z]+/', '', $resim->getCoordinates()) > $duzen['alt']) {
                $resimler->offsetUnset($i);
            }
        }

        // Eski veri + alt bilgi bölgesini temizle.
        foreach (array_keys($ws->getMergeCells()) as $aralik) {
            if (preg_match('/^[A-Z]+(\d+):/', $aralik, $e) && (int) $e[1] > $duzen['alt']) {
                $ws->unmergeCells($aralik);
            }
        }
        foreach (array_keys($ws->getConditionalStylesCollection()) as $aralik) {
            $ws->removeConditionalStyles($aralik);
        }
        $silinecek = $ws->getHighestRow() - $duzen['alt'];
        if ($silinecek > 0) {
            $ws->removeRow($duzen['ilk'], $silinecek);
        }

        $r = $duzen['ilk'];
        $no = 0;
        $bolum = null;
        $sayim = array_fill_keys(array_column(config('hastane_risk.bantlar'), 'harf'), 0);

        foreach ($maddeler->sortBy([['sira', 'asc'], ['id', 'asc']]) as $m) {
            if (filled($m->bolum) && $m->bolum !== $bolum) {
                $bolum = $m->bolum;
                static::kopyala($ws, $stilBant, $r);
                $ws->setCellValue('A'.$r, $bolum);
                $ws->mergeCells("A{$r}:S{$r}");
                $ws->getRowDimension($r)->setRowHeight($yukBant > 0 ? $yukBant : 20);
                $r++;
            }

            $no++;
            static::kopyala($ws, $stilVeri, $r);
            $ws->setCellValue('A'.$r, $no);
            foreach (['B' => 'tehlike', 'C' => 'risk', 'D' => 'etki_alani', 'E' => 'mevcut_onlem', 'K' => 'oneri'] as $s => $alan) {
                static::metinYaz($ws, $zengin, $s, $r, $m->{$alan});
            }
            foreach (['F' => 'olasilik', 'G' => 'frekans', 'H' => 'siddet', 'L' => 'son_olasilik', 'M' => 'son_frekans', 'N' => 'son_siddet'] as $s => $alan) {
                $ws->setCellValue($s.$r, $m->{$alan});
            }
            if ($m->olasilik !== null && $m->frekans !== null && $m->siddet !== null) {
                $ws->setCellValue('I'.$r, "=F{$r}*G{$r}*H{$r}");
            }
            if ($m->son_olasilik !== null && $m->son_frekans !== null && $m->son_siddet !== null) {
                $ws->setCellValue('O'.$r, "=L{$r}*M{$r}*N{$r}");
            }
            foreach (['J' => $m->duzey, 'P' => $m->son_duzey] as $s => $harf) {
                $ws->setCellValue($s.$r, $harf);
                if ($harf && ($renk = HastaneRisk::renk($harf))) {
                    $ws->getStyle($s.$r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($renk);
                }
            }
            static::metinYaz($ws, $zengin, 'Q', $r, $m->sorumlu);
            static::metinYaz($ws, $zengin, 'R', $r, $m->termin);
            static::metinYaz($ws, $zengin, 'S', $r, $m->aciklama);

            if ($m->duzey && isset($sayim[$m->duzey])) {
                $sayim[$m->duzey]++;
            }

            static::satirYuksekligi($ws, $r, $m, $yukVeri);
            $r++;
        }

        static::altBilgiYaz($ws, $altBilgi, $r, $no, $sayim);
    }

    /**
     * Şablon veri satırlarındaki karışık renkli/biçimli (zengin) metinler: sütun + düz metin → RichText.
     * Veritabanında yalnız düz metin var; aynı metin yazılırken şablondaki renkler geri konur.
     *
     * @param  array<string, int|null>  $duzen
     * @return array<string, RichText>
     */
    private static function zenginMetinler(Worksheet $ws, array $duzen): array
    {
        $harita = [];
        for ($r = $duzen['ilk']; $r <= $ws->getHighestRow(); $r++) {
            foreach (array_keys(static::METIN_SUTUNLARI) as $s) {
                $deger = $ws->getCell($s.$r)->getValue();
                if ($deger instanceof RichText && count($deger->getRichTextElements()) > 1) {
                    $harita[$s.'|'.static::duzMetin($deger->getPlainText())] ??= $deger;
                }
            }
        }

        return $harita;
    }

    private static function duzMetin(string $metin): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $metin));
    }

    /** @param  array<string, RichText>  $zengin */
    private static function metinYaz(Worksheet $ws, array $zengin, string $sutun, int $satir, mixed $deger): void
    {
        $kalip = is_string($deger) ? ($zengin[$sutun.'|'.static::duzMetin($deger)] ?? null) : null;

        $ws->getCell($sutun.$satir)->setValue($kalip ? clone $kalip : $deger);
    }

    /** @return array<string, int> sütun → xf indeksi */
    private static function stiller(Worksheet $ws, int $satir): array
    {
        $stil = [];
        foreach (static::SUTUNLAR as $s) {
            $stil[$s] = $ws->getCell($s.$satir)->getXfIndex();
        }

        return $stil;
    }

    /** @param  array<string, int>  $stil */
    private static function kopyala(Worksheet $ws, array $stil, int $satir): void
    {
        foreach (static::SUTUNLAR as $s) {
            $ws->getCell($s.$satir)->setXfIndex($stil[$s]);
        }
    }

    /**
     * Alt bilgi bloğunu (özet sayımları, dipnot, ekip alanı) kaydeder.
     *
     * @param  array<string, int|null>  $duzen
     * @return array{satirlar: list<array{ofset: int, yukseklik: float, hucreler: array<string, array{deger: mixed, xf: int}>}>, birlesik: list<array{string, int, string, int}>}
     */
    private static function altBilgiAl(Worksheet $ws, array $duzen): array
    {
        $satirlar = [];
        for ($r = $duzen['footer']; $r <= $duzen['son']; $r++) {
            $hucreler = [];
            foreach (static::SUTUNLAR as $s) {
                $hucre = $ws->getCell($s.$r);
                $hucreler[$s] = ['deger' => $hucre->getValue(), 'xf' => $hucre->getXfIndex()];
            }
            $satirlar[] = ['ofset' => $r - $duzen['footer'], 'yukseklik' => $ws->getRowDimension($r)->getRowHeight(), 'hucreler' => $hucreler];
        }

        $birlesik = [];
        foreach (array_keys($ws->getMergeCells()) as $aralik) {
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $aralik, $e) && (int) $e[2] >= $duzen['footer']) {
                $birlesik[] = [$e[1], (int) $e[2] - $duzen['footer'], $e[3], (int) $e[4] - $duzen['footer']];
            }
        }

        return ['satirlar' => $satirlar, 'birlesik' => $birlesik];
    }

    /**
     * @param  array{satirlar: list<array<string, mixed>>, birlesik: list<array{string, int, string, int}>}  $altBilgi
     * @param  array<string, int>  $sayim
     */
    private static function altBilgiYaz(Worksheet $ws, array $altBilgi, int $baslangic, int $toplam, array $sayim): void
    {
        $bantlar = config('hastane_risk.bantlar');

        foreach ($altBilgi['satirlar'] as $satir) {
            $r = $baslangic + $satir['ofset'];
            foreach ($satir['hucreler'] as $s => $h) {
                $hucre = $ws->getCell($s.$r);
                $hucre->setXfIndex($h['xf']);
                if (is_string($h['deger']) && trim($h['deger']) !== '') {
                    $hucre->setValueExplicit(static::ozetMetni($h['deger'], $toplam, $sayim, $bantlar), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            if ($satir['yukseklik'] > 0) {
                $ws->getRowDimension($r)->setRowHeight($satir['yukseklik']);
            }
        }

        foreach ($altBilgi['birlesik'] as [$s1, $o1, $s2, $o2]) {
            $ws->mergeCells($s1.($baslangic + $o1).':'.$s2.($baslangic + $o2));
        }
    }

    /** Özet blokundaki sayım metinlerini güncel sayılarla yeniler; diğer metinler aynen kalır. */
    public static function ozetMetni(string $metin, int $toplam, array $sayim, array $bantlar): string
    {
        if (str_contains($metin, 'TOPLAM RİSK SAYISI')) {
            return 'BÖLÜM İÇERSİNDEKİ TOPLAM RİSK SAYISI: '.$toplam;
        }

        foreach ($bantlar as $bant) {
            if (str_starts_with(trim($metin), $bant['ozet'])) {
                $n = $sayim[$bant['harf']] ?? 0;

                return $bant['ozet'].':'.($bant['harf'] === 'E' && $n === 0 ? '' : ' '.$n);
            }
        }

        return $metin;
    }

    /** Satır yüksekliği: metin sütunlarındaki satır sayısına göre; sığmazsa yazı küçültülür. */
    private static function satirYuksekligi(Worksheet $ws, int $r, RiskMaddesi $m, float $kalipYuksekligi): void
    {
        $azami = (float) config('hastane_risk.azami_satir_yuksekligi');
        $enAz = $kalipYuksekligi > 0 ? min($kalipYuksekligi, 60) : 30;
        $punto = (float) ($ws->getStyle('B'.$r)->getFont()->getSize() ?: 12);

        for ($boyut = $punto; $boyut >= 6; $boyut -= 1) {
            $satir = 1;
            foreach (static::METIN_SUTUNLARI as $s => $alan) {
                $satir = max($satir, static::satirSayisi((string) $m->{$alan}, (float) $ws->getColumnDimension($s)->getWidth(), $boyut));
            }
            $yukseklik = max($enAz, $satir * $boyut * 1.3 + 6);

            if ($yukseklik <= $azami) {
                break;
            }
        }

        if ($boyut < $punto) {
            $ws->getStyle("A{$r}:S{$r}")->getFont()->setSize($boyut);
        }
        $ws->getRowDimension($r)->setRowHeight(min($yukseklik, $azami));
    }

    /** Sütun genişliğine (karakter birimi) göre sözcük sarmalı tahmini satır sayısı. */
    private static function satirSayisi(string $metin, float $genislik, float $punto): int
    {
        if (trim($metin) === '') {
            return 1;
        }

        // 1 genişlik birimi ≈ 7 px; Times New Roman ortalama karakter ≈ 0,47 em (1 pt = 4/3 px).
        $sigan = max(4, (int) floor((($genislik > 0 ? $genislik : 9.14) * 7 - 4) / (0.5 * $punto * 4 / 3)));
        $toplam = 0;

        foreach (preg_split('/\n/u', $metin) as $paragraf) {
            $uzunluk = 0;
            $satir = 1;
            foreach (preg_split('/\s+/u', trim($paragraf)) as $kelime) {
                $k = mb_strlen($kelime);
                if ($uzunluk > 0 && $uzunluk + 1 + $k > $sigan) {
                    $satir++;
                    $uzunluk = 0;
                }
                $uzunluk += ($uzunluk > 0 ? 1 : 0) + $k;
                while ($uzunluk > $sigan) { // sığmayan uzun sözcük
                    $satir++;
                    $uzunluk -= $sigan;
                }
            }
            $toplam += $satir;
        }

        return $toplam;
    }

    /**
     * A4 yatay, genişliğe sığdır, yükseklik otomatik; her sayfada başlık satırları tekrarlanır.
     *
     * @param  array<string, int|null>  $duzen
     */
    private static function sayfaDuzeni(Worksheet $ws, array $duzen, int $sonSatir): void
    {
        $ps = $ws->getPageSetup();
        $ilk = 1;
        if (preg_match('/^\$?[A-Z]+\$?(\d+)/', (string) $ps->getPrintArea(), $e)) {
            $ilk = (int) $e[1];
        }

        $ps->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4);
        $ps->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_NORMAL);
        $ps->setPrintArea("A{$ilk}:S{$sonSatir}");
        $ps->setRowsToRepeatAtTopByStartAndEnd($ilk, $duzen['alt']);
        $ws->getSheetView()->setZoomScale(70);

        foreach (array_keys($ws->getBreaks()) as $hucre) {
            $ws->setBreak($hucre, Worksheet::BREAK_NONE);
        }
    }
}
