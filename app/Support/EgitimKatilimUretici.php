<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu PDF ve Excel üretimi. isgpratik EĞİTİM ekranları.
 * Klasik düzen: konular → katılımcı imza listesi → eğitmenler formun ALTINDA;
 * 10 kişilik form tek A4 sayfaya sığar (küçük punto, sıkı düzen).
 */
class EgitimKatilimUretici
{
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
    public static function bosFormPdf(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi, string $egitimTuru): StreamedResponse
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

        return static::pdfCikti($kayit, null, $icerik, $ad);
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
    public static function excel(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');
        ExcelBellek::artir();

        $icerik = $kayit->konu_secimleri ?? [];
        $ikiGun = ($kayit->sure_gun ?? 1) >= 2;

        $tarihler = collect($kayit->gun_tarihleri ?? [])->filter()
            ->map(fn ($t) => \Illuminate\Support\Carbon::parse($t)->format('d.m.Y'))->values();
        if ($tarihler->isEmpty() && $kayit->belge_tarihi) {
            $tarihler = collect([$kayit->belge_tarihi->format('d.m.Y')]);
        }

        $kitap = IOFactory::load(resource_path(self::EXCEL_SABLONU));
        $s = $kitap->getSheet(0);

        $s->setCellValue('F26', $kayit->firma?->unvan ?? '');
        if ($tarihler->isNotEmpty()) {
            $s->setCellValue('L4', $tarihler->implode(' - '));
        }
        if (filled($kayit->egitim_yeri)) {
            $s->setCellValue('F4', $kayit->egitim_yeri);
        }
        if ($saat = $icerik['saat'] ?? null) {
            $s->setCellValue('L26', "Eğitim Süresi: / {$saat} Ders Saati");
            $s->setCellValue('M26', "Eğitim Süresi: / {$saat} Ders Saati");
        }
        $s->getColumnDimension('M')->setVisible($ikiGun);
        if ($ikiGun) {
            // Şablonda L26 ve M26 aynı metni taşır (M gizliyken tek görünür); 2 günde tek hücre olsun.
            $s->setCellValue('M26', null);
            $s->mergeCells('L26:M26');
        }

        $katilimcilar = array_values($kayit->katilimcilar ?? []);
        $ilk = self::KATILIMCI_ILK_SATIR;
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
}
