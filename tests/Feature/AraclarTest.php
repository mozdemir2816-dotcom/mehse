<?php

namespace Tests\Feature;

use App\Filament\Pages\Araclar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AraclarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_sayfa_acilir(): void
    {
        Livewire::test(Araclar::class)->assertOk();
    }

    public function test_kaza_siklik_ve_agirlik_hizi_hesaplanir(): void
    {
        $component = Livewire::test(Araclar::class)
            ->set('kazaSayisi', 3)
            ->set('kayipGunSayisi', 45)
            ->set('toplamCalismaSaati', 500000);

        $this->assertSame(6.0, $component->instance()->sikikHizi());
        $this->assertSame(0.09, $component->instance()->agirlikHizi());
    }

    public function test_deger_girilmeden_hiz_hesaplari_null_doner(): void
    {
        $component = Livewire::test(Araclar::class);

        $this->assertNull($component->instance()->sikikHizi());
        $this->assertNull($component->instance()->agirlikHizi());
    }

    public function test_kaza_hesaplayici_sifirlanir(): void
    {
        $component = Livewire::test(Araclar::class)
            ->set('kazaSayisi', 3)
            ->set('kayipGunSayisi', 45)
            ->set('toplamCalismaSaati', 500000)
            ->call('kazaHesaplayiciSifirla');

        $this->assertNull($component->get('kazaSayisi'));
        $this->assertNull($component->get('kayipGunSayisi'));
        $this->assertNull($component->get('toplamCalismaSaati'));
    }

    public function test_gurultu_olcumu_eklenir_ve_silinir(): void
    {
        $component = Livewire::test(Araclar::class)->call('gurultuOlcumEkle');
        $this->assertCount(2, $component->get('gurultuOlcumleri'));

        $component->call('gurultuOlcumSil', 0);
        $this->assertCount(1, $component->get('gurultuOlcumleri'));
    }

    public function test_tek_olcumden_lex8h_dogru_hesaplanir(): void
    {
        // 8 saat 85 dB(A) -> Lex,8h = 85
        $component = Livewire::test(Araclar::class)
            ->set('gurultuOlcumleri.0.db', 85)
            ->set('gurultuOlcumleri.0.saat', 8);

        $this->assertSame(85.0, $component->instance()->lex8h());
    }

    public function test_ust_eylem_degeri_asilinca_uyari_doner(): void
    {
        $component = Livewire::test(Araclar::class)
            ->set('gurultuOlcumleri.0.db', 86)
            ->set('gurultuOlcumleri.0.saat', 8);

        $seviye = $component->instance()->gurultuSeviyesi();
        $this->assertNotNull($seviye);
        $this->assertSame('Üst Eylem Değeri Aşıldı', $seviye['etiket']);
    }

    public function test_dusuk_gurultude_seviye_null_doner(): void
    {
        $component = Livewire::test(Araclar::class)
            ->set('gurultuOlcumleri.0.db', 60)
            ->set('gurultuOlcumleri.0.saat', 8);

        $this->assertNull($component->instance()->gurultuSeviyesi());
    }

    public function test_olcum_girilmeden_lex8h_null_doner(): void
    {
        $component = Livewire::test(Araclar::class);

        $this->assertNull($component->instance()->lex8h());
    }

    public function test_gurultu_hesaplayici_sifirlanir(): void
    {
        $component = Livewire::test(Araclar::class)
            ->call('gurultuOlcumEkle')
            ->set('gurultuOlcumleri.0.db', 90)
            ->set('gurultuOlcumleri.0.saat', 8)
            ->call('gurultuHesaplayiciSifirla');

        $this->assertCount(1, $component->get('gurultuOlcumleri'));
        $this->assertNull($component->get('gurultuOlcumleri')[0]['db']);
    }
}
