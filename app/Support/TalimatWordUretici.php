<?php

namespace App\Support;

use App\Models\Talimat;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\ListItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Çalışma Talimatı Word (.docx) çıktısı — `pdf.talimat` (dompdf) ile aynı
 * içerik. Talimatın madde sayısı firmadan firmaya değiştiğinden (Acil Durum
 * Planı/Atama Yazıları gibi SABİT alanlı belgelerin aksine — bkz.
 * `AcilDurumWordUretici`/`AtamaYazisiWordUretici`, gerçek referans .docx
 * şablonu üzerinde alan değiştirir), burada sabit bir şablon yerine içerik
 * doğrudan `phpoffice/phpword` ile programatik üretilir.
 */
class TalimatWordUretici
{
    public static function word(Talimat $talimat): StreamedResponse
    {
        $talimat->loadMissing('firma');

        $phpWord = new PhpWord;
        $bolum = $phpWord->addSection();

        $bolum->addText($talimat->baslik, ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $bolum->addText($talimat->firma?->unvan, ['size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        $kunye = $bolum->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        $kunye->addRow();
        $kunye->addCell(2500, ['bgColor' => 'F0F0F0'])->addText('Kategori', ['bold' => true, 'size' => 10]);
        $kunye->addCell(7000)->addText($talimat->kategoriEtiketi(), ['size' => 10]);

        if ($talimat->aciklama) {
            $bolum->addTextBreak();
            $bolum->addText($talimat->aciklama, ['italic' => true, 'size' => 11, 'color' => '444444']);
        }

        if ($talimat->kkdler) {
            $bolum->addTextBreak();
            $bolum->addText('Gerekli KKD\'ler: '.implode(', ', $talimat->kkdler), ['bold' => true, 'size' => 10]);
        }

        $bolum->addTextBreak();

        foreach (($talimat->maddeler ?: ['Madde eklenmedi.']) as $madde) {
            $bolum->addListItem($madde, 0, ['size' => 11], ['listType' => ListItem::TYPE_NUMBER]);
        }

        $bolum->addTextBreak(2);

        $imza = $bolum->addTable(['cellMargin' => 80]);
        $imza->addRow();
        $imza->addCell(4750)->addText("İş Güvenliği Uzmanı\n(İmza – Kaşe)", ['size' => 10], ['alignment' => Jc::CENTER]);
        $imza->addCell(4750)->addText("Çalışan\n(Okudum, Anladım – İmza)", ['size' => 10], ['alignment' => Jc::CENTER]);

        $bolum->addTextBreak();
        $bolum->addText(
            '6331 sayılı İş Sağlığı ve Güvenliği Kanunu uyarınca işveren, çalışana yapacağı iş ve '.
            'kullanacağı ekipmanla ilgili güvenli çalışma talimatı vermekle yükümlüdür.',
            ['size' => 8, 'italic' => true, 'color' => '666666']
        );

        $geciciYol = tempnam(sys_get_temp_dir(), 'mehse-talimat').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($geciciYol);

        $ad = 'talimat-'.Str::slug($talimat->baslik).'.docx';

        return response()->streamDownload(function () use ($geciciYol) {
            echo file_get_contents($geciciYol);
            @unlink($geciciYol);
        }, $ad);
    }
}
