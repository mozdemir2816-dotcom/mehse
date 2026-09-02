<?php

namespace Tests\Feature;

use App\Filament\Pages\TehlikeCakismalari;
use App\Models\Tehlike;
use App\Models\TehlikeCakismasi;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TehlikeCakismalariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function cakismaOlustur(): TehlikeCakismasi
    {
        $kategori = TehlikeKategorisi::create(['ad' => 'Test', 'anahtar' => 'test']);
        $mevcut = Tehlike::create(['tehlike_kategorisi_id' => $kategori->id, 'tehlike' => 'Orijinal metin', 'risk' => 'Orijinal risk']);

        return TehlikeCakismasi::create([
            'tehlike_kategorisi_id' => $kategori->id,
            'mevcut_tehlike_id' => $mevcut->id,
            'benzerlik_yuzdesi' => 85,
            'yeni_veri' => ['tehlike' => 'Orijinal metin (güncel)', 'risk' => 'Yeni risk'],
        ]);
    }

    public function test_bos_iken_nav_gizli_doluyken_rozet_gosterir(): void
    {
        $this->assertFalse(TehlikeCakismalari::shouldRegisterNavigation());
        $this->assertNull(TehlikeCakismalari::getNavigationBadge());

        $this->cakismaOlustur();

        $this->assertTrue(TehlikeCakismalari::shouldRegisterNavigation());
        $this->assertSame('1', TehlikeCakismalari::getNavigationBadge());
    }

    public function test_sayfa_acilir_ve_yenisiniKullan_calisir(): void
    {
        $cakisma = $this->cakismaOlustur();

        Livewire::test(TehlikeCakismalari::class)
            ->assertOk()
            ->call('yenisiniKullan', $cakisma->id);

        $this->assertSame(0, TehlikeCakismasi::count());
        $this->assertSame('Yeni risk', Tehlike::first()->risk);
    }
}
