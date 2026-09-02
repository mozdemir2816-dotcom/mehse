<?php

namespace Tests\Feature;

use App\Filament\Pages\AtamaYazilari as AtamaSayfasi;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\AtamaYazisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AtamaYazilariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_tekli_rol_manuel_uye_ile_kaydedilir_ve_pdf_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_ad' => 'Ali Patron']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'calisan_temsilcisi')
            ->set('tekAdSoyad', 'Ahmet Yılmaz')
            ->set('tekTc', '12345678901')
            ->set('basTemsilci', true)
            ->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('calisan_temsilcisi', $kayit->rol_anahtari);
        $this->assertCount(1, $kayit->uyeler);
        $this->assertSame('Ahmet Yılmaz', $kayit->uyeler[0]['ad_soyad']);
        $this->assertTrue($kayit->uyeler[0]['bas_uye']);
        $this->assertStringStartsWith('ATM-'.now()->year.'-', $kayit->dokuman_no);
        $this->assertNotNull($kayit->gorev_baslangic);
    }

    public function test_firma_secilince_isveren_adi_otomatik_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_vekili' => 'Vekil Bey', 'isveren_ad' => 'Asıl Patron']);

        $component = Livewire::test(AtamaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('Vekil Bey', $component->get('isverenVekiliAdi'));
    }

    public function test_ekip_rolu_coklu_calisan_ve_bas_uye_ile_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c1 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan Bir']);
        $c2 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan İki']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'sondurme_ekibi')
            ->call('calisanToggle', $c1->id)
            ->call('calisanToggle', $c2->id)
            ->call('basUyeSec', $c1->id)
            ->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(2, $kayit->uyeler);
        $this->assertNull($kayit->gorev_baslangic);
        $basUye = collect($kayit->uyeler)->firstWhere('bas_uye', true);
        $this->assertSame('Çalışan Bir', $basUye['ad_soyad']);
    }

    public function test_isg_kurulu_firma_profilinden_otomatik_doldur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->count(3)->create();

        $component = Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu')
            ->call('firmaProfilindenDoldur');

        $this->assertCount(3, $component->get('secilenCalisanIdler'));
    }

    public function test_uye_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'calisan_temsilcisi')
            ->callAction('pdf');

        $this->assertDatabaseCount('atama_yazilari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'isg_kurulu',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Üye Bir', 'tc' => null, 'gorev' => 'İGU', 'bas_uye' => true],
            ],
        ]);

        $yanit = AtamaYazisiUretici::pdf($kayit);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'bilgi_sahibi',
            'tarih' => now(),
            'uyeler' => [['ad_soyad' => 'Test', 'tc' => null, 'gorev' => null, 'bas_uye' => false]],
        ]);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $kayit->id);

        $this->assertDatabaseMissing('atama_yazilari', ['id' => $kayit->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(AtamaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
