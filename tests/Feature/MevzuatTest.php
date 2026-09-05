<?php

namespace Tests\Feature;

use App\Filament\Pages\Mevzuat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MevzuatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_sayfa_acilir(): void
    {
        Livewire::test(Mevzuat::class)->assertOk();
    }

    public function test_varsayilan_tumu_kategorisinde_tum_liste_gorunur(): void
    {
        $component = Livewire::test(Mevzuat::class);

        $this->assertCount(count(config('isg.mevzuat.liste')), $component->instance()->sonuclar());
    }

    public function test_kategori_secilince_filtrelenir(): void
    {
        $component = Livewire::test(Mevzuat::class)->call('kategoriSec', 'kanun');

        $beklenen = collect(config('isg.mevzuat.liste'))->where('kategori', 'kanun')->count();

        $this->assertCount($beklenen, $component->instance()->sonuclar());
        $this->assertGreaterThan(0, $beklenen);
    }

    public function test_arama_baslik_veya_aciklamada_arar(): void
    {
        $component = Livewire::test(Mevzuat::class)->set('arama', 'gürültü');

        $sonuclar = $component->instance()->sonuclar();

        $this->assertNotEmpty($sonuclar);
        foreach ($sonuclar as $s) {
            $this->assertTrue(
                str_contains(mb_strtolower($s['baslik']), 'gürültü') || str_contains(mb_strtolower($s['aciklama']), 'gürültü'),
            );
        }
    }

    public function test_eslesmeyen_arama_bos_liste_doner(): void
    {
        $component = Livewire::test(Mevzuat::class)->set('arama', 'xyzabc123hicbiryerdeyok');

        $this->assertEmpty($component->instance()->sonuclar());
    }
}
