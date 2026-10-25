<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Aynı unvanlı şube/şantiyeler firma seçim listelerinde kısa adla ayrılır. */
class FirmaSecimEtiketiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kisa_ad_unvanin_basi_degilse_one_yazilir(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $unvan = 'SAT TURİZM İNŞAAT LTD. ŞTİ.';
        $merkez = Firma::factory()->for($uzman)->create(['unvan' => $unvan, 'kisa_ad' => 'SAT – Merkez']);
        $santiye = Firma::factory()->for($uzman)->create(['unvan' => $unvan, 'kisa_ad' => 'SAT – Şantiye']);
        $sade = Firma::factory()->for($uzman)->create(['unvan' => 'BTM BURSA TOZ BOYA', 'kisa_ad' => 'BTM']);
        $bos = Firma::factory()->for($uzman)->create(['unvan' => 'Alpuğan Çimento', 'kisa_ad' => null]);

        $secenek = Firma::query()->orderBy('unvan')->secenekler()->all();

        $this->assertSame('SAT – Merkez · '.$unvan, $secenek[$merkez->id]);
        $this->assertSame('SAT – Şantiye · '.$unvan, $secenek[$santiye->id]);
        $this->assertSame('BTM BURSA TOZ BOYA', $secenek[$sade->id]);
        $this->assertSame('Alpuğan Çimento', $secenek[$bos->id]);

        Livewire::test(EgitimKatilim::class)->assertSee('SAT – Şantiye · SAT');
    }
}
