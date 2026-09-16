<?php

namespace Tests\Feature;

use App\Filament\Pages\EkipEvraklari;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskSablonu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EkipEvraklariTest extends TestCase
{
    use RefreshDatabase;

    public function test_sahip_erisir_uzman_erisemez(): void
    {
        $sahip = User::factory()->create();
        $this->actingAs($sahip);
        $this->assertTrue(EkipEvraklari::canAccess());

        $uzman = User::factory()->kisitli()->create();
        $this->actingAs($uzman);
        $this->assertFalse(EkipEvraklari::canAccess());
    }

    public function test_uzmanin_kendi_firmasindaki_risk_degerlendirmesi_sahibe_gorunur(): void
    {
        $sahip = User::factory()->create();
        $uzman = User::factory()->kisitli()->create(['name' => 'Ayşe Uzman']);
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Uzman Firması']);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Kaygan zemin', 'durum' => 'acik']);

        $this->actingAs($sahip);
        $liste = Livewire::test(EkipEvraklari::class)->instance()->riskDegerlendirmeleri();

        $this->assertCount(1, $liste);
        $this->assertSame($rd->id, $liste->first()->id);
    }

    public function test_sahibin_kendi_firmasindaki_risk_degerlendirmesi_listede_yok(): void
    {
        $sahip = User::factory()->create();
        $kendiFirma = Firma::factory()->for($sahip)->create();
        RiskDegerlendirmesi::create(['firma_id' => $kendiFirma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $this->actingAs($sahip);
        $liste = Livewire::test(EkipEvraklari::class)->instance()->riskDegerlendirmeleri();

        $this->assertCount(0, $liste);
    }

    public function test_kullanici_filtresi_yalniz_secili_kisinin_kayitlarini_gosterir(): void
    {
        $sahip = User::factory()->create();
        $uzman1 = User::factory()->kisitli()->create();
        $uzman2 = User::factory()->kisitli()->create();
        $firma1 = Firma::factory()->for($uzman1)->create();
        $firma2 = Firma::factory()->for($uzman2)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma1->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        RiskDegerlendirmesi::create(['firma_id' => $firma2->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $this->actingAs($sahip);
        $bilesen = Livewire::test(EkipEvraklari::class)->set('kullaniciId', $uzman1->id);

        $this->assertCount(1, $bilesen->instance()->riskDegerlendirmeleri());
    }

    public function test_havuza_ekle_paylasilan_risk_sablonu_olusturur(): void
    {
        $sahip = User::factory()->create();
        $uzman = User::factory()->kisitli()->create();
        $firma = Firma::factory()->for($uzman)->create(['unvan' => 'Test Firma']);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Kaygan zemin', 'risk' => 'Düşme', 'olasilik' => 3, 'siddet' => 3]);
        $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Elektrik çarpması', 'risk' => 'Yaralanma']);

        $this->actingAs($sahip);
        Livewire::test(EkipEvraklari::class)->call('havuzaEkle', $rd->id);

        $sablon = RiskSablonu::where('user_id', $sahip->id)->first();
        $this->assertNotNull($sablon);
        $this->assertTrue($sablon->paylasildi);
        $this->assertCount(2, $sablon->maddeler);
        $this->assertSame('matris_5x5', $sablon->yontem);
    }

    public function test_acil_durum_plani_konu_secilmisse_sahibe_gorunur(): void
    {
        $sahip = User::factory()->create();
        $uzman = User::factory()->kisitli()->create();
        $firma = Firma::factory()->for($uzman)->create();
        AcilDurumPlani::create(['firma_id' => $firma->id, 'konular' => ['yangin']]);

        $this->actingAs($sahip);
        $liste = Livewire::test(EkipEvraklari::class)->instance()->acilDurumPlanlari();

        $this->assertCount(1, $liste);
    }

    public function test_sayfa_hatasiz_render_olur(): void
    {
        $sahip = User::factory()->create();
        $uzman = User::factory()->kisitli()->create();
        $firma = Firma::factory()->for($uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Kaygan zemin']);
        AcilDurumPlani::create(['firma_id' => $firma->id, 'konular' => ['yangin']]);

        $this->actingAs($sahip);
        Livewire::test(EkipEvraklari::class)->assertOk();
    }
}
