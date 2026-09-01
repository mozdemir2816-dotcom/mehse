<?php

namespace Tests\Feature;

use App\Filament\Pages\IsgKatipRobot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsgKatipRobotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_bilgi_sayfasi_acilir_ve_eklenti_bagli_degil(): void
    {
        Livewire::test(IsgKatipRobot::class)
            ->assertOk()
            ->assertSet('eklentiBagli', false)
            ->assertActionExists('eklentiIndir')
            ->assertActionExists('baglantiKontrol');
    }

    public function test_bot_kur_stub_bildirimi_doner(): void
    {
        Livewire::test(IsgKatipRobot::class)
            ->call('botKur', 'Çoklu Atama Yap')
            ->assertNotified();
    }

    public function test_config_bot_katalogu_ve_hak_sayaci(): void
    {
        $this->assertSame(3, config('isg.isg_katip.gunluk_hak'));
        $this->assertCount(11, config('isg.isg_katip.botlar'));
        $this->assertCount(4, config('isg.isg_katip.kurulum_adimlari'));
    }
}
