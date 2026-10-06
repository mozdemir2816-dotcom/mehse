<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KurulToplantisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Kurulu toplantı tutanağı çıktısı — PDF (dompdf), Word (PhpWord) ve Excel (PhpSpreadsheet).
 * isgpratik yardım/kurul-toplantisi rehberi — "PDF İçeriği" listesi.
 */
class KurulToplantisiUretici
{
    private const BASLIK_ARKA = 'F0F0F0';

    public static function pdf(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kurul-toplantisi', [
            'toplanti' => $toplanti,
            'firma' => $toplanti->firma,
        ])->setPaper('a4');

        // Alt bilgi şeridinin sağına "Sayfa X / Y" (isgsuite tutanağındaki gibi) —
        // tek render, page_script ile (bkz. EgitimKatilimUretici).
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_script(function (int $sayfa, int $toplam) use ($canvas, $font): void {
            $canvas->text($canvas->get_width() - 88, $canvas->get_height() - 22, "Sayfa {$sayfa} / {$toplam}", $font, 7, [0.39, 0.45, 0.55]);
        });

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($toplanti, 'pdf'));
    }

    /** Aynı tutanak Excel olarak — kararlar tablosunda "Durum" sütunu yok, "Karar Metni" geniş. */
    public static function excel(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Tutanak');

        $satir = 1;

        $s->setCellValue("A{$satir}", 'İSG KURULU TOPLANTI TUTANAĞI');
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(14);
        $s->getStyle("A{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $satir++;

        $s->setCellValue("A{$satir}", $toplanti->firma?->unvan ?? '');
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $satir += 2;

        foreach ([
            'Toplantı No' => $toplanti->toplanti_no ?: '—',
            'Belge No' => $toplanti->belge_no ?: '—',
            'Revizyon' => $toplanti->revizyon_no ?: '00',
            'Tarih' => $toplanti->tarih?->format('d.m.Y') ?: '—',
            'Saat' => $toplanti->saatAraligi() ?: '—',
            'Toplantı Yeri' => $toplanti->yer ?: '—',
            'Toplantı Türü' => $toplanti->turEtiketi(),
            'Toplantı Başkanı' => $toplanti->baskan ?: '—',
            'Sonraki Toplantı' => $toplanti->sonraki_toplanti?->format('d.m.Y') ?: '—',
        ] as $etiket => $deger) {
            $s->setCellValue("A{$satir}", $etiket);
            $s->getStyle("A{$satir}")->getFont()->setBold(true);
            $s->setCellValue("B{$satir}", $deger);
            $satir++;
        }
        $satir++;

        // KATILIMCILAR
        $satir = self::bolumBasligi($s, $satir, 'KATILIMCILAR');
        $satir = self::tabloBasligi($s, $satir, ['#', 'Ad Soyad', 'Görevi (İşe Giriş Bildirgesi)', 'Kuruldaki Görevi', 'Katılım']);
        foreach (KurulUyeleri::tutanakKatilimcilari($toplanti) as $i => $k) {
            $s->fromArray([
                $i + 1,
                $k['ad_basilir'] ? $k['ad_soyad'] : '', // kaşeli görevli adı basılmaz
                $k['is_gorevi'],
                $k['kurul_gorevi'],
                $k['katildi'] ? 'Katıldı' : 'Katılmadı',
            ], null, "A{$satir}");
            $satir++;
        }
        $satir++;

        // GÜNDEM
        $satir = self::bolumBasligi($s, $satir, 'GÜNDEM');
        $satir = self::tabloBasligi($s, $satir, ['#', 'Gündem Maddesi']);
        foreach (($toplanti->gundem ?? []) as $i => $madde) {
            $s->fromArray([$i + 1, $madde], null, "A{$satir}");
            $satir++;
        }
        $satir++;

        // ALINAN KARARLAR — "Durum" sütunu yok
        $satir = self::bolumBasligi($s, $satir, 'ALINAN KARARLAR');
        $satir = self::tabloBasligi($s, $satir, ['#', 'İlgili Gündem', 'Karar Metni', 'Sorumlu', 'Termin']);
        foreach (($toplanti->kararlar ?? []) as $i => $k) {
            $s->fromArray([
                $i + 1,
                $k['gundem_maddesi'] ?? '—',
                $k['karar_metni'] ?? '—',
                $k['sorumlu'] ?? '—',
                $k['termin'] ?? '—',
            ], null, "A{$satir}");
            $s->getStyle("C{$satir}")->getAlignment()->setWrapText(true);
            $satir++;
        }

        // Sütun genişlikleri: Karar Metni geniş, Sorumlu dar.
        $s->getColumnDimension('A')->setWidth(5);
        $s->getColumnDimension('B')->setWidth(30);
        $s->getColumnDimension('C')->setWidth(70);
        $s->getColumnDimension('D')->setWidth(16);
        $s->getColumnDimension('E')->setWidth(14);

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, self::dosyaAdi($toplanti, 'xlsx'));
    }

    private static function bolumBasligi(Worksheet $s, int $satir, string $metin): int
    {
        $s->setCellValue("A{$satir}", $metin);
        $s->mergeCells("A{$satir}:E{$satir}");
        $s->getStyle("A{$satir}")->getFont()->setBold(true)->setSize(11);

        return $satir + 1;
    }

    /** @param array<int, string> $basliklar */
    private static function tabloBasligi(Worksheet $s, int $satir, array $basliklar): int
    {
        $s->fromArray($basliklar, null, "A{$satir}");

        $sonSutun = chr(ord('A') + count($basliklar) - 1);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getFont()->setBold(true);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BASLIK_ARKA);
        $s->getStyle("A{$satir}:{$sonSutun}{$satir}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN);

        return $satir + 1;
    }

    /**
     * Başlığın sol kutusundaki logo: firma logosu, yoksa OSGB logosu
     * (config isg.kurul_toplantisi.varsayilan_logo). PDF ve Word ortak.
     */
    public static function logoYolu(?Firma $firma): ?string
    {
        if ($firma?->logo && is_file($yol = Storage::disk('public')->path($firma->logo))) {
            return $yol;
        }

        $varsayilan = resource_path((string) config('isg.kurul_toplantisi.varsayilan_logo', ''));

        return is_file($varsayilan) ? $varsayilan : null;
    }

    /**
     * Tutanak Word (.docx) olarak — PDF ile aynı düzen (1. sayfa katılanlar +
     * gündem, kararlar yeni sayfada, sonda imza tablosu). Kullanıcı kararları
     * Word'de düzeltip çıktı alabilsin diye (06.10.2026).
     */
    public static function word(KurulToplantisi $toplanti): StreamedResponse
    {
        $toplanti->loadMissing('firma');
        $firma = $toplanti->firma;

        $kisiler = KurulUyeleri::tutanakKatilimcilari($toplanti);
        $katilanlar = array_values(array_filter($kisiler, fn ($k) => $k['katildi']));
        $katilmayanlar = array_values(array_filter($kisiler, fn ($k) => ! $k['katildi']));
        $ad = fn (array $k) => $k['ad_basilir'] ? $k['ad_soyad'] : ''; // kaşeli görevli adı basılmaz

        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);

        $kenar = ['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 70];
        $baslikHucre = ['bgColor' => 'DBEAFE', 'valign' => 'center'];
        $kalin = ['bold' => true, 'color' => '1E3A5F'];
        $h2 = ['bold' => true, 'size' => 11, 'color' => '1E3A5F'];
        $h2p = ['spaceBefore' => 200, 'spaceAfter' => 80];

        $bolum = $word->addSection(['marginTop' => 600, 'marginBottom' => 700, 'marginLeft' => 650, 'marginRight' => 650]);
        $bolum->addFooter()->addPreserveText(
            'İSG Kurulu Toplantı Tutanağı · '.($firma?->unvan ?? '').' · Sayfa {PAGE} / {NUMPAGES}',
            ['size' => 7, 'color' => '64748B'], ['alignment' => Jc::END]
        );

        // Üst başlık: logo | başlık | belge bilgisi
        $ust = $bolum->addTable(['borderSize' => 6, 'borderColor' => '94A3B8', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct']);
        $ust->addRow(900);
        $logoHucre = $ust->addCell(2600, ['valign' => 'center']);
        if ($logo = self::logoYolu($firma)) {
            $logoHucre->addImage($logo, ['width' => 115, 'height' => 42, 'alignment' => Jc::CENTER, 'wrappingStyle' => 'inline']);
        }
        $baslik = $ust->addCell(4800, ['valign' => 'center', 'bgColor' => 'EFF6FF'])->addTextRun(['alignment' => Jc::CENTER]);
        $baslik->addText('İŞ SAĞLIĞI VE GÜVENLİĞİ KURULU', ['bold' => true, 'size' => 13, 'color' => '1E3A5F']);
        $baslik->addTextBreak();
        $baslik->addText('TOPLANTI TUTANAĞI', ['bold' => true, 'size' => 13, 'color' => '1E3A5F']);
        $bilgi = $ust->addCell(2800, ['valign' => 'center']);
        foreach ([
            'Belge No' => $toplanti->belge_no ?: '—',
            'Toplantı No' => $toplanti->toplanti_no ?: '—',
            'Revizyon' => $toplanti->revizyon_no ?: '00',
            'Oluşturma' => now()->format('d.m.Y'),
        ] as $e => $d) {
            $run = $bilgi->addTextRun(['spaceAfter' => 0]);
            $run->addText($e.': ', ['bold' => true, 'size' => 8, 'color' => '475569']);
            $run->addText($d, ['size' => 8]);
        }

        // Künye
        $bolum->addTextBreak(1, ['size' => 4]);
        $katilanSayisi = count($katilanlar);
        $kunye = $bolum->addTable($kenar + ['width' => 100 * 50, 'unit' => 'pct']);
        foreach ([
            ['İşyeri', $firma?->unvan ?? '—', 'Adres', collect([$firma?->adres, $firma?->ilce, $firma?->il])->filter()->implode(', ') ?: '—'],
            ['Toplantı tarihi', $toplanti->tarih?->format('d.m.Y') ?: '—', 'Saat', $toplanti->saatAraligi() ?: '—'],
            ['Toplantı yeri', $toplanti->yer ?: '—', 'Toplantı türü', $toplanti->turEtiketi()],
            ['Toplantı başkanı', 'İşveren / İşveren Vekili', 'Sonraki toplantı', $toplanti->sonraki_toplanti?->format('d.m.Y') ?: '—'],
            ['Katılım', 'Katılan: '.$katilanSayisi.' / '.count($kisiler).' kişi'.(count($katilmayanlar) ? ' · Katılmayan: '.count($katilmayanlar) : ''), null, null],
        ] as $s) {
            $kunye->addRow();
            $kunye->addCell(1700, ['bgColor' => 'F1F5F9'])->addText($s[0], ['bold' => true, 'color' => '475569']);
            if ($s[2] === null) {
                $kunye->addCell(8500, ['gridSpan' => 3])->addText($s[1]);

                continue;
            }
            $kunye->addCell(3400)->addText($s[1]);
            $kunye->addCell(1700, ['bgColor' => 'F1F5F9'])->addText($s[2], ['bold' => true, 'color' => '475569']);
            $kunye->addCell(3400)->addText($s[3]);
        }

        // 1. sayfa: katılanlar
        $bolum->addText('Toplantıya Katılanlar', $h2, $h2p);
        $t = $bolum->addTable($kenar + ['width' => 100 * 50, 'unit' => 'pct']);
        $t->addRow();
        foreach ([['#', 500], ['Ad Soyad', 2700], ['Görevi (İşe Giriş Bildirgesi)', 3200], ['Kuruldaki Görevi', 3800]] as [$b, $g]) {
            $t->addCell($g, $baslikHucre)->addText($b, $kalin);
        }
        foreach ($katilanlar as $i => $k) {
            $t->addRow();
            $t->addCell(500)->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
            $t->addCell(2700)->addText($ad($k));
            $t->addCell(3200)->addText($k['is_gorevi']);
            $t->addCell(3800)->addText($k['kurul_gorevi']);
        }
        if ($katilmayanlar) {
            $bolum->addText('Katılmayan: '.collect($katilmayanlar)->map(fn ($k) => ($ad($k) ? $ad($k).' — ' : '').$k['kurul_gorevi'])->implode('; '),
                ['size' => 8, 'color' => '64748B'], ['spaceBefore' => 60]);
        }

        // Gündem
        $bolum->addText('Gündem (Toplantı Konuları)', $h2, $h2p);
        $t = $bolum->addTable($kenar + ['width' => 100 * 50, 'unit' => 'pct']);
        foreach (($toplanti->gundem ?? []) ?: ['Gündem maddesi eklenmedi.'] as $i => $madde) {
            $t->addRow();
            $t->addCell(500)->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
            $t->addCell(9700)->addText($madde);
        }

        // 2. sayfadan itibaren: kararlar
        $bolum->addText('Kararlar ve Takip', $h2, ['pageBreakBefore' => true, 'spaceAfter' => 80]);
        $t = $bolum->addTable($kenar + ['width' => 100 * 50, 'unit' => 'pct']);
        $t->addRow();
        foreach ([['#', 500], ['İlgili Gündem', 2400], ['Karar Metni', 4500], ['Sorumlu', 1500], ['Termin', 1300]] as [$b, $g]) {
            $t->addCell($g, $baslikHucre)->addText($b, $kalin);
        }
        foreach (($toplanti->kararlar ?? []) as $i => $k) {
            $t->addRow();
            $t->addCell(500)->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
            $t->addCell(2400)->addText((string) ($k['gundem_maddesi'] ?? '—'));
            $t->addCell(4500)->addText((string) ($k['karar_metni'] ?? '—'));
            $t->addCell(1500)->addText((string) (($k['sorumlu'] ?? null) ?: '—'));
            $t->addCell(1300)->addText(filled($k['termin'] ?? null) ? Carbon::parse($k['termin'])->format('d.m.Y') : '—');
        }
        if (! ($toplanti->kararlar ?? [])) {
            // Boş karar satırları — elle doldurmak için
            foreach (range(1, 5) as $i) {
                $t->addRow(500);
                $t->addCell(500)->addText((string) $i, [], ['alignment' => Jc::CENTER]);
                foreach ([2400, 4500, 1500, 1300] as $g) {
                    $t->addCell($g);
                }
            }
        }

        if (filled($toplanti->notlar)) {
            $bolum->addText('Notlar', $h2, $h2p);
            foreach (preg_split('/\r\n|\r|\n/', (string) $toplanti->notlar) as $satir) {
                $bolum->addText($satir);
            }
        }

        // Sonda: katılımcı imzaları
        $bolum->addText('Katılımcı İmzaları', $h2, $h2p + ['keepNext' => true]);
        $bolum->addText('Yukarıdaki kararlar toplantıya katılan kurul üyelerince alınmış ve imza altına alınmıştır.',
            ['size' => 8, 'color' => '475569'], ['spaceAfter' => 60, 'keepNext' => true]);
        $t = $bolum->addTable($kenar + ['width' => 100 * 50, 'unit' => 'pct']);
        $t->addRow(null, ['cantSplit' => true]);
        foreach ([['#', 500], ['Ad Soyad', 2900], ['Kuruldaki Görevi', 3500], ['İmza', 3300]] as [$b, $g]) {
            $t->addCell($g, $baslikHucre)->addText($b, $kalin);
        }
        foreach ($katilanlar as $i => $k) {
            $t->addRow(650, ['cantSplit' => true]);
            $t->addCell(500, ['valign' => 'center'])->addText((string) ($i + 1), [], ['alignment' => Jc::CENTER]);
            $t->addCell(2900, ['valign' => 'center'])->addText($ad($k));
            $t->addCell(3500, ['valign' => 'center'])->addText($k['kurul_gorevi']);
            $t->addCell(3300);
        }

        $gecici = tempnam(sys_get_temp_dir(), 'mehse-kurul').'.docx';
        WordIO::createWriter($word, 'Word2007')->save($gecici);

        // deleteFileAfterSend bu ortamda takılabiliyor — streamDownload + elle sil.
        return response()->streamDownload(function () use ($gecici) {
            echo file_get_contents($gecici);
            @unlink($gecici);
        }, self::dosyaAdi($toplanti, 'docx'));
    }

    private static function dosyaAdi(KurulToplantisi $toplanti, string $uzanti): string
    {
        $ek = $toplanti->toplanti_no
            ? Str::slug(str_replace('/', '-', $toplanti->toplanti_no))
            : $toplanti->tarih?->format('Y-m-d');

        return 'kurul-toplantisi-'.Str::slug($toplanti->firma?->unvan ?? 'firma').'-'.$ek.'.'.$uzanti;
    }
}
