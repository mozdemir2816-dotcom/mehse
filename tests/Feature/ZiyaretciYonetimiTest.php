<?php

namespace Tests\Feature;

use App\Filament\Pages\ZiyaretciYonetimi;
use App\Models\Firma;
use App\Models\User;
use App\Models\Ziyaretci;
use App\Support\ZiyaretciUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ZiyaretciYonetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Alfa Metal']);
    }

    private function ziyaretci(array $ek = []): Ziyaretci
    {
        return Ziyaretci::create([
            'firma_id' => $this->firma->id, 'ad_soyad' => 'Can Yılmaz', 'kurum' => 'XYZ Bakım',
            'gecerlilik_baslangic' => now()->subHour(), 'gecerlilik_bitis' => now()->addHours(4), ...$ek,
        ]);
    }

    public function test_gecis_olusturulur_ve_kart_pdf_iner(): void
    {
        Livewire::test(ZiyaretciYonetimi::class)
            ->set('formFirmaId', $this->firma->id)
            ->set('adSoyad', 'Ayşe Demir')
            ->set('kurum', 'Denetim A.Ş.')
            ->set('ziyaretAmaci', 'Denetim')
            ->set('isgBilgilendirme', true)
            ->call('gecisOlustur')
            ->assertHasNoErrors()
            ->assertFileDownloaded();

        $z = Ziyaretci::sole();
        $this->assertSame('Ayşe Demir', $z->ad_soyad);
        $this->assertTrue($z->isg_bilgilendirme);
        $this->assertStringStartsWith('ZYR-'.now()->year.'-', $z->kart_no);
        $this->assertSame(40, strlen($z->token));
        $this->assertSame('gecerli', $z->durum());
    }

    public function test_zorunlu_alanlar_ve_bitis_baslangictan_sonra(): void
    {
        Livewire::test(ZiyaretciYonetimi::class)
            ->set('formFirmaId', $this->firma->id)
            ->set('adSoyad', '')
            ->set('baslangic', '2026-10-02T10:00')
            ->set('bitis', '2026-10-02T09:00')
            ->call('gecisOlustur')
            ->assertHasErrors(['adSoyad', 'bitis']);

        $this->assertSame(0, Ziyaretci::count());
    }

    public function test_durumlar(): void
    {
        $this->assertSame('planli', $this->ziyaretci(['gecerlilik_baslangic' => now()->addDay(), 'gecerlilik_bitis' => now()->addDays(2)])->durum());
        $this->assertSame('suresi_doldu', $this->ziyaretci(['gecerlilik_baslangic' => now()->subDays(2), 'gecerlilik_bitis' => now()->subDay()])->durum());
        $this->assertSame('icerde', $this->ziyaretci(['giris_zamani' => now()])->durum());
        $this->assertSame('cikti', $this->ziyaretci(['giris_zamani' => now()->subHour(), 'cikis_zamani' => now()])->durum());
        $this->assertSame('iptal', $this->ziyaretci(['iptal' => true])->durum());
    }

    public function test_giris_cikis_ve_iptal_kartla_giris_engeli(): void
    {
        $z = $this->ziyaretci();
        $iptal = $this->ziyaretci(['iptal' => true]);

        $sayfa = Livewire::test(ZiyaretciYonetimi::class)->call('giris', $z->id);
        $this->assertNotNull($z->fresh()->giris_zamani);
        $this->assertCount(1, $sayfa->instance()->iceridekiler);
        $this->assertSame(1, $sayfa->instance()->ozet['iceride']);

        $sayfa->call('cikis', $z->id);
        $this->assertNotNull($z->fresh()->cikis_zamani);
        $this->assertCount(0, $sayfa->instance()->iceridekiler);

        $sayfa->call('giris', $iptal->id);
        $this->assertNull($iptal->fresh()->giris_zamani);
    }

    public function test_erken_gelen_planli_ziyaretci_giris_yapabilir(): void
    {
        $z = $this->ziyaretci(['gecerlilik_baslangic' => now()->addHour(), 'gecerlilik_bitis' => now()->addHours(5)]);

        Livewire::test(ZiyaretciYonetimi::class)->call('giris', $z->id);

        $z->refresh();
        $this->assertSame('icerde', $z->durum());
        $this->assertTrue($z->gecerlilik_baslangic->lte(now()));
    }

    public function test_suresi_dolup_cikis_yapmayan_iceride_sayilir(): void
    {
        $this->ziyaretci(['gecerlilik_baslangic' => now()->subDays(2), 'gecerlilik_bitis' => now()->subDay(), 'giris_zamani' => now()->subDays(2)]);

        $sayfa = Livewire::test(ZiyaretciYonetimi::class);
        $this->assertCount(1, $sayfa->instance()->iceridekiler);
        // "Bugün" listesinde de görünür (dönem dışı olsa bile)
        $this->assertCount(1, $sayfa->instance()->ziyaretciler);
    }

    public function test_qr_dogrulama_sayfasi_herkese_acik(): void
    {
        $z = $this->ziyaretci();
        auth()->logout();

        $this->get('/ziyaretci/'.$z->token)->assertOk()->assertSee('GEÇERLİ')->assertSee('Can Yılmaz')->assertSee('Alfa Metal');

        $z->update(['iptal' => true]);
        $this->get('/ziyaretci/'.$z->token)->assertOk()->assertSee('İPTAL EDİLMİŞ');

        $this->get('/ziyaretci/'.str_repeat('a', 40))->assertNotFound()->assertSee('GEÇERSİZ KART');
    }

    public function test_baskasinin_ziyaretcisine_erisilemez(): void
    {
        $kisitli = User::factory()->kisitli()->create();
        $kisitli->sayfaYetkileri()->create(['sayfa_anahtari' => 'ziyaretci-yonetimi']);
        $this->actingAs($kisitli);

        $z = $this->ziyaretci();

        $sayfa = Livewire::test(ZiyaretciYonetimi::class)->set('donem', 'hepsi');
        $this->assertCount(0, $sayfa->instance()->ziyaretciler);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $sayfa->call('sil', $z->id);
    }

    public function test_kart_iceridekiler_ve_defter_ciktilari(): void
    {
        $z = $this->ziyaretci(['giris_zamani' => now(), 'verilen_kkd' => 'Baret']);

        ob_start();
        ZiyaretciUretici::kartPdf($z)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $z->qrDataUri());

        ob_start();
        ZiyaretciUretici::iceridekilerPdf($this->firma, collect([$z]))->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $tmp = tempnam(sys_get_temp_dir(), 'zyt').'.xlsx';
        ob_start();
        ZiyaretciUretici::defterExcel(Ziyaretci::with('firma')->get())->sendContent();
        file_put_contents($tmp, ob_get_clean());
        $s = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);
        $this->assertSame('Can Yılmaz', $s->getCell('C2')->getValue());
        $this->assertSame('İçeride', $s->getCell('N2')->getValue());
    }
}
