<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Filament\Resources\Firmas\Schemas\FirmaForm;
use App\Models\NaceKodu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** K1 (23.11.2026): tehlike sınıfı NACE kodundan gelir (İşyeri Tehlike Sınıfları Tebliği). */
class FirmaNaceTehlikeSinifiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        NaceKodu::create(['kod' => '41.20.02', 'tanim' => 'İkamet amaçlı binaların inşaatı', 'tehlike_sinifi' => 'cok_tehlikeli']);
        NaceKodu::create(['kod' => '62.01.01', 'tanim' => 'Bilgisayar programlama faaliyetleri', 'tehlike_sinifi' => 'az_tehlikeli']);
    }

    public function test_yeni_firmada_tehlike_sinifi_varsayilan_bos(): void
    {
        Livewire::test(CreateFirma::class)->assertFormSet(['tehlike_sinifi' => null]);
    }

    public function test_nace_girilince_tehlike_sinifi_ve_aciklama_dolar(): void
    {
        Livewire::test(CreateFirma::class)
            ->fillForm(['nace_kodu' => '412002'])
            ->assertFormSet([
                'nace_kodu' => '41.20.02',
                'tehlike_sinifi' => 'cok_tehlikeli',
                'nace_aciklama' => 'İkamet amaçlı binaların inşaatı',
            ]);
    }

    public function test_elle_secilen_sinif_nace_degisince_guncellenir(): void
    {
        Livewire::test(CreateFirma::class)
            ->fillForm(['tehlike_sinifi' => 'cok_tehlikeli'])
            ->fillForm(['nace_kodu' => '62.01.01'])
            ->assertFormSet(['tehlike_sinifi' => 'az_tehlikeli']);
    }

    public function test_listede_olmayan_kod_sinifi_degistirmez_ve_uyarir(): void
    {
        Livewire::test(CreateFirma::class)
            ->fillForm(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '41.00.01'])
            ->assertFormSet(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '41.00.01']);

        $this->assertStringContainsString('bulunamadı', FirmaForm::naceIpucu('41.00.01', null));
        $this->assertStringContainsString('6 haneli', FirmaForm::naceIpucu('41.00', null));
        $this->assertNull(FirmaForm::naceIpucu('41.20.02', null));
    }

    public function test_uyumsuz_secimde_uyari_verir(): void
    {
        $this->assertStringContainsString('Çok Tehlikeli', FirmaForm::tehlikeSinifiIpucu('41.20.02', 'az_tehlikeli'));
        $this->assertNull(FirmaForm::tehlikeSinifiIpucu('41.20.02', 'cok_tehlikeli'));
        $this->assertNull(FirmaForm::tehlikeSinifiIpucu('41.00.01', 'az_tehlikeli'));
    }
}
