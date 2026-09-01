<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Models\Firma;
use App\Models\User;
use App\Support\FirmaExcelIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FirmaExcelIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** @param  array<int, array<int, mixed>>  $satirlar */
    private function xlsxOlustur(array $satirlar): string
    {
        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();

        foreach ($satirlar as $i => $satir) {
            $sayfa->fromArray($satir, null, 'A'.($i + 1));
        }

        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_gecerli_satirlar_ice_aktarilir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Unvan', 'Kısa Ad', 'Tehlike Sınıfı', 'Çalışan Sayısı', 'Sözleşme Başlangıç'],
            ['Örnek A.Ş.', 'Örnek', 'Çok Tehlikeli', 25, '2026-01-15'],
            ['İkinci Ltd.', '', 'tehlikeli', 5, ''],
        ]);

        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertEmpty($sonuc['hatalar']);

        $f1 = Firma::where('unvan', 'Örnek A.Ş.')->firstOrFail();
        $this->assertSame('cok_tehlikeli', $f1->tehlike_sinifi);
        $this->assertSame(25, $f1->calisan_sayisi);
        $this->assertSame('2026-01-15', $f1->sozlesme_baslangic->toDateString());
        $this->assertSame($this->uzman->id, $f1->user_id);

        $f2 = Firma::where('unvan', 'İkinci Ltd.')->firstOrFail();
        $this->assertSame('tehlikeli', $f2->tehlike_sinifi);

        unlink($yol);
    }

    public function test_unvan_bos_satir_atlanir_ve_hata_bildirilir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Unvan', 'İl'],
            ['', 'İstanbul'],
            ['Geçerli Firma', 'Ankara'],
        ]);

        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertCount(1, $sonuc['hatalar']);
        $this->assertStringContainsString('Satır 2', $sonuc['hatalar'][0]);

        unlink($yol);
    }

    public function test_bos_satirlar_sessizce_atlanir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Unvan', 'İl'],
            ['', ''],
            ['Geçerli Firma', 'Ankara'],
        ]);

        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_unvan_sutunu_yoksa_hata_doner(): void
    {
        $yol = $this->xlsxOlustur([
            ['İl', 'Telefon'],
            ['İstanbul', '5551234567'],
        ]);

        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_bilinmeyen_tehlike_sinifi_varsayilana_duser(): void
    {
        $yol = $this->xlsxOlustur([
            ['Unvan', 'Tehlike Sınıfı'],
            ['Firma', 'garip-deger'],
        ]);

        FirmaExcelIceAktarici::iceAktar($yol, $this->uzman->id);

        $this->assertSame('az_tehlikeli', Firma::where('unvan', 'Firma')->firstOrFail()->tehlike_sinifi);

        unlink($yol);
    }

    public function test_sablon_indirilebilir(): void
    {
        $yanit = FirmaExcelIceAktarici::sablonIndir();

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertNotEmpty($icerik);
        $this->assertStringStartsWith('PK', $icerik); // xlsx = zip konteyneri
    }

    public function test_sayfa_excel_aksiyonuyla_ice_aktarir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Unvan'],
            ['Livewire Firma'],
        ]);

        Livewire::test(ListFirmas::class)
            ->callAction('excelYukle', data: [
                'dosya' => UploadedFile::fake()->createWithContent('firmalar.xlsx', file_get_contents($yol)),
            ]);

        $this->assertDatabaseHas('firmalar', ['unvan' => 'Livewire Firma', 'user_id' => $this->uzman->id]);

        unlink($yol);
    }
}
