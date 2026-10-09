<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\Talimat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firmaya tanımlı çalışma talimatlarının listesi (PDF / Excel) — "bu firmaya
 * hangi talimatları verdim" sorusu için (kullanıcı isteği 09.10.2026).
 * PDF'te istenirse her talimatın maddeleri ek olarak basılır.
 */
class TalimatListesiUretici
{
    /** @return Collection<int, Talimat> kategori, sonra başlık sırasıyla */
    public static function talimatlar(Firma $firma): Collection
    {
        return $firma->talimatlar()->get()
            ->sortBy(fn (Talimat $t) => [$t->kategoriEtiketi(), mb_strtolower($t->baslik)])
            ->values();
    }

    /** "12 madde" / "Yüklenen dosya (Word)" */
    public static function icerikEtiketi(Talimat $t): string
    {
        if ($t->dosyaVarMi()) {
            $uzanti = strtolower(pathinfo((string) $t->dosya_adi, PATHINFO_EXTENSION));

            return 'Yüklenen dosya ('.($uzanti === 'pdf' ? 'PDF' : 'Word').')';
        }

        return count($t->maddeler ?? []).' madde';
    }

    public static function pdf(Firma $firma, bool $maddelerDahil = false): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.talimat-listesi', [
            'firma' => $firma,
            'talimatlar' => self::talimatlar($firma),
            'maddelerDahil' => $maddelerDahil,
            'logo' => KurulToplantisiUretici::logoYolu($firma),
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($firma, 'pdf'));
    }

    public static function excel(Firma $firma): StreamedResponse
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Talimat Listesi');

        $s->setCellValue('A1', 'ÇALIŞMA TALİMATLARI LİSTESİ');
        $s->mergeCells('A1:G1');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $s->setCellValue('A2', $firma->unvan.' · '.now()->format('d.m.Y'));
        $s->mergeCells('A2:G2');
        $s->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $s->fromArray(['#', 'Talimat Adı', 'Kategori', 'Gerekli KKD', 'İçerik', 'Hazırlanma', 'Son Güncelleme'], null, 'A4');
        $s->getStyle('A4:G4')->getFont()->setBold(true);
        $s->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');

        $satir = 5;
        foreach (self::talimatlar($firma) as $i => $t) {
            $s->fromArray([
                $i + 1,
                $t->baslik,
                $t->kategoriEtiketi() ?: '—',
                implode(', ', $t->kkdler ?? []) ?: '—',
                self::icerikEtiketi($t),
                $t->created_at?->format('d.m.Y'),
                $t->updated_at?->format('d.m.Y'),
            ], null, "A{$satir}");
            $satir++;
        }

        $son = max(4, $satir - 1);
        $s->getStyle("A4:G{$son}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $s->getStyle("B5:D{$son}")->getAlignment()->setWrapText(true);
        $s->getStyle("A5:G{$son}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        foreach (['A' => 5, 'B' => 45, 'C' => 22, 'D' => 35, 'E' => 20, 'F' => 13, 'G' => 15] as $sutun => $genislik) {
            $s->getColumnDimension($sutun)->setWidth($genislik);
        }
        $s->setAutoFilter("A4:G{$son}");
        $s->freezePane('A5');

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(fn () => $yazici->save('php://output'), self::dosyaAdi($firma, 'xlsx'));
    }

    private static function dosyaAdi(Firma $firma, string $uzanti): string
    {
        return 'talimat-listesi-'.Str::slug($firma->unvan).'.'.$uzanti;
    }
}
