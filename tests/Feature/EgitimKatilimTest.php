<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\KatilimciExcelOkuyucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class EgitimKatilimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_genel_baslik_icerigi_tehlike_sinifina_ve_sektore_gore_uretilir(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'az_tehlikeli');

        $this->assertSame('genel', $icerik['tip']);
        $this->assertSame(8, $icerik['saat']);
        $this->assertCount(4, $icerik['genel_konular']);
        $this->assertCount(5, $icerik['saglik_konulari']);
        $this->assertCount(12, $icerik['teknik_konular']);
        $this->assertSame('İnşaat', $icerik['isyerine_ozgu']['sektor']);
        $this->assertCount(5, $icerik['isyerine_ozgu']['maddeler']);
    }

    public function test_genel_baslik_sektorsuz_ise_isyerine_ozgu_bos_gelir(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli');

        $this->assertNull($icerik['isyerine_ozgu']);
    }

    public function test_ozel_baslik_tek_bloklu_sabit_icerik_doner(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('yuksekte_calisma', null, 'az_tehlikeli');

        $this->assertSame('ozel', $icerik['tip']);
        $this->assertSame('Yükseklerde Çalışma', $icerik['ad']);
        $this->assertNotEmpty($icerik['maddeler']);
    }

    public function test_sayfa_firma_secilince_calisanlar_varsayilan_secili_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->count(3)->create();

        $component = Livewire::test(EgitimSayfasi::class)
            ->assertOk()
            ->set('firmaId', $firma->id);

        $this->assertCount(3, $component->get('secilenCalisanIdler'));
    }

    public function test_manuel_katilimci_eklenir_ve_cikarilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniAdSoyad', 'Ahmet Yılmaz')
            ->set('yeniTc', '12345678901')
            ->call('manuelEkle');

        $this->assertCount(1, $component->get('manuelKatilimcilar'));

        $component->call('manuelCikar', 0);
        $this->assertCount(0, $component->get('manuelKatilimcilar'));
    }

    public function test_excel_ile_katilimci_toplu_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(['Ad Soyad', 'T.C. Kimlik No', 'Görevi'], null, 'A1');
        $sayfa->fromArray(['Zeynep Kaya', '11122233344', 'Formen'], null, 'A2');
        $sayfa->fromArray(['Emre Demir', '', 'İşçi'], null, 'A3');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $component = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('excelDosya', UploadedFile::fake()->createWithContent('katilimci.xlsx', file_get_contents($yol)))
            ->call('excelIceAktar');

        $manuel = $component->get('manuelKatilimcilar');
        $this->assertCount(2, $manuel);
        $this->assertSame('Zeynep Kaya', $manuel[0]['ad_soyad']);
        $this->assertSame('11122233344', $manuel[0]['tc']);

        unlink($yol);
    }

    public function test_elle_eklenenleri_firmaya_kaydet_secilince_calisan_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', now()->toDateString())
            ->set('yeniAdSoyad', 'Ali Veli')
            ->set('yeniTc', '99988877766')
            ->call('manuelEkle')
            ->set('elleEklenenleriFirmayaKaydet', true)
            ->callAction('pdf');

        $this->assertDatabaseHas('calisanlar', [
            'firma_id' => $firma->id,
            'ad_soyad' => 'Ali Veli',
            'tc' => '99988877766',
        ]);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_pdf_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create(['ad_soyad' => 'Test Çalışan']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('sektorAnahtari', 'insaat')
            ->set('egitimYeri', 'Şantiye ofisi')
            ->set('belgeTarihi', now()->toDateString())
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('genel', $kayit->baslik_anahtari);
        $this->assertSame('insaat', $kayit->sektor_anahtari);
        $this->assertNotNull($kayit->konu_secimleri);
        $this->assertCount(1, $kayit->katilimcilar);
        $this->assertStringStartsWith('EGT-'.now()->year.'-', $kayit->belge_no);
    }

    public function test_ozel_baslik_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'ilkyardim_ekibi',
            'belge_tarihi' => now(),
            'sure_gun' => 1,
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('ilkyardim_ekibi', null, 'az_tehlikeli'),
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'gorev' => null]],
        ]);

        $yanit = EgitimKatilimUretici::pdf($kayit);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_pdf_ve_silme(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'belge_tarihi' => now(),
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
            'katilimcilar' => [],
        ]);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $kayit->id);

        $this->assertDatabaseMissing('egitim_katilimlari', ['id' => $kayit->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(EgitimSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
