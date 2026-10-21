<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu PDF ve Excel üretimi. isgpratik EĞİTİM ekranları.
 * Klasik düzen: konular → katılımcı imza listesi → eğitmenler formun ALTINDA;
 * 10 kişilik form tek A4 sayfaya sığar (küçük punto, sıkı düzen).
 */
class EgitimKatilimUretici
{
    private const BASLIK_ARKA = 'F0F0F0';

    /** @param  bool  $imzali  false = imzasız (matbu): eğitmen kaşe/imza görselleri basılmaz */
    public static function pdf(EgitimKatilim $kayit, bool $imzali = true): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.pdf';

        return static::pdfCikti($kayit, $kayit->firma, $kayit->konu_secimleri ?? [], $ad, $imzali);
    }

    /**
     * pdf.egitim-katilim'i render eder; katılımcı listesi 2. sayfaya taşarsa
     * her sayfaya "Belge No · Sayfa X/Y" damgalar (eğitmen imzası zaten
     * position:fixed ile her sayfada). Tek sayfada damga yok.
     */
    private static function pdfCikti(EgitimKatilim $kayit, $firma, array $icerik, string $ad, bool $imzali = true): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.egitim-katilim', compact('kayit', 'firma', 'icerik', 'imzali'))->setPaper('a4');
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $belgeNo = (string) $kayit->belge_no;

        $canvas->page_script(function (int $pageNumber, int $pageCount) use ($canvas, $font, $belgeNo): void {
            if ($pageCount > 1) {
                $canvas->text($canvas->get_width() - 132, 15, "{$belgeNo} · Sayfa {$pageNumber} / {$pageCount}", $font, 6.5, [0.45, 0.45, 0.45]);
            }
        });

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * Firma/katılımcı seçmeden, yalnız konu içeriğiyle boş imza formu — eğitime
     * gelenlerin kendi el yazısıyla ad/T.C./imza atması için (en az 10 satır,
     * bkz. pdf.egitim-katilim şablonundaki satır tamamlama mantığı).
     */
    /** Firma verilirse belgede firma adı basılır (firma evrak paketi); katılımcılar yine boş. */
    public static function bosFormPdf(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi, string $egitimTuru, ?Firma $firma = null): StreamedResponse
    {
        $icerik = EgitimIcerikOlusturucu::olustur($baslikAnahtari, $sektorAnahtari, $tehlikeSinifi, $egitimTuru);

        $kayit = new EgitimKatilim([
            'baslik_anahtari' => $baslikAnahtari,
            'egitim_turu' => $egitimTuru,
            'sure_gun' => EgitimIcerikOlusturucu::planlananGun($icerik),
            'isg_uzmani_var' => true,
            'isyeri_hekimi_var' => false,
            'katilimcilar' => [],
        ]);
        $kayit->belge_no = 'BOŞ FORM';

        $ad = 'bos-egitim-katilim-'.Str::slug($kayit->basliklarEtiketi()).'.pdf';

        return static::pdfCikti($kayit, $firma, $icerik, $ad);
    }

    /** Kullanıcının kendi formu (resources/belge) — konular/başlık/logo elle düzenlenir, kod dokunmaz. */
    public const EXCEL_SABLONU = 'belge/egitim-katilim-formu.xlsx';

    /** Şablonda katılımcı satırlarının başladığı satır ve hazır satır sayısı (29–38). */
    private const KATILIMCI_ILK_SATIR = 29;

    private const KATILIMCI_SABLON_SATIR = 10;

    /**
     * Kayıtlı bir eğitim katılım formunu kullanıcının Excel şablonuna doldurur.
     * Yalnız firma ismi (F26), tarih (L4), eğitim yeri (F4, girildiyse), ders saati (L26)
     * ve katılımcılar (sıra C/D, ad soyad E:G, T.C. H, görev I:K) yazılır; konular, başlık,
     * logo, imza/sınav sütunları ve sayfa düzeni şablonda nasılsa öyle kalır.
     * 10'dan fazla katılımcıda satır eklenir (biçim ve birleştirmeler kopyalanır).
     * 2 günlük eğitimde şablonda gizli olan "2. GÜN İMZA" (M) sütunu açılır.
     */
    /** Şablonun sabit konu bölümü (F5:O25) — sol yarı (F:J), sağ yarı (K:O). */
    private const KONU_ILK_SATIR = 5;

    private const KONU_SON_SATIR = 25;

    /**
     * Şablondaki F5:O25 bölümü tek bir örnek firmaya göre elle yazılmış sabit
     * metinlerle geliyordu (hangi eğitim/sektör seçilse de hep aynı konular
     * basılıyordu — bkz. kullanıcı şikâyeti). Bu metot o bölümü PDF/Word'deki
     * gibi kaydın gerçek konu seçimleriyle (icerik) yeniden doldurur:
     * 'genel' başlıkta sol yarı Genel+Sağlık, sağ yarı Teknik+İşyerine Özgü;
     * diğer başlıklarda tek blok tam genişlikte (F:O). 21 satır (5–25) yetmezse
     * altına satır eklenir; döndürülen sayı firma/katılımcı satırlarının kaç
     * satır kaydığıdır (çağıran bu kadarını sonraki sabit satır numaralarına ekler).
     */
    private static function konulariDoldur($s, array $icerik): int
    {
        $ilk = self::KONU_ILK_SATIR;
        $son = self::KONU_SON_SATIR;

        // Şablonun F-O arasındaki eski (tutarsız) birleşimlerini kaldır — yeni
        // birleşimler bunlarla çakışırsa PhpSpreadsheet hata verir.
        foreach (array_values($s->getMergeCells()) as $aralik) {
            [[$c1, $r1], [$c2, $r2]] = Coordinate::rangeBoundaries($aralik);
            if ($r2 < $ilk || $r1 > $son || $c2 < 6 || $c1 > 15) {
                continue;   // F..O (6..15) / 5..25 ile kesişmiyor
            }
            $s->unmergeCells($aralik);
        }

        for ($r = $ilk; $r <= $son; $r++) {
            foreach (range('F', 'O') as $sutun) {
                $s->setCellValue("{$sutun}{$r}", null);
            }
        }

        $genel = ($icerik['tip'] ?? null) === 'genel';

        if ($genel) {
            $sol = [
                ...self::blokSatirlari('Genel Konular', $icerik['genel_konular'] ?? []),
                ...self::blokSatirlari('Sağlık Konuları', $icerik['saglik_konulari'] ?? []),
            ];

            $sag = self::blokSatirlari('Teknik Konular', $icerik['teknik_konular'] ?? []);

            if ($icerik['isyerine_ozgu'] ?? null) {
                $sag = [...$sag, ...self::blokSatirlari('İşyerine Özgü Riskler — '.$icerik['isyerine_ozgu']['sektor'], $icerik['isyerine_ozgu']['maddeler'] ?? [])];
            } else {
                $sag[] = ['metin' => 'İşyerine Özgü Riskler', 'kalin' => true];
                $sag[] = ['metin' => 'Sektör seçilmedi.', 'kalin' => false];
            }

            $gereken = max(count($sol), count($sag));
        } else {
            $tek = self::blokSatirlari($icerik['ad'] ?? '', $icerik['maddeler'] ?? []);
            $gereken = count($tek);
        }

        $ek = max(0, $gereken - ($son - $ilk + 1));

        if ($ek > 0) {
            $s->insertNewRowBefore($son + 1, $ek);

            for ($r = $son + 1; $r <= $son + $ek; $r++) {
                if ($genel) {
                    $s->mergeCells("F{$r}:J{$r}");
                    $s->mergeCells("K{$r}:O{$r}");
                    $s->duplicateStyle($s->getStyle("F{$son}"), "F{$r}");
                    $s->duplicateStyle($s->getStyle("K{$son}"), "K{$r}");
                } else {
                    $s->mergeCells("F{$r}:O{$r}");
                    $s->duplicateStyle($s->getStyle("F{$son}"), "F{$r}");
                }
            }
        }

        if ($genel) {
            for ($i = 0; $i < $gereken; $i++) {
                $r = $ilk + $i;
                $s->mergeCells("F{$r}:J{$r}");
                $s->mergeCells("K{$r}:O{$r}");

                if (isset($sol[$i])) {
                    $s->setCellValue("F{$r}", $sol[$i]['metin']);
                    $s->getStyle("F{$r}")->getFont()->setBold($sol[$i]['kalin']);
                }
                if (isset($sag[$i])) {
                    $s->setCellValue("K{$r}", $sag[$i]['metin']);
                    $s->getStyle("K{$r}")->getFont()->setBold($sag[$i]['kalin']);
                }
            }
        } else {
            foreach ($tek as $i => $satir) {
                $r = $ilk + $i;
                $s->mergeCells("F{$r}:O{$r}");
                $s->setCellValue("F{$r}", $satir['metin']);
                $s->getStyle("F{$r}")->getFont()->setBold($satir['kalin']);
            }
        }

        return $ek;
    }

    /** @return array<int, array{metin: string, kalin: bool}> başlık satırı + dahil edilen madde satırları */
    private static function blokSatirlari(string $baslik, array $maddeler): array
    {
        $sure = EgitimIcerikOlusturucu::bolumSuresi($maddeler);
        $satirlar = [['metin' => "{$baslik} ({$sure['fiili']}+{$sure['dinlenme']} dk)", 'kalin' => true]];

        foreach (collect($maddeler)->where('dahil', true)->values() as $m) {
            $satirlar[] = ['metin' => '• '.($m['madde'] ?? '').' ('.($m['dakika'] ?? 0).' dk)', 'kalin' => false];
        }

        return $satirlar;
    }

    public static function excel(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');
        ExcelBellek::artir();

        $icerik = $kayit->konu_secimleri ?? [];
        $ikiGun = ($kayit->sure_gun ?? 1) >= 2;

        $tarihler = collect($kayit->gun_tarihleri ?? [])->filter()
            ->map(fn ($t) => Carbon::parse($t)->format('d.m.Y'))->values();
        if ($tarihler->isEmpty() && $kayit->belge_tarihi) {
            $tarihler = collect([$kayit->belge_tarihi->format('d.m.Y')]);
        }

        $kitap = IOFactory::load(resource_path(self::EXCEL_SABLONU));
        $s = $kitap->getSheet(0);

        // Şablonun sabit örnek konu metinleri yerine kaydın gerçek konu seçimlerini
        // yazar; gerekirse satır ekler — dönen sayı firma/katılımcı satırlarının
        // kaymasıdır (bkz. konulariDoldur).
        $ekSatir = self::konulariDoldur($s, $icerik);
        $firmaSatiri = 26 + $ekSatir;
        $katilimciIlkSatir = self::KATILIMCI_ILK_SATIR + $ekSatir;

        $s->setCellValue("F{$firmaSatiri}", $kayit->firma?->unvan ?? '');
        if ($tarihler->isNotEmpty()) {
            $s->setCellValue('L4', $tarihler->implode(' - '));
        }
        if (filled($kayit->egitim_yeri)) {
            $s->setCellValue('F4', $kayit->egitim_yeri);
        }
        // Ders saati boşsa şablondaki örnek "8" kalmasın — elle doldurulacak boşluk.
        $saat = ($icerik['saat'] ?? null) ?: '..........';
        $s->setCellValue("L{$firmaSatiri}", "Eğitim Süresi: / {$saat} Ders Saati");
        $s->setCellValue("M{$firmaSatiri}", "Eğitim Süresi: / {$saat} Ders Saati");
        $s->getColumnDimension('M')->setVisible($ikiGun);
        if ($ikiGun) {
            // Şablonda firma satırında L ve M aynı metni taşır (M gizliyken tek görünür); 2 günde tek hücre olsun.
            $s->setCellValue("M{$firmaSatiri}", null);
            $s->mergeCells("L{$firmaSatiri}:M{$firmaSatiri}");
        }

        $katilimcilar = array_values($kayit->katilimcilar ?? []);
        $ilk = $katilimciIlkSatir;
        $fazla = max(0, count($katilimcilar) - self::KATILIMCI_SABLON_SATIR);

        if ($fazla > 0) {
            $sonSablon = $ilk + self::KATILIMCI_SABLON_SATIR - 1;
            $s->insertNewRowBefore($sonSablon + 1, $fazla);   // biçim üstteki satırdan kopyalanır

            for ($r = $sonSablon + 1; $r <= $sonSablon + $fazla; $r++) {
                $s->mergeCells("E{$r}:G{$r}");
                $s->mergeCells("I{$r}:K{$r}");
                $s->getRowDimension($r)->setRowHeight($s->getRowDimension($sonSablon)->getRowHeight());
            }
            // Yazdırma alanı (A1:O43) satır eklenince kütüphane tarafından kendiliğinden uzatılır.
        }

        // Şablon satırlarının hizası birbirinden farklı (bazısı sola, bazısı ortaya dayalı):
        // tüm katılımcı satırlarını ilk satırın biçimine eşitle, yazılar dikeyde ortalı olsun.
        $sonSatir = $ilk + max(count($katilimcilar), self::KATILIMCI_SABLON_SATIR) - 1;
        foreach (range('C', 'O') as $sutun) {
            $s->duplicateStyle($s->getStyle("{$sutun}{$ilk}"), "{$sutun}{$ilk}:{$sutun}{$sonSatir}");
        }
        $s->getStyle("C{$ilk}:O{$sonSatir}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ($katilimcilar as $i => $k) {
            $r = $ilk + $i;
            $s->setCellValue("C{$r}", $i + 1);
            $s->setCellValue("D{$r}", $i + 1);
            $s->setCellValue("E{$r}", $k['ad_soyad'] ?? '');
            $s->setCellValueExplicit("H{$r}", (string) ($k['tc'] ?? ''), DataType::TYPE_STRING);
            $s->setCellValue("I{$r}", $k['gorev'] ?? '');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'egt').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.xlsx';

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad);
    }

    /**
     * Eğitim Katılım Formu'nun Word (.docx) çıktısı — `pdf.egitim-katilim` ile aynı
     * içerik (künye, seçimler, konu blokları, katılımcı imza listesi, eğitmen
     * kaşe/imza). Excel şablonundaki gibi sabit bir şablon yoktur; konu sayısı
     * eğitimden eğitime değiştiği için `phpoffice/phpword` ile programatik üretilir.
     *
     * @param  bool  $imzali  false = imzasız (matbu): eğitmen kaşe/imza görselleri basılmaz
     */
    public static function word(EgitimKatilim $kayit, bool $imzali = true): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(9);

        self::wordBolumuEkle($phpWord, $kayit, $kayit->firma, $kayit->konu_secimleri ?? [], $imzali);

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.docx';

        return self::wordIndir($phpWord, $ad);
    }

    private static function wordBolumuEkle(PhpWord $phpWord, EgitimKatilim $kayit, ?Firma $firma, array $icerik, bool $imzali): void
    {
        $bolum = $phpWord->addSection([
            'marginTop' => 560, 'marginBottom' => 560, 'marginLeft' => 680, 'marginRight' => 680,
        ]);

        if ($firma?->logo && is_file($lg = storage_path('app/public/'.$firma->logo))) {
            $bolum->addImage($lg, ['height' => 32, 'alignment' => Jc::CENTER]);
        }
        $bolum->addText('EĞİTİM KATILIM FORMU', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        if ($firma?->unvan) {
            $bolum->addText($firma->unvan, ['size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);
        }

        $ikiGun = ($kayit->sure_gun ?? 1) >= 2;
        $sureMetni = ($icerik['saat'] ?? null)
            ? $icerik['saat'].' Ders Saati · '.($kayit->sure_gun ?? 1).' gün'.($ikiGun ? ' (1. ve 2. gün)' : '')
            : '.......... Ders Saati';
        $gunTarihleri = collect($kayit->gun_tarihleri ?? [])->filter()
            ->map(fn ($t) => Carbon::parse($t)->format('d.m.Y'))->values();
        $tarihMetni = $gunTarihleri->count() > 1
            ? $gunTarihleri->map(fn ($t, $i) => ($i + 1).'. Gün: '.$t)->implode(' · ')
            : ($gunTarihleri->first() ?? $kayit->belge_tarihi?->format('d.m.Y') ?? '..... / ..... / ..........');
        $tekrarMi = ($kayit->egitim_turu ?? 'ilk') === 'tekrar';
        $sekil = $kayit->egitim_sekli ?? 'yuz_yuze';
        $tik = fn (bool $v) => $v ? '☑' : '☐';

        $kunyeStil = ['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 60];
        $satir = function ($tablo, array $hucreler): void {
            $tablo->addRow();
            foreach ($hucreler as [$genislik, $metin, $etiketMi]) {
                $tablo->addCell($genislik, $etiketMi ? ['bgColor' => self::BASLIK_ARKA] : [])
                    ->addText($metin, $etiketMi ? ['bold' => true, 'size' => 8] : ['size' => 8]);
            }
        };

        $kunye = $bolum->addTable($kunyeStil);
        $satir($kunye, [
            [1700, 'Eğitim Konusu', true], [3400, $kayit->basliklarEtiketi(), false],
            [1700, 'Belge No', true], [2600, (string) $kayit->belge_no, false],
        ]);
        $satir($kunye, [
            [1700, 'Eğitim Yeri', true], [3400, $kayit->egitim_yeri ?: '—', false],
            [1700, 'Tarih', true], [2600, $tarihMetni, false],
        ]);
        $egitimciler = implode(' · ', array_filter([
            $kayit->isg_uzmani_var ? 'İş Güvenliği Uzmanı' : null,
            $kayit->isyeri_hekimi_var ? 'İşyeri Hekimi' : null,
        ])) ?: '—';
        $satir($kunye, [
            [1700, 'Süre', true], [3400, $sureMetni, false],
            [1700, 'Eğitimciler', true], [2600, $egitimciler, false],
        ]);

        $bolum->addTextBreak(1, ['size' => 2]);

        $secim = $bolum->addTable($kunyeStil);
        $satir($secim, [
            [1700, 'Eğitim Türü', true],
            [3400, $tik(! $tekrarMi).' İlk Defa    '.$tik($tekrarMi).' Tekrar (Yenileme)', false],
            [1700, 'Eğitim Şekli', true],
            [2600, $tik($sekil === 'yuz_yuze').' Yüz Yüze  '.$tik($sekil === 'uzaktan').' Uzaktan  '.$tik($sekil === 'karma').' Karma', false],
        ]);

        $bolum->addTextBreak(1, ['size' => 4]);

        if (($icerik['tip'] ?? null) === 'genel') {
            $grid = $bolum->addTable(['cellMargin' => 60]);
            $grid->addRow();

            $sol = $grid->addCell(4800);
            self::wordKonuBlok($sol, 'Genel Konular', $icerik['genel_konular'] ?? []);
            $sol->addTextBreak();
            self::wordKonuBlok($sol, 'Sağlık Konuları', $icerik['saglik_konulari'] ?? []);

            $sag = $grid->addCell(4800);
            self::wordKonuBlok($sag, 'Teknik Konular', $icerik['teknik_konular'] ?? []);
            $sag->addTextBreak();
            if ($icerik['isyerine_ozgu'] ?? null) {
                self::wordKonuBlok($sag, 'İşyerine Özgü Riskler — '.$icerik['isyerine_ozgu']['sektor'], $icerik['isyerine_ozgu']['maddeler'] ?? []);
            } else {
                $sag->addText('İşyerine Özgü Riskler', ['bold' => true, 'size' => 8]);
                $sag->addText('Sektör seçilmedi.', ['size' => 7.5, 'color' => '888888']);
            }
        } else {
            self::wordKonuBlok($bolum, $icerik['ad'] ?? $kayit->basliklarEtiketi(), $icerik['maddeler'] ?? []);
        }

        $bolum->addTextBreak(1, ['size' => 4]);
        $bolum->addText('Katılımcı Listesi ve İmzaları', ['bold' => true, 'size' => 9], ['spaceAfter' => 40]);

        $basliklar = $ikiGun
            ? [['#', 500], ['Ad Soyad', 2600], ['T.C. No', 1300], ['Görevi', 1500], ['İmza (1. Gün)', 1300], ['İmza (2. Gün)', 1300], ['Ön Test', 700], ['Son Test', 700]]
            : [['#', 500], ['Ad Soyad', 3300], ['T.C. No', 1400], ['Görevi', 1800], ['İmza', 1600], ['Ön Test', 700], ['Son Test', 700]];

        $tablo = $bolum->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 50]);
        $tablo->addRow(null, ['tblHeader' => true]);
        foreach ($basliklar as [$etiket, $genislik]) {
            $tablo->addCell($genislik, ['bgColor' => self::BASLIK_ARKA])->addText($etiket, ['bold' => true, 'size' => 7.5]);
        }

        $katilimcilar = $kayit->katilimcilar ?? [];
        $satirSayisi = max(count($katilimcilar), 24);
        $digerSutunSayisi = count($basliklar) - 4;   // # / Ad Soyad / T.C. / Görevi hariç kalan boş sütunlar

        for ($i = 0; $i < $satirSayisi; $i++) {
            $k = $katilimcilar[$i] ?? null;
            $tablo->addRow(260);
            $tablo->addCell($basliklar[0][1])->addText((string) ($i + 1), ['size' => 8]);
            $tablo->addCell($basliklar[1][1])->addText((string) ($k['ad_soyad'] ?? ''), ['size' => 8]);
            $tablo->addCell($basliklar[2][1])->addText((string) ($k['tc'] ?? ''), ['size' => 8]);
            $tablo->addCell($basliklar[3][1])->addText((string) ($k['gorev'] ?? ''), ['size' => 8]);

            for ($s = 0; $s < $digerSutunSayisi; $s++) {
                $tablo->addCell($basliklar[4 + $s][1])->addText('');
            }
        }

        if ($kayit->isg_uzmani_var || $kayit->isyeri_hekimi_var) {
            $bolum->addTextBreak(1, ['size' => 4]);
            $imzaTablo = $bolum->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 80]);
            $imzaTablo->addRow();

            if ($kayit->isg_uzmani_var) {
                self::wordImzaHucresi($imzaTablo, 'Eğitimi Veren — İş Güvenliği Uzmanı', $imzali ? $kayit->isg_uzmani_kase : null);
            }
            if ($kayit->isyeri_hekimi_var) {
                self::wordImzaHucresi($imzaTablo, 'Eğitimi Veren — İşyeri Hekimi', $imzali ? $kayit->isyeri_hekimi_kase : null);
            }

            $bolum->addText('6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.', ['italic' => true, 'size' => 6.5, 'color' => '666666'], ['alignment' => Jc::CENTER, 'spaceBefore' => 60]);
        } else {
            $bolum->addText('6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.', ['italic' => true, 'size' => 6.5, 'color' => '666666'], ['alignment' => Jc::CENTER, 'spaceBefore' => 60]);
        }
    }

    /** Konu başlığı + süresi ve dahil edilen maddeler, Section'a veya grid hücresine. */
    private static function wordKonuBlok(AbstractContainer $konteyner, string $baslik, array $maddeler): void
    {
        $sure = EgitimIcerikOlusturucu::bolumSuresi($maddeler);
        $konteyner->addText("{$baslik} ({$sure['fiili']}+{$sure['dinlenme']} dk)", ['bold' => true, 'size' => 8], ['spaceAfter' => 40]);

        foreach (collect($maddeler)->where('dahil', true)->values() as $m) {
            $konteyner->addText('• '.($m['madde'] ?? '').' ('.($m['dakika'] ?? 0).' dk)', ['size' => 7.5], ['spaceAfter' => 10]);
        }
    }

    /** Eğitmen kaşe/imza hücresi — kaşe yoksa/imzasızsa boş bırakılır (matbu form). */
    private static function wordImzaHucresi(\PhpOffice\PhpWord\Element\Table $tablo, string $rol, ?string $kaseYolu): void
    {
        $hucre = $tablo->addCell(4800);
        $hucre->addText($rol, ['bold' => true, 'size' => 8]);

        $tamYol = $kaseYolu ? storage_path('app/public/'.$kaseYolu) : null;

        if ($tamYol && is_file($tamYol)) {
            $hucre->addImage($tamYol, ['height' => 32, 'alignment' => Jc::CENTER]);
        } else {
            $hucre->addTextBreak();
        }

        $hucre->addText('Kaşe / İmza', ['size' => 7, 'color' => '666666']);
    }

    private static function wordIndir(PhpWord $phpWord, string $ad): StreamedResponse
    {
        $gecici = tempnam(sys_get_temp_dir(), 'mehse-egt').'.docx';
        WordIO::createWriter($phpWord, 'Word2007')->save($gecici);

        return response()->streamDownload(function () use ($gecici) {
            echo file_get_contents($gecici);
            @unlink($gecici);
        }, $ad);
    }
}
