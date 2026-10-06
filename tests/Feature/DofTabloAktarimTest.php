<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\User;
use App\Support\DofTabloOkuyucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DofTabloAktarimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function wordDosyasi(): string
    {
        $word = new PhpWord;
        $bolum = $word->addSection();
        $bolum->addText('ÇOKLU DÖF RAPORU');

        $bilgi = $bolum->addTable();
        $bilgi->addRow();
        $bilgi->addCell()->addText('Alan / Bölge');
        $bilgi->addCell()->addText('Mekanik Bakım');
        $bilgi->addCell()->addText('Gözetim Tarihi');
        $bilgi->addCell()->addText('06.10.2026');
        $bilgi->addRow();
        $bilgi->addCell()->addText('Gözetim Yapan');
        $bilgi->addCell()->addText('Uzman Adı');
        $bilgi->addCell()->addText('Sertifika No');
        $bilgi->addCell()->addText('405044');

        $t = $bolum->addTable();
        $t->addRow();
        foreach (['#', 'Tespit Edilen Uygunsuzluk', 'Öncelik', 'Öneri / Düzeltici Faaliyet', 'Sorumlu', 'Termin', 'Durum', 'Foto'] as $b) {
            $t->addCell()->addText($b);
        }
        foreach ([
            ['1', 'Torna aynası muhafazasız', 'Yüksek', ['1- Muhafaza takılmalı.', '2- Talaş toplanmalı.'], 'İşveren', '13.10.2026', 'Açık'],
            ['2', 'Merdane ezilme noktası', 'Çok Yüksek', ['Tünel muhafaza'], 'İşveren Vekili', '09.10.2026', 'Devam Ediyor'],
        ] as $s) {
            $t->addRow();
            $t->addCell()->addText($s[0]);
            $t->addCell()->addText($s[1]);
            $t->addCell()->addText($s[2]);
            $hucre = $t->addCell();
            foreach ($s[3] as $p) {
                $hucre->addText($p);
            }
            $t->addCell()->addText($s[4]);
            $t->addCell()->addText($s[5]);
            $t->addCell()->addText($s[6]);
            $t->addCell()->addText('Foto');
        }

        $yol = tempnam(sys_get_temp_dir(), 'dof').'.docx';
        WordIO::createWriter($word, 'Word2007')->save($yol);

        return $yol;
    }

    public function test_word_tablosu_maddelere_ve_ust_bilgilere_cozulur(): void
    {
        $sonuc = DofTabloOkuyucu::oku($this->wordDosyasi());

        $this->assertSame('Mekanik Bakım', $sonuc['bilgi']['alanBolge']);
        $this->assertSame('06.10.2026', $sonuc['bilgi']['gozetimTarihAraligi']);
        $this->assertSame('Uzman Adı', $sonuc['bilgi']['gozetimYapan']);
        $this->assertSame('405044', $sonuc['bilgi']['gozetimYapanSertifikaNo']);

        $this->assertCount(2, $sonuc['maddeler']);
        [$m1, $m2] = $sonuc['maddeler'];
        $this->assertSame('Torna aynası muhafazasız', $m1['tespit']);
        $this->assertSame('yuksek', $m1['oncelik']);
        $this->assertSame("1- Muhafaza takılmalı.\n2- Talaş toplanmalı.", $m1['oneri']);
        $this->assertSame('2026-10-13', $m1['termin']);
        $this->assertSame('acik', $m1['durum']);
        $this->assertSame('kritik', $m2['oncelik']);
        $this->assertSame('devam_ediyor', $m2['durum']);
    }

    public function test_excel_tablosu_sutun_sirasi_farkli_olsa_da_okunur(): void
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->fromArray([
            ['DÖF TAKİP LİSTESİ'],
            [],
            ['Sıra', 'Öneri / Düzeltici Faaliyet', 'Tespit', 'Termin', 'Öncelik', 'Sorumlu'],
            [1, 'Kablolar kanala alınmalı', 'Zeminde serbest kablo', null, 'Düşük', 'Bakım şefi'],
            [2, 'Tüp zincirle sabitlenmeli', 'Gaz tüpü sabitlenmemiş', '20.10.2026', 'Orta', null],
            [null, null, null, null, null, null],
        ]);
        $s->setCellValue('D4', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2026-10-15')));
        $s->getStyle('D4')->getNumberFormat()->setFormatCode('dd.mm.yyyy');

        $yol = tempnam(sys_get_temp_dir(), 'dof').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $m = DofTabloOkuyucu::oku($yol)['maddeler'];

        $this->assertCount(2, $m);
        $this->assertSame('Zeminde serbest kablo', $m[0]['tespit']);
        $this->assertSame('Kablolar kanala alınmalı', $m[0]['oneri']);
        $this->assertSame('dusuk', $m[0]['oncelik']);
        $this->assertSame('2026-10-15', $m[0]['termin']);
        $this->assertSame('Bakım şefi', $m[0]['sorumlu']);
        $this->assertSame('2026-10-20', $m[1]['termin']);
        $this->assertNull($m[1]['sorumlu']);
    }

    public function test_sayfadan_aktarilir_fotograf_eklenir_ve_dof_raporu_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(DofOlustur::class)
            ->set('firmaId', $firma->id)
            ->callAction('tablodanAktar', data: [
                'dosya' => UploadedFile::fake()->createWithContent('dof.docx', file_get_contents($this->wordDosyasi())),
            ])
            ->assertHasNoActionErrors()
            ->assertSet('alanBolge', 'Mekanik Bakım')
            ->assertSet('gozetimYapan', 'Uzman Adı')
            ->assertCount('maddeler', 2)
            ->assertSee('Torna aynası muhafazasız')
            ->set('maddeFotolari.1', UploadedFile::fake()->image('saha.jpg'));

        $foto = $sayfa->get('maddeler')[1]['foto_yolu'];
        $this->assertNotNull($foto);
        Storage::disk('public')->assertExists($foto);

        $sayfa->callAction('pdf')->assertHasNoActionErrors();

        $rapor = DofRaporu::sole();
        $this->assertSame('Mekanik Bakım', $rapor->alan_bolge);
        $this->assertCount(2, $rapor->maddeler);
        $this->assertSame($foto, $rapor->maddeler[1]['foto_yolu']);
    }

    public function test_tablo_olmayan_dosya_madde_eklemez(): void
    {
        $word = new PhpWord;
        $word->addSection()->addText('Sadece düz metin');
        $yol = tempnam(sys_get_temp_dir(), 'dof').'.docx';
        WordIO::createWriter($word, 'Word2007')->save($yol);

        Livewire::test(DofOlustur::class)
            ->callAction('tablodanAktar', data: [
                'dosya' => UploadedFile::fake()->createWithContent('bos.docx', file_get_contents($yol)),
            ])
            ->assertCount('maddeler', 0)
            ->assertNotified('Dosyada DÖF tablosu bulunamadı');
    }
}
