<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Görevlendirme (atama) yazısı Word (.docx) üretimi. PDF ile aynı içerik
 * (aciklama/legal boilerplate metni YOK — yalnız resmi görevlendirme
 * bilgisi), üye başına ayrı sayfa.
 */
class AtamaYazisiWordUretici
{
    public static function docx(AtamaYazisi $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');
        $firma = $kayit->firma;
        $rol = $kayit->rol();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $uyeler = $kayit->uyeler ?: [];

        if (! $uyeler) {
            $uyeler = [['ad_soyad' => '—', 'tc' => null, 'gorev' => null, 'bas_uye' => false]];
        }

        foreach ($uyeler as $u) {
            $section = $phpWord->addSection();

            $section->addText(strtoupper(($rol['ad'] ?? $kayit->rolEtiketi()).' Görevlendirme Yazısı'), ['bold' => true, 'size' => 15], ['alignment' => Jc::CENTER]);
            $section->addText($firma?->unvan, ['size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

            $tablo = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'width' => 100 * 50, 'unit' => 'pct']);

            $satir = function ($tablo, string $etiket, ?string $deger) {
                $tablo->addRow();
                $tablo->addCell(2500, ['bgColor' => 'F0F0F0'])->addText($etiket, ['bold' => true, 'size' => 9]);
                $tablo->addCell(7500)->addText($deger ?: '—', ['size' => 9]);
            };

            $satir($tablo, 'Doküman No', $kayit->dokuman_no);
            $satir($tablo, 'Tarih', $kayit->tarih?->format('d.m.Y'));
            $satir($tablo, 'Ad Soyad', $u['ad_soyad'] ?? null);
            $satir($tablo, 'T.C. Kimlik No', $u['tc'] ?? null);
            $satir($tablo, 'Görev / Unvan', $u['gorev'] ?? null);
            $satir($tablo, 'İşveren / İşveren Vekili', $kayit->isveren_vekili_adi ?: ($firma?->isveren_vekili ?: $firma?->isveren_ad));

            if ($kayit->gorev_baslangic) {
                $satir($tablo, 'Görev Başlangıç', $kayit->gorev_baslangic->format('d.m.Y'));
                $satir($tablo, 'Görev Bitiş', $kayit->gorev_bitis?->format('d.m.Y') ?: 'Belirsiz süreli');
            }

            $section->addTextBreak(1);

            $govde = sprintf(
                '%s işyerinde görevli %s, %s tarihinden itibaren %s olarak görevlendirilmiştir.',
                $firma?->unvan,
                $u['ad_soyad'] ?? '',
                $kayit->tarih?->format('d.m.Y'),
                $rol['ad'] ?? $kayit->rolEtiketi()
            );

            if ($u['bas_uye'] ?? false) {
                $govde .= ' Adı geçen personel ekibin baş üyesi olarak görevlendirilmiştir.';
            }

            $section->addText($govde, ['size' => 11], ['alignment' => Jc::BOTH, 'lineHeight' => 1.4]);

            $section->addTextBreak(4);

            $imzaTablo = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
            $imzaTablo->addRow();
            $imzaTablo->addCell(5000)->addText('İşveren / İşveren Vekili (İmza – Kaşe)', ['size' => 9], ['alignment' => Jc::CENTER]);
            $imzaTablo->addCell(5000)->addText(($u['ad_soyad'] ?? '')."\n(Görevlendirilen Personel – İmza)", ['size' => 9], ['alignment' => Jc::CENTER]);

            $section->addTextBreak(1);
            $section->addText('6331 Sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili yönetmelikler uyarınca düzenlenmiştir.', ['size' => 7, 'color' => '666666']);
        }

        $ad = 'atama-yazisi-'.Str::slug($kayit->rolEtiketi()).'-'.Str::slug($firma?->unvan ?? 'firma').'.docx';

        return response()->streamDownload(function () use ($phpWord) {
            $phpWord->save('php://output', 'Word2007');
        }, $ad);
    }
}
