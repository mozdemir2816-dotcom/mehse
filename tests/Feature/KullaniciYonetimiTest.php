<?php

namespace Tests\Feature;

use App\Filament\Pages\KullaniciYonetimi;
use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KullaniciYonetimiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sahip_kullanici_yonetimine_erisir_uzman_erisemez(): void
    {
        $sahip = User::factory()->create();
        $this->actingAs($sahip);
        $this->assertTrue(KullaniciYonetimi::canAccess());

        $uzman = User::factory()->kisitli()->create();
        $this->actingAs($uzman);
        $this->assertFalse(KullaniciYonetimi::canAccess());
    }

    public function test_kisitli_kullanici_varsayilan_olarak_hicbir_sayfa_goremez(): void
    {
        $uzman = User::factory()->kisitli()->create();
        $this->actingAs($uzman);

        $this->assertFalse(RiskSihirbazi::canAccess());
    }

    public function test_sahip_yetki_verince_kullanici_sayfayi_ve_sadece_o_firmayi_gorur(): void
    {
        $sahip = User::factory()->create();
        $uzman = User::factory()->kisitli()->create();

        $gorecegiFirma = Firma::factory()->create(['user_id' => $sahip->id]);
        $goremeyecegiFirma = Firma::factory()->create(['user_id' => $sahip->id]);

        $this->actingAs($sahip);
        Livewire::test(KullaniciYonetimi::class)
            ->call('ac', $uzman->id)
            ->set("sayfaSecim.risk-sihirbazi", true)
            ->set("firmaSecim.{$gorecegiFirma->id}", true)
            ->call('kaydet');

        $this->actingAs($uzman->refresh());

        $this->assertTrue(RiskSihirbazi::canAccess());

        $gorunenFirmaIdleri = Firma::pluck('id');
        $this->assertTrue($gorunenFirmaIdleri->contains($gorecegiFirma->id));
        $this->assertFalse($gorunenFirmaIdleri->contains($goremeyecegiFirma->id));
    }

    public function test_kayit_sonrasi_kullanici_hicbir_yetkiyle_olusmaz(): void
    {
        $bosPanel = User::factory()->kisitli()->create();

        $this->assertFalse($bosPanel->sahipMi());
        $this->assertEmpty($bosPanel->sayfaYetkileri()->get());
        $this->assertTrue($bosPanel->aktif);
    }
}
