<?php

namespace App\Support;

use App\Models\MuayeneFormu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Muayene Formu (EK-2), KVKK'lı İşveren Hekim Kanaat Belgesi ve Çoklu EK-2
 * toplu PDF üretimi (dompdf; TR ve TR/EN çift dil destekli).
 */
class MuayeneFormuUretici
{
    /**
     * @param  bool  $imzali  false = imzasız (matbu): kaşe/imza görselleri basılmaz
     * @param  string  $dil  'tr' | 'tr_en'
     */
    public static function pdf(MuayeneFormu $m, bool $imzali = true, ?string $canliImza = null, string $dil = 'tr'): StreamedResponse
    {
        $m->loadMissing('firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.muayene-formu', [
            'imzali' => $imzali,
            'form' => $m,
            'firma' => $m->firma,
            'canliImza' => $canliImza,
            'dil' => $dil === 'tr_en' ? 'tr_en' : 'tr',
        ])->setPaper('a4');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $canvas->text(485, 822, "Sayfa {$pageNumber} / {$pageCount}", $font, 7.5, [0.35, 0.4, 0.48]);
        });

        $ek = $dil === 'tr_en' ? '-tr-en' : '';
        $ad = 'muayene-formu-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').$ek.'.pdf';

        return response()->streamDownload(fn () => print ($dompdf->output()), $ad);
    }

    /**
     * KVKK & 6331 m.15/5 uyumlu İşveren Hekim Kanaat PDF'i — tıbbi anamnez ve
     * laboratuvar bulguları gizli kalır, yalnızca işe uygunluk kanaati basılır.
     */
    public static function kanaatPdf(MuayeneFormu $m, bool $imzali = true, string $dil = 'tr'): StreamedResponse
    {
        $m->loadMissing('firma.isyeriHekimi');

        $pdf = Pdf::loadView('pdf.hekim-kanaat', [
            'imzali' => $imzali,
            'form' => $m,
            'firma' => $m->firma,
            'dil' => $dil === 'tr_en' ? 'tr_en' : 'tr',
        ])->setPaper('a4');

        $ek = $dil === 'tr_en' ? '-tr-en' : '';
        $ad = 'hekim-kanaat-'.Str::slug($m->calisan_ad_soyad ?: 'calisan').$ek.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * Çoklu EK-2 Toplu PDF — seçilen birden fazla muayene formunu tek PDF'te birleştirir.
     *
     * @param  Collection<int, MuayeneFormu>  $formlar
     */
    public static function topluPdf(Collection $formlar, bool $imzali = true, string $dil = 'tr'): StreamedResponse
    {
        $dil = $dil === 'tr_en' ? 'tr_en' : 'tr';
        $stil = '';
        $govdeler = [];

        foreach ($formlar->values() as $m) {
            $m->loadMissing('firma.isyeriHekimi');
            $html = view('pdf.muayene-formu', [
                'imzali' => $imzali,
                'form' => $m,
                'firma' => $m->firma,
                'canliImza' => null,
                'dil' => $dil,
            ])->render();

            if ($stil === '' && preg_match('/<style>([\s\S]*?)<\/style>/i', $html, $sm)) {
                $stil = $sm[1];
            }
            if (preg_match('/<body>([\s\S]*?)<\/body>/i', $html, $bm)) {
                $govdeler[] = $bm[1];
            }
        }

        $parcalar = [];
        $son = count($govdeler) - 1;
        foreach ($govdeler as $i => $g) {
            $kirilim = $i < $son ? 'page-break-after:always;' : '';
            $parcalar[] = "<div style=\"{$kirilim}\">{$g}</div>";
        }

        $tamHtml = '<!doctype html><html lang="tr"><head><meta charset="utf-8"><style>'.$stil.'</style></head><body>'
            .implode("\n", $parcalar)
            .'</body></html>';

        $pdf = Pdf::loadHTML($tamHtml)->setPaper('a4');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $canvas->text(485, 822, "Sayfa {$pageNumber} / {$pageCount}", $font, 7.5, [0.35, 0.4, 0.48]);
        });

        $ilkFirma = $formlar->first()?->firma?->unvan ?: 'firma';
        $ad = 'coklu-ek2-muayene-'.Str::slug($ilkFirma).'-'.count($govdeler).'-kisi.pdf';

        return response()->streamDownload(fn () => print ($dompdf->output()), $ad);
    }
}
