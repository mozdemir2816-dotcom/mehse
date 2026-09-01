<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Models\User;
use Database\Seeders\TehlikeKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiskSihirbaziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TehlikeKutuphanesiSeeder::class);
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_sayfa_acilir_ve_ilk_adimda_baslar(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->assertOk()
            ->assertSet('adim', 1)
            ->assertSet('raporTarihi', now()->toDateString());
    }

    public function test_gecerlilik_tarihi_tehlike_sinifina_gore_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        Livewire::test(RiskSihirbazi::class)
            ->set('raporTarihi', '2026-01-01')
            ->set('firmaId', $firma->id)
            ->assertSet('gecerlilikTarihi', '2028-01-01'); // +2 yıl
    }

    public function test_firma_ve_yontem_secilmeden_ilerlenemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('ileri')
            ->assertSet('adim', 1)                  // firma yok
            ->set('firmaId', Firma::factory()->for($this->uzman)->create()->id)
            ->call('ileri')
            ->assertSet('adim', 2)
            ->call('yontemSec', 'excel')            // hazır değil
            ->assertSet('yontemSecim', null)
            ->call('yontemSec', 'manuel')
            ->assertSet('yontemSecim', 'manuel')
            ->call('ileri')
            ->assertSet('adim', 3);
    }

    public function test_kutuphaneden_tehlike_eklenir_ve_mukerrer_engellenir(): void
    {
        $tehlike = Tehlike::first();

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', $tehlike->id)
            ->call('tehlikeEkle', $tehlike->id);

        $this->assertCount(1, $component->get('secilenler'));
        $this->assertSame($tehlike->tehlike, $component->get('secilenler.0.tehlike'));
    }

    public function test_kategori_tumunu_ekle(): void
    {
        $kategori = Tehlike::first()->kategori;

        $component = Livewire::test(RiskSihirbazi::class)
            ->call('kategoriTumunuEkle', $kategori->id);

        $this->assertCount($kategori->tehlikeler()->count(), $component->get('secilenler'));
    }

    public function test_puansiz_madde_ile_6_adima_gecilemez(): void
    {
        Livewire::test(RiskSihirbazi::class)
            ->call('tehlikeEkle', Tehlike::first()->id)
            ->set('adim', 5)
            ->call('ileri')
            ->assertSet('adim', 5); // puan eksik
    }

    public function test_uctan_uca_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $t1 = Tehlike::all()->get(0);
        $t2 = Tehlike::all()->get(1);

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->call('ileri')                        // 1 -> 2
            ->call('yontemSec', 'manuel')
            ->call('ileri')                        // 2 -> 3
            ->call('tehlikeEkle', $t1->id)
            ->call('tehlikeEkle', $t2->id)
            ->call('ileri')                        // 3 -> 4
            ->set('varsayilanTermin', 'Sürekli')
            ->call('ileri')                        // 4 -> 5
            ->set('secilenler.0.olasilik', '3')
            ->set('secilenler.0.siddet', '5')
            ->set('secilenler.1.olasilik', '2')
            ->set('secilenler.1.siddet', '2')
            ->call('ileri')                        // 5 -> 6
            ->assertSet('adim', 6)
            ->call('kaydet')
            ->assertRedirect();

        $rd = RiskDegerlendirmesi::firstOrFail();
        $this->assertSame($firma->id, $rd->firma_id);
        $this->assertSame('matris_5x5', $rd->yontem);
        $this->assertCount(2, $rd->maddeler);

        $ilk = $rd->maddeler()->orderBy('sira')->first();
        $this->assertSame(15.0, $ilk->puan);
        $this->assertSame('Yüksek Risk', $ilk->duzey);
        $this->assertSame('Sürekli', $ilk->termin);
    }

    public function test_baska_uzmanin_firmasi_listede_gorunmez(): void
    {
        $benim = Firma::factory()->for($this->uzman)->create();
        $baskasi = Firma::factory()->create();

        $firmalar = Livewire::test(RiskSihirbazi::class)->instance()->firmalar();

        $this->assertArrayHasKey($benim->id, $firmalar);
        $this->assertArrayNotHasKey($baskasi->id, $firmalar);
    }
}
