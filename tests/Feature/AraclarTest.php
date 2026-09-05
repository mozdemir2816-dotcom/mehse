<?php

namespace Tests\Feature;

use App\Filament\Pages\Araclar;
use App\Models\User;
use Database\Seeders\MykMeslekSeeder;
use Database\Seeders\NaceKoduSeeder;
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

    public function test_nace_kodu_noktali_girilirse_bulunur(): void
    {
        $this->seed(NaceKoduSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('naceKoduGirdi', '01.11.14')
            ->call('naceSorgula');

        $this->assertNotNull($component->instance()->naceSonuc);
        $this->assertSame('tehlikeli', $component->instance()->naceSonuc->tehlike_sinifi);
        $this->assertNull($component->instance()->naceHata);
    }

    public function test_nace_kodu_noktasiz_girilirse_normalize_edilip_bulunur(): void
    {
        $this->seed(NaceKoduSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('naceKoduGirdi', '011114')
            ->call('naceSorgula');

        $this->assertSame('01.11.14', $component->instance()->naceSonuc->kod);
    }

    public function test_gecersiz_formatta_nace_kodu_hata_doner(): void
    {
        $this->seed(NaceKoduSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('naceKoduGirdi', '123')
            ->call('naceSorgula');

        $this->assertNull($component->instance()->naceSonuc);
        $this->assertNotNull($component->instance()->naceHata);
    }

    public function test_listede_olmayan_nace_kodu_bulunamadi_doner(): void
    {
        $this->seed(NaceKoduSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('naceKoduGirdi', '99.99.99')
            ->call('naceSorgula');

        $this->assertNull($component->instance()->naceSonuc);
        $this->assertStringContainsString('bulunamadı', $component->instance()->naceHata);
    }

    public function test_nace_sorgusu_sifirlanir(): void
    {
        $this->seed(NaceKoduSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('naceKoduGirdi', '01.11.14')
            ->call('naceSorgula')
            ->call('naceSifirla');

        $this->assertNull($component->get('naceKoduGirdi'));
        $this->assertNull($component->instance()->naceSonuc);
        $this->assertNull($component->instance()->naceHata);
    }

    public function test_myk_meslek_adiyla_aranir(): void
    {
        $this->seed(MykMeslekSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('mykAramaTerimi', 'Ahşap Kalıpçı')
            ->call('mykAra');

        $this->assertCount(1, $component->instance()->mykSonuclar);
        $this->assertSame('11UY0011-3', $component->instance()->mykSonuclar->first()->yeterlilik_kodu);
    }

    public function test_myk_kod_ile_kismi_aranir_ve_birden_fazla_sonuc_doner(): void
    {
        $this->seed(MykMeslekSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('mykAramaTerimi', '17UY0301')
            ->call('mykAra');

        $this->assertCount(3, $component->instance()->mykSonuclar);
    }

    public function test_myk_eslesmeyen_arama_bos_sonuc_doner(): void
    {
        $this->seed(MykMeslekSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('mykAramaTerimi', 'Uzay Mühendisi Xyzabc')
            ->call('mykAra');

        $this->assertTrue($component->instance()->mykSonuclar->isEmpty());
        $this->assertTrue($component->get('mykArandi'));
    }

    public function test_myk_sorgusu_sifirlanir(): void
    {
        $this->seed(MykMeslekSeeder::class);

        $component = Livewire::test(Araclar::class)
            ->set('mykAramaTerimi', 'Ahşap Kalıpçı')
            ->call('mykAra')
            ->call('mykSifirla');

        $this->assertNull($component->get('mykAramaTerimi'));
        $this->assertFalse($component->get('mykArandi'));
        $this->assertTrue($component->instance()->mykSonuclar->isEmpty());
    }
}
