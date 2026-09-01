<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_profil_ozeti_sayaclari_hesaplar(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 3]);
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        Firma::factory()->create(); // başka uzman

        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'A', 'aktif' => true]);
        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'B', 'aktif' => false]);

        $rd = RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        // önemli risk: puan > eşik (140)
        $rd->maddeler()->create(['tehlike' => 'Ağır', 'olasilik' => 10, 'frekans' => 6, 'siddet' => 7]); // 420
        $rd->maddeler()->create(['tehlike' => 'Hafif', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]); // 1

        RiskSablonu::olustur($this->uzman, 'S', 'ofis', null, 'matris_5x5', [['anahtar' => 'x', 'tehlike' => 't']]);

        $o = PortfoyKarne::profilOzeti($this->uzman->id);

        $this->assertSame(2, $o['firma']);
        $this->assertSame(1, $o['calisan']); // yalnız aktif
        $this->assertSame(1, $o['risk_degerlendirmesi']);
        $this->assertSame(1, $o['risk_sablonu']);
        $this->assertSame(1, $o['onemli_risk']);
        $this->assertSame(1, $o['tehlike_dagilimi']['Çok Tehlikeli']);
        $this->assertSame(1, $o['tehlike_dagilimi']['Az Tehlikeli']);
    }

    public function test_firma_kriter_matrisi_risk_degerlendirmesini_isaretler(): void
    {
        $a = Firma::factory()->for($this->uzman)->create();
        $b = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $matris = collect(PortfoyKarne::firmaKriterMatrisi($this->uzman->id))->keyBy(fn ($s) => $s['firma']->id);

        $this->assertTrue($matris[$a->id]['hucreler']['risk_degerlendirmesi']);
        $this->assertFalse($matris[$b->id]['hucreler']['risk_degerlendirmesi']);
        $this->assertSame(100, $matris[$a->id]['oran']);
        $this->assertSame(0, $matris[$b->id]['oran']);
    }

    public function test_sayfa_sekmeli_acilir(): void
    {
        Firma::factory()->for($this->uzman)->create();

        Livewire::test(Profilim::class)
            ->assertOk()
            ->assertSet('sekme', 'genel')
            ->call('sekmeSec', 'firmalar')->assertSet('sekme', 'firmalar')
            ->call('sekmeSec', 'firma_takip')->assertSet('sekme', 'firma_takip')
            ->call('sekmeSec', 'calisanlar')->assertSet('sekme', 'calisanlar')
            ->call('sekmeSec', 'risklerim')->assertSet('sekme', 'risklerim')
            ->call('sekmeSec', 'diger')->assertSet('sekme', 'diger')
            ->call('sekmeSec', 'yok')->assertSet('sekme', 'diger');
    }

    public function test_calisanlar_yalniz_kendi_firmalarindan_gelir(): void
    {
        $benim = Firma::factory()->for($this->uzman)->create();
        $baskasi = Firma::factory()->create();
        Calisan::create(['firma_id' => $benim->id, 'ad_soyad' => 'Benimki', 'aktif' => true]);
        Calisan::create(['firma_id' => $baskasi->id, 'ad_soyad' => 'Yabancı', 'aktif' => true]);

        $liste = Livewire::test(Profilim::class)->instance()->calisanlar();

        $this->assertCount(1, $liste);
        $this->assertSame('Benimki', $liste->first()->ad_soyad);
    }
}
