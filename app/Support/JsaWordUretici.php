<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\JsaSablonu;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSA (İşe Özgü Risk Değerlendirmesi) Word (.docx) çıktısı — `pdf.jsa` ile aynı
 * içerik. İş adımı sayısı analizden analize değiştiği için sabit bir referans
 * .docx şablonu yerine `phpoffice/phpword` ile programatik üretilir (bkz.
 * `TalimatWordUretici` aynı gerekçe). Şablon bölünmez: tüm adımlar tek tabloda.
 */
class JsaWordUretici
{
    private const BASLIK_ARKA = 'EEEEEE';

    public static function word(JsaSablonu $sablon, ?Firma $firma = null): StreamedResponse
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontSize(8);

        $bolum = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 720, 'marginRight' => 720,
        ]);

        $bolum->addText($sablon->baslik, ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);

        $kunye = array_filter([
            $firma?->unvan,
            $sablon->dokuman_ref ? 'Doküman Ref: '.$sablon->dokuman_ref : null,
            $sablon->revizyon ? 'Rev: '.$sablon->revizyon : null,
            'Tarih: '.($sablon->belge_tarihi ?: now()->format('d.m.Y')),
        ]);
        $bolum->addText(implode('   •   ', $kunye), ['size' => 8, 'color' => '333333'], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);

        if ($sablon->kapsam) {
            $bolum->addText('Kapsam: '.$sablon->kapsam, ['size' => 8, 'italic' => true, 'color' => '444444'], ['spaceAfter' => 120]);
        }

        $tablo = $bolum->addTable([
            'borderSize' => 4, 'borderColor' => '888888', 'cellMargin' => 40, 'width' => 100 * 50, 'unit' => 'pct',
        ]);

        $basliklar = ['#', 'İş Adımı / Faaliyet', 'Olası Tehlikeler', 'Olası Sonuçlar / Riskler', 'Başlangıç Risk', 'Kontrol Tedbirleri (KKD Dahil)', 'Kalıntı Risk', 'Sorumlu'];
        $genislik = [500, 2200, 2600, 2200, 1100, 3400, 1100, 1700];

        $tablo->addRow(null, ['tblHeader' => true]);
        foreach ($basliklar as $i => $b) {
            $tablo->addCell($genislik[$i], ['bgColor' => self::BASLIK_ARKA])
                ->addText($b, ['bold' => true, 'size' => 8]);
        }

        foreach (($sablon->adimlar ?? []) as $a) {
            $tablo->addRow();
            $tablo->addCell($genislik[0])->addText((string) ($a['sira'] ?? ''), ['size' => 8], ['alignment' => Jc::CENTER]);
            $tablo->addCell($genislik[1])->addText((string) ($a['is_adimi'] ?? ''), ['size' => 8]);
            $tablo->addCell($genislik[2])->addText((string) ($a['tehlikeler'] ?? ''), ['size' => 8]);
            $tablo->addCell($genislik[3])->addText((string) ($a['sonuclar'] ?? ''), ['size' => 8]);
            $tablo->addCell($genislik[4])->addText((string) ($a['baslangic_risk'] ?? ''), self::riskStili($a['baslangic_risk'] ?? null), ['alignment' => Jc::CENTER]);
            $tablo->addCell($genislik[5])->addText((string) ($a['kontrol_tedbirleri'] ?? ''), ['size' => 8]);
            $tablo->addCell($genislik[6])->addText((string) ($a['kalinti_risk'] ?? ''), self::riskStili($a['kalinti_risk'] ?? null), ['alignment' => Jc::CENTER]);
            $tablo->addCell($genislik[7])->addText((string) ($a['sorumlu'] ?? ''), ['size' => 8]);
        }

        if ($sablon->notlar) {
            $bolum->addTextBreak();
            $bolum->addText('Notlar ve Ek Gereksinimler', ['bold' => true, 'size' => 10]);
            foreach ($sablon->notlar as $not) {
                $bolum->addText($not, ['size' => 8]);
            }
        }

        $bolum->addTextBreak();
        $bolum->addText('Onay ve İmza', ['bold' => true, 'size' => 10]);

        $imza = $bolum->addTable(['borderSize' => 4, 'borderColor' => '888888', 'cellMargin' => 60]);
        $imza->addRow(null, ['tblHeader' => true]);
        foreach (['Görevi / Rolü', 'Adı Soyadı', 'İmza', 'Tarih'] as $b) {
            $imza->addCell(3500, ['bgColor' => self::BASLIK_ARKA])->addText($b, ['bold' => true, 'size' => 8]);
        }
        // Firma seçiliyse "Hazırlayan" satırı firmaya atanmış İSG Uzmanı + kaşesiyle dolar.
        $uzman = $firma?->igu;

        foreach (($sablon->imza_rolleri ?: JsaSablonu::VARSAYILAN_IMZA_ROLLERI) as $rol) {
            $hazirlayan = $uzman && JsaSablonu::hazirlayanRoluMu($rol['rol'] ?? null);

            $imza->addRow(400);
            $imza->addCell(3500)->addText((string) ($rol['rol'] ?? ''), ['size' => 8]);

            $adHucre = $imza->addCell(3500);
            $adHucre->addText($hazirlayan ? (string) $uzman->ad_soyad : (string) ($rol['ad'] ?? ''), ['size' => 8]);
            if ($hazirlayan && $uzman->unvan) {
                $adHucre->addText((string) $uzman->unvan, ['size' => 7, 'color' => '555555']);
            }

            $imzaHucre = $imza->addCell(3500);
            $kaseYolu = $hazirlayan && $uzman->kase_gorseli
                ? storage_path('app/public/'.$uzman->kase_gorseli)
                : null;
            if ($kaseYolu && is_file($kaseYolu)) {
                $imzaHucre->addImage($kaseYolu, ['height' => 38, 'alignment' => Jc::CENTER]);
            } else {
                $imzaHucre->addText('');
            }

            $imza->addCell(3500)->addText((string) ($rol['tarih'] ?? ''), ['size' => 8]);
        }

        $geciciYol = tempnam(sys_get_temp_dir(), 'mehse-jsa').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($geciciYol);

        $ad = 'jsa-'.Str::slug($sablon->baslik ?: 'ise-ozgu-risk')
            .($firma ? '-'.Str::slug($firma->unvan) : '').'.docx';

        return response()->streamDownload(function () use ($geciciYol) {
            print(file_get_contents($geciciYol));
            @unlink($geciciYol);
        }, $ad);
    }

    /** @return array<string, mixed> */
    private static function riskStili(?string $seviye): array
    {
        $renk = match (JsaSablonu::riskRengi($seviye)) {
            'kritik', 'yuksek' => 'C00000',
            'orta' => 'BF8F00',
            'dusuk' => '2E7D32',
            default => '333333',
        };

        return ['size' => 8, 'bold' => true, 'color' => $renk];
    }
}
