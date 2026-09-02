<?php

namespace Tests\Feature;

use App\Filament\Pages\EReetem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EReetemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_bilgi_sayfasi_acilir(): void
    {
        Livewire::test(EReetem::class)
            ->assertOk()
            ->assertSee('Ücretsiz e-Reçete');
    }

    public function test_isyeri_hekimi_hatirlatma_bildirimi_doner(): void
    {
        Livewire::test(EReetem::class)
            ->call('isyeriHekimiHatirlat')
            ->assertNotified();
    }

    public function test_config_icerik_bolumleri_dolu(): void
    {
        $this->assertNotEmpty(config('isg.e_recetem.nedir'));
        $this->assertNotEmpty(config('isg.e_recetem.kimler_faydalanabilir'));
        $this->assertNotEmpty(config('isg.e_recetem.nasil_calisir'));
        $this->assertNotEmpty(config('isg.e_recetem.sss'));
    }
}
