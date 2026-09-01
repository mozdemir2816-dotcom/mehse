<?php

namespace Tests\Feature;

use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\TehlikeExcelIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TehlikeExcelIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
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

    public function test_yeni_kategori_otomatik_olusturulur_ve_tehlike_eklenir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Kategori', 'Bölüm', 'Faaliyet', 'Tehlike', 'Risk', 'Mevcut Önlem', 'Mevzuat'],
            ['Kazı Çalışmaları', 'Şantiye', 'Kazı', 'İksasız derin kazı', 'Göçük altında kalma', 'Şev açısı hesabı yapılır.', 'Yapı İşlerinde İSG Yön.'],
        ]);

        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertSame(1, $sonuc['yeniKategori']);
        $this->assertEmpty($sonuc['hatalar']);

        $kategori = TehlikeKategorisi::where('anahtar', 'kazi_calismalari')->firstOrFail();
        $this->assertSame('Kazı Çalışmaları', $kategori->ad);

        $tehlike = Tehlike::where('tehlike_kategorisi_id', $kategori->id)->firstOrFail();
        $this->assertSame('İksasız derin kazı', $tehlike->tehlike);
        $this->assertSame('Göçük altında kalma', $tehlike->risk);

        unlink($yol);
    }

    public function test_mevcut_kategoriye_yeni_tehlike_eklenir_tekrar_yuklenince_guncellenir(): void
    {
        $kategori = TehlikeKategorisi::create(['ad' => 'Kalıp İşleri', 'anahtar' => 'kalip_isleri']);

        $yol = $this->xlsxOlustur([
            ['Kategori', 'Tehlike', 'Risk'],
            ['Kalıp İşleri', 'Kalıp montajında düşme', 'Yaralanma'],
        ]);
        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);
        $this->assertSame(1, $sonuc['basarili']);
        $this->assertSame(0, $sonuc['yeniKategori']); // kategori zaten vardı
        unlink($yol);

        // ayni tehlikeyi guncelleyerek tekrar yukle -- mukerrer olusturmamali
        $yol2 = $this->xlsxOlustur([
            ['Kategori', 'Tehlike', 'Risk'],
            ['Kalıp İşleri', 'Kalıp montajında düşme', 'Ciddi yaralanma (güncellendi)'],
        ]);
        TehlikeExcelIceAktarici::iceAktar($yol2);
        unlink($yol2);

        $this->assertSame(1, Tehlike::where('tehlike_kategorisi_id', $kategori->id)->count());
        $this->assertSame('Ciddi yaralanma (güncellendi)', Tehlike::first()->risk);
    }

    public function test_tehlike_bos_satir_atlanir_ve_hata_bildirilir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Kategori', 'Tehlike'],
            ['Metal', ''],
            ['Metal', 'Geçerli tehlike'],
        ]);

        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertCount(1, $sonuc['hatalar']);
        $this->assertStringContainsString('Satır 2', $sonuc['hatalar'][0]);

        unlink($yol);
    }

    public function test_kategori_sutunu_yoksa_hata_doner(): void
    {
        $yol = $this->xlsxOlustur([
            ['Bölüm', 'Tehlike'],
            ['Şantiye', 'Test'],
        ]);

        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_sablon_indirilebilir(): void
    {
        $yanit = TehlikeExcelIceAktarici::sablonIndir();

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('PK', $icerik);
    }

    public function test_sayfa_excel_aksiyonuyla_ice_aktarir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Kategori', 'Tehlike'],
            ['Zemin İyileştirme', 'Yetersiz zemin etüdü'],
        ]);

        Livewire::test(ListTehlikes::class)
            ->callAction('excelYukle', data: [
                'dosya' => UploadedFile::fake()->createWithContent('tehlikeler.xlsx', file_get_contents($yol)),
            ]);

        $this->assertDatabaseHas('tehlikeler', ['tehlike' => 'Yetersiz zemin etüdü']);

        unlink($yol);
    }
}
