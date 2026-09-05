<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumKrokisi as KrokiSayfasi;
use App\Models\AcilDurumKrokisi;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilDurumKrokisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AcilDurumKrokisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_icin_bos_kroki_olusur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kroki = AcilDurumKrokisi::firmaIcin($firma);

        $this->assertTrue($kroki->exists);
        $this->assertSame([], $kroki->duvarlar);
        $this->assertSame([], $kroki->semboller);
        $this->assertNotNull($kroki->hazirlanma_tarihi);
    }

    public function test_sembol_araciyla_tiklaninca_sembol_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('semboSec', 'sondurucu')
            ->call('nokta', 120, 340);

        $this->assertCount(1, $component->get('semboller'));
        $this->assertSame('sondurucu', $component->get('semboller')[0]['tip']);
        $this->assertEquals(120, $component->get('semboller')[0]['x']);
    }

    public function test_duvar_araciyla_iki_tiklama_ile_yatay_duvar_90_dereceye_kenetlenir(): void
    {
        $component = Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('aracSec', 'duvar')
            ->call('nokta', 100, 100)
            ->call('nokta', 400, 130); // yatay hareket daha büyük -> y2 = y1'e kenetlenir

        $duvarlar = $component->get('duvarlar');
        $this->assertCount(1, $duvarlar);
        $this->assertEquals(100, $duvarlar[0]['y1']);
        $this->assertEquals(100, $duvarlar[0]['y2']); // kenetlendi
        $this->assertEquals(400, $duvarlar[0]['x2']);
    }

    public function test_duvar_araciyla_dikey_hareket_x_eksenine_kenetlenir(): void
    {
        $component = Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('aracSec', 'duvar')
            ->call('nokta', 200, 100)
            ->call('nokta', 230, 400); // dikey hareket daha büyük -> x2 = x1'e kenetlenir

        $duvarlar = $component->get('duvarlar');
        $this->assertEquals(200, $duvarlar[0]['x1']);
        $this->assertEquals(200, $duvarlar[0]['x2']); // kenetlendi
        $this->assertEquals(400, $duvarlar[0]['y2']);
    }

    public function test_sembol_ve_duvar_silinir(): void
    {
        $component = Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('nokta', 100, 100) // varsayılan araç 'sembol'
            ->call('semboSil', 0);

        $this->assertCount(0, $component->get('semboller'));
    }

    public function test_kaydet_veritabanina_yazar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(KrokiSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('nokta', 50, 50)
            ->call('kaydet');

        $kroki = AcilDurumKrokisi::where('firma_id', $firma->id)->first();
        $this->assertCount(1, $kroki->semboller);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $component = Livewire::test(KrokiSayfasi::class)->set('firmaId', $baskaFirma->id);

        $this->assertNull($component->instance()->firma());
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kroki = AcilDurumKrokisi::firmaIcin($firma);
        $kroki->update(['semboller' => [['tip' => 'cikis', 'x' => 10, 'y' => 10, 'etiket' => null]]]);

        $yanit = AcilDurumKrokisiUretici::pdf($kroki);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_lejant_sembol_tiplerine_gore_gruplar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kroki = AcilDurumKrokisi::firmaIcin($firma);
        $kroki->semboller = [
            ['tip' => 'cikis', 'x' => 1, 'y' => 1, 'etiket' => null],
            ['tip' => 'cikis', 'x' => 2, 'y' => 2, 'etiket' => null],
            ['tip' => 'sondurucu', 'x' => 3, 'y' => 3, 'etiket' => null],
        ];

        $lejant = collect($kroki->lejant())->keyBy('tip');

        $this->assertSame(2, $lejant['cikis']['adet']);
        $this->assertSame(1, $lejant['sondurucu']['adet']);
    }
}
