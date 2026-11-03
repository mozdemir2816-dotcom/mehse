<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\RelationManagers\CalisanlarRelationManager;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\CalisanExcelIceAktarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CalisanExcelIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
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
            ['Ad Soyad', 'T.C. Kimlik No', 'Görevi', 'İşe Giriş', 'Ağır ve Tehlikeli İş', 'Aktif'],
            ['Ahmet Yılmaz', '12345678901', 'Şantiye Şefi', '01.03.2024', 'Evet', 'Evet'],
            ['Ayşe Kaya', '', 'Muhasebeci', '', 'Hayır', 'Evet'],
        ]);

        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertEmpty($sonuc['hatalar']);

        $ahmet = Calisan::where('ad_soyad', 'Ahmet Yılmaz')->firstOrFail();
        $this->assertSame($this->firma->id, $ahmet->firma_id);
        $this->assertSame('12345678901', $ahmet->tc);
        $this->assertSame('2024-03-01', $ahmet->ise_giris->toDateString());
        $this->assertTrue($ahmet->agir_tehlikeli_iste);

        $ayse = Calisan::where('ad_soyad', 'Ayşe Kaya')->firstOrFail();
        $this->assertFalse($ayse->agir_tehlikeli_iste);
        $this->assertTrue($ayse->aktif);

        unlink($yol);
    }

    public function test_tc_ile_tekrar_yuklenince_guncellenir(): void
    {
        $yol1 = $this->xlsxOlustur([
            ['Ad Soyad', 'T.C. Kimlik No', 'Görevi'],
            ['Ahmet Yılmaz', '12345678901', 'İşçi'],
        ]);
        CalisanExcelIceAktarici::iceAktar($yol1, $this->firma->id);
        unlink($yol1);

        $yol2 = $this->xlsxOlustur([
            ['Ad Soyad', 'T.C. Kimlik No', 'Görevi'],
            ['Ahmet Yılmaz', '12345678901', 'Ustabaşı'],
        ]);
        $sonuc = CalisanExcelIceAktarici::iceAktar($yol2, $this->firma->id);
        unlink($yol2);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertSame(1, Calisan::count());
        $this->assertSame('Ustabaşı', Calisan::first()->gorev);
    }

    public function test_ad_soyad_bos_satir_atlanir_ve_hata_bildirilir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Ad Soyad', 'Görevi'],
            ['', 'İşçi'],
            ['Geçerli Kişi', 'İşçi'],
        ]);

        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);

        $this->assertSame(1, $sonuc['basarili']);
        $this->assertCount(1, $sonuc['hatalar']);
        $this->assertStringContainsString('Satır 2', $sonuc['hatalar'][0]);

        unlink($yol);
    }

    public function test_ad_soyad_sutunu_yoksa_hata_doner(): void
    {
        $yol = $this->xlsxOlustur([
            ['Görevi', 'Departman'],
            ['İşçi', 'Üretim'],
        ]);

        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);

        $this->assertSame(0, $sonuc['basarili']);
        $this->assertNotEmpty($sonuc['hatalar']);

        unlink($yol);
    }

    public function test_sablon_indirilebilir(): void
    {
        $yanit = CalisanExcelIceAktarici::sablonIndir();

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('PK', $icerik);
    }

    public function test_relation_manager_excel_aksiyonuyla_ice_aktarir(): void
    {
        $yol = $this->xlsxOlustur([
            ['Ad Soyad'],
            ['Livewire Çalışan'],
        ]);

        Livewire::test(CalisanlarRelationManager::class, [
            'ownerRecord' => $this->firma,
            'pageClass' => EditFirma::class,
        ])
            ->callTableAction('excelYukle', data: [
                'dosya' => UploadedFile::fake()->createWithContent('calisanlar.xlsx', file_get_contents($yol)),
            ]);

        $this->assertDatabaseHas('calisanlar', ['ad_soyad' => 'Livewire Çalışan', 'firma_id' => $this->firma->id]);

        unlink($yol);
    }

    public function test_listede_olmayan_aktif_personel_yalniz_secenek_acikken_pasife_alinir(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Listede Var', 'aktif' => true]);
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Listede Yok', 'aktif' => true]);
        $baskaFirma = Calisan::factory()->create(['ad_soyad' => 'Baska Firma', 'aktif' => true]);

        $yol = $this->xlsxOlustur([['Ad Soyad', 'Görevi'], ['Listede Var', 'Usta'], ['Yeni Kişi', 'İşçi']]);

        // Önizleme yazmaz.
        $o = CalisanExcelIceAktarici::onizle($yol, $this->firma->id);
        $this->assertSame(1, $o['yeni']);
        $this->assertSame(1, $o['guncellenen']);
        $this->assertSame(['Listede Yok'], $o['pasife']);
        $this->assertTrue(Calisan::where('ad_soyad', 'Listede Yok')->first()->aktif);
        $this->assertSame(0, Calisan::where('ad_soyad', 'Yeni Kişi')->count());

        // Seçenek kapalı: kimse pasife alınmaz.
        $kapali = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id);
        $this->assertSame(0, $kapali['pasife']);
        $this->assertTrue(Calisan::where('ad_soyad', 'Listede Yok')->first()->aktif);

        // Seçenek açık: yalnız bu firmadaki listede olmayan aktif kişi pasife alınır, silinmez.
        $acik = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id, true);
        $this->assertSame(1, $acik['pasife']);
        $this->assertFalse(Calisan::where('ad_soyad', 'Listede Yok')->first()->aktif);
        $this->assertTrue(Calisan::where('ad_soyad', 'Listede Var')->first()->aktif);
        $this->assertTrue(Calisan::where('ad_soyad', 'Yeni Kişi')->first()->aktif);
        $this->assertTrue($baskaFirma->fresh()->aktif);

        unlink($yol);
    }

    public function test_islenebilir_satir_yoksa_kimse_pasife_alinmaz(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Mevcut', 'aktif' => true]);

        $yol = $this->xlsxOlustur([['Ad Soyad', 'Görevi'], ['', 'Boş isimli']]);

        $this->assertSame([], CalisanExcelIceAktarici::onizle($yol, $this->firma->id)['pasife']);
        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $this->firma->id, true);

        $this->assertSame(0, $sonuc['pasife']);
        $this->assertTrue(Calisan::where('ad_soyad', 'Mevcut')->first()->aktif);

        unlink($yol);
    }

    public function test_arayuzde_onay_verilmeden_pasife_alma_uygulanmaz(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Listede Yok', 'aktif' => true]);
        $yol = $this->xlsxOlustur([['Ad Soyad'], ['Yeni Kişi']]);
        $dosya = fn () => UploadedFile::fake()->createWithContent('calisanlar.xlsx', file_get_contents($yol));

        $rm = fn () => Livewire::test(CalisanlarRelationManager::class, ['ownerRecord' => $this->firma, 'pageClass' => EditFirma::class]);

        // Onay kutusu işaretlenmeden yükleme reddedilir.
        $rm()->callTableAction('excelYukle', data: ['dosya' => $dosya(), 'pasife_al' => true])->assertHasTableActionErrors();
        $this->assertTrue(Calisan::where('ad_soyad', 'Listede Yok')->first()->aktif);

        // Onayla: pasife alınır.
        $rm()->callTableAction('excelYukle', data: ['dosya' => $dosya(), 'pasife_al' => true, 'pasife_onay' => true])->assertHasNoTableActionErrors();
        $this->assertFalse(Calisan::where('ad_soyad', 'Listede Yok')->first()->aktif);

        unlink($yol);
    }
}
