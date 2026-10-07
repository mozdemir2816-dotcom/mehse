<?php

namespace Tests\Feature;

use App\Support\OfficeMimeTahmincisi;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

/**
 * 07.10.2026: Word'ün kaydettiği bir Saha Gözlem Raporu (.docx) fileinfo'da
 * "application/octet-stream" çıktığı için acceptedFileTypes alanlarında
 * reddediliyordu. Office dosyaları ZIP içeriğinden tanınmalı.
 */
class OfficeMimeTahmincisiTest extends TestCase
{
    private function zip(array $girisler): string
    {
        $yol = tempnam(sys_get_temp_dir(), 'ofis').'.zip';
        $zip = new ZipArchive;
        $zip->open($yol, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($girisler as $ad) {
            $zip->addFromString($ad, '<x/>');
        }
        $zip->close();

        return $yol;
    }

    public function test_word_excel_powerpoint_zip_iceriginden_taninir(): void
    {
        $t = new OfficeMimeTahmincisi;
        // Sorunlu dosyadaki giriş sırası: word/ ancak 5. girişte geliyor
        $docx = $this->zip(['[Content_Types].xml', '_rels/.rels', 'docProps/core.xml', 'docProps/app.xml', 'word/document.xml', 'customXml/item1.xml']);
        $xlsx = $this->zip(['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml']);
        $pptx = $this->zip(['[Content_Types].xml', 'ppt/presentation.xml']);
        $zip = $this->zip(['belge.txt']);

        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $t->guessMimeType($docx));
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $t->guessMimeType($xlsx));
        $this->assertSame('application/vnd.openxmlformats-officedocument.presentationml.presentation', $t->guessMimeType($pptx));
        $this->assertNull($t->guessMimeType($zip));   // sıradan ZIP Office sayılmaz

        $png = UploadedFile::fake()->image('a.png');
        $this->assertNull($t->guessMimeType($png->getPathname()));

        array_map('unlink', [$docx, $xlsx, $pptx, $zip]);
    }

    /**
     * FilePond, input accept listesini acceptedFileTypes olarak okuyup dosya
     * türünü (mimeTypeMap[uzantı]) bu listede birebir arar; yalnız uzantı
     * içeren liste her dosyayı tarayıcıda reddediyordu (07.10.2026).
     */
    public function test_tarayici_tur_kontrolu_her_uzanti_icin_eslesir(): void
    {
        $uzantilar = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];
        $kabul = explode(',', \App\Filament\Support\DosyaKabul::accept($uzantilar));

        foreach (\App\Filament\Support\DosyaKabul::harita($uzantilar) as $uzanti => $mime) {
            $this->assertContains($mime, $kabul, $uzanti.' türü kabul listesinde yok');
            $this->assertContains('.'.$uzanti, $kabul);
        }
        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', \App\Filament\Support\DosyaKabul::harita(['docx'])['docx']);
    }

    public function test_dosya_turu_dogrulamasi_word_dosyasini_kabul_eder(): void
    {
        $yol = $this->zip(['[Content_Types].xml', '_rels/.rels', 'docProps/core.xml', 'docProps/app.xml', 'word/document.xml']);
        $dosya = new UploadedFile($yol, 'Saha_Gozlem_Raporu.docx', null, null, true);

        $this->assertSame('docx', $dosya->guessExtension());
        $this->assertFalse(validator(['f' => $dosya], ['f' => 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->fails());
        $this->assertFalse(validator(['f' => $dosya], ['f' => 'mimes:docx'])->fails());

        @unlink($yol);
    }
}
