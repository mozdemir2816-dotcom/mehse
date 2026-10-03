<?php

namespace App\Support;

use App\Models\YillikPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Yıllık Değerlendirme Raporu — kullanıcının gerçek Excel şablonu
 * (resources/belge/yillik-degerlendirme.xlsx) birebir doldurulur: her
 * sayfanın künye satırları, 36 çalışma satırının Tarih / Dönem, Yapılan
 * çalışma, Yapan (unvan), Yöntem / Kanıt ve Sonuç sütunları, 7. sayfanın
 * genel sonuç metinleri. Görevli ad soyadı yazılmaz (imza alanında kaşe
 * kullanılır). Şablonda yazdırma ayarı olmadığı için A4 dikey, genişliğe
 * sığdır ve 19 satırlık sayfa blokları arasına sayfa sonu eklenir.
 */
class YillikDegerlendirmeExcelUretici
{
    private const SABLON = 'belge/yillik-degerlendirme.xlsx';

    /** Şablonda her sayfa 19 satırlık blok; 7 sayfa. */
    private const BLOK = 19;

    public static function excel(YillikPlan $plan, ?Carbon $raporTarihi = null): StreamedResponse
    {
        ExcelBellek::artir();

        $firma = $plan->firma;
        $kunye = YillikDegerlendirmeVerisi::kunye($firma, $plan->yil);
        $raporTarihi ??= Carbon::today();

        $kitap = IOFactory::load(resource_path(self::SABLON));
        $s = $kitap->getActiveSheet();
        $s->setTitle('Yıllık Değerlendirme');

        $bos = fn ($v) => filled($v) ? $v : '…';
        $kunyeMetni = [
            'İşyeri unvanı: …' => 'İşyeri unvanı: '.$bos($firma->unvan),
            'SGK sicil no: …' => 'SGK sicil no: '.$bos($firma->sgk_sicil_no),
            'NACE / Faaliyet: …' => 'NACE / Faaliyet: '.$bos($kunye['nace']),
            'Adres: …' => 'Adres: '.$bos($firma->adres),
        ];

        $satirlar = collect($plan->degerlendirmeler ?? []);
        $calisma = $satirlar->where('tur', '!=', 'genel')->values();
        $genel = $satirlar->where('tur', 'genel')->values();

        for ($r = 1; $r <= $s->getHighestRow(); $r++) {
            foreach (['A', 'D'] as $c) {
                $v = (string) $s->getCell($c.$r)->getValue();
                if (str_starts_with($v, 'Tehlike sınıfı:')) {
                    $s->setCellValue($c.$r, 'Tehlike sınıfı: '.$firma->tehlikeSinifiEtiketi().'  |  Telefon / E-posta: '.$bos(collect([$firma->telefon, $firma->eposta])->filter()->implode(' / ')));
                } elseif (str_starts_with($v, 'Çalışan:')) {
                    $s->setCellValue($c.$r, 'Çalışan: Erkek '.$kunye['erkek'].' / Kadın '.$kunye['kadin'].' / Toplam '.$kunye['toplam'].'  |  Genç '.$kunye['genc'].' / Çocuk '.$kunye['cocuk']);
                } elseif (isset($kunyeMetni[$v])) {
                    $s->setCellValue($c.$r, $kunyeMetni[$v]);
                } elseif (str_starts_with($v, 'Rapor yılı:')) {
                    $s->setCellValue($c.$r, 'Rapor yılı: '.$plan->yil.' | Dönem: '.$kunye['donem']);
                } elseif (str_starts_with($v, 'Rapor tarihi:')) {
                    $s->setCellValue($c.$r, preg_replace('/^Rapor tarihi: … \/ … \/ …/u', 'Rapor tarihi: '.$raporTarihi->format('d.m.Y'), $v));
                } elseif (str_starts_with($v, 'Ad soyad: …')) {
                    // Görevli adı yazılmaz — kaşe / imza alanı.
                    $s->setCellValue($c.$r, 'Tarih / Kaşe / İmza:');
                }
            }
            $f = (string) $s->getCell('F'.$r)->getValue();
            if (str_starts_with($f, 'Ad soyad: …')) {
                $s->setCellValue('F'.$r, 'Tarih / Kaşe / İmza:');
            }

            // Çalışma satırları (No sütunu 1..36) — plan sırasına göre
            $no = $s->getCell('A'.$r)->getValue();
            if (is_numeric($no) && ($d = $calisma[(int) $no - 1] ?? null)) {
                $s->setCellValue('B'.$r, filled($d['tarih'] ?? null) ? static::tarih($d['tarih']) : '… / … / …');
                $s->setCellValue('C'.$r, $d['calisma'] ?? '');
                $s->setCellValue('D'.$r, $d['yapan_kisi'] ?? '');
                $s->setCellValue('E'.$r, $d['yontem'] ?? '');
                $s->setCellValue('F'.$r, $d['sonuc'] ?? '');
                static::yukseklik($s, $r, (string) ($d['sonuc'] ?? ''), 52);
            }

            // 7. sayfa genel sonuç: A başlık = plan satırı başlığı
            $baslik = (string) $s->getCell('A'.$r)->getValue();
            if ($baslik !== '' && ($g = $genel->firstWhere('calisma', $baslik))) {
                $s->setCellValue('D'.$r, $g['sonuc'] ?? '');
                static::yukseklik($s, $r, (string) ($g['sonuc'] ?? ''), 95);
            }
        }

        static::ekSatirlar($s, $calisma->slice(36)->values());
        static::yazdirmaAyari($s);

        $tmp = tempnam(sys_get_temp_dir(), 'ydr').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'yillik-degerlendirme-'.$plan->yil.'-'.Str::slug($firma->unvan).'.xlsx');
    }

    /** "2026-05-03" → "03.05.2026"; dönem metni olduğu gibi. */
    private static function tarih(string $t): string
    {
        // Dönem aralığı dar sütunda iki satıra bölünür
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) ? Carbon::parse($t)->format('d.m.Y') : str_replace('–', "–\n", $t);
    }

    /** Uzun sonuç metninde satır yüksekliğini metne göre büyütür (şablon yüksekliğinden küçültmez). */
    private static function yukseklik(Worksheet $s, int $r, string $metin, int $satirBasiKarakter): void
    {
        $satir = max(1, (int) ceil(mb_strlen($metin) / $satirBasiKarakter));
        $gereken = $satir * 12 + 8;
        $mevcut = $s->getRowDimension($r)->getRowHeight();
        if ($gereken > $mevcut) {
            $s->getRowDimension($r)->setRowHeight($gereken);
        }
    }

    /** 36'dan fazla (elle eklenen) satır: 6. sayfanın son çalışma satırından sonra eklenir. */
    private static function ekSatirlar(Worksheet $s, $ekler): void
    {
        if ($ekler->isEmpty()) {
            return;
        }

        $son = null;
        for ($r = 1; $r <= $s->getHighestRow(); $r++) {
            if ((string) $s->getCell('A'.$r)->getValue() === '36') {
                $son = $r;
            }
        }
        if (! $son) {
            return;
        }

        $s->insertNewRowBefore($son + 1, $ekler->count());
        foreach ($ekler as $i => $d) {
            $r = $son + 1 + $i;
            $s->duplicateStyle($s->getStyle('A'.$son.':F'.$son), 'A'.$r.':F'.$r);
            $s->getRowDimension($r)->setRowHeight($s->getRowDimension($son)->getRowHeight());
            $s->fromArray([37 + $i, filled($d['tarih'] ?? null) ? static::tarih($d['tarih']) : '… / … / …', $d['calisma'] ?? '', $d['yapan_kisi'] ?? '', $d['yontem'] ?? '', $d['sonuc'] ?? ''], null, 'A'.$r);
        }
    }

    private static function yazdirmaAyari(Worksheet $s): void
    {
        $s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)->setFitToHeight(0);
        $s->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);

        // Her sayfa bloğu "İŞ SAĞLIĞI VE GÜVENLİĞİ YILLIK DEĞERLENDİRME RAPORU" başlığıyla başlar.
        $baslar = [];
        for ($r = 1; $r <= $s->getHighestRow(); $r++) {
            if (str_starts_with((string) $s->getCell('A'.$r)->getValue(), 'İŞ SAĞLIĞI VE GÜVENLİĞİ YILLIK DEĞERLENDİRME RAPORU')) {
                $baslar[] = $r;
            }
        }
        foreach (array_slice($baslar, 1) as $r) {
            $s->setBreak('A'.($r - 1), Worksheet::BREAK_ROW);
        }
        $s->getPageSetup()->setPrintArea('A1:F'.$s->getHighestRow());
    }
}
