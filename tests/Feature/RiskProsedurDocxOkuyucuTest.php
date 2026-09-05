<?php

namespace Tests\Feature;

use App\Support\RiskProsedurDocxOkuyucu;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class RiskProsedurDocxOkuyucuTest extends TestCase
{
    public function test_baslik_ve_paragraflari_ayirt_eder(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('AMAÇ');
        $section->addText('Bu prosedürün amacı işyerindeki tehlikeleri belirlemek ve riskleri değerlendirmektir.');
        $section->addText('KAPSAM');
        $section->addText('Tüm faaliyetleri kapsar.');

        $yol = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($yol);

        $bloklar = RiskProsedurDocxOkuyucu::oku($yol);
        unlink($yol);

        $this->assertSame('baslik', $bloklar[0]['tip']);
        $this->assertSame('AMAÇ', $bloklar[0]['metin']);
        $this->assertSame('paragraf', $bloklar[1]['tip']);
        $this->assertStringContainsString('tehlikeleri belirlemek', $bloklar[1]['metin']);
        $this->assertSame('baslik', $bloklar[2]['tip']);
        $this->assertSame('KAPSAM', $bloklar[2]['metin']);
    }

    public function test_bos_belge_bos_dizi_dondurur(): void
    {
        $phpWord = new PhpWord();
        $phpWord->addSection();

        $yol = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($yol);

        $bloklar = RiskProsedurDocxOkuyucu::oku($yol);
        unlink($yol);

        $this->assertSame([], $bloklar);
    }
}
