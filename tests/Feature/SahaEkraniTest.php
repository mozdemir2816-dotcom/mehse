<?php

namespace Tests\Feature;

use App\Filament\Pages\AnaSayfa;
use App\Filament\Pages\Ayarlar;
use App\Filament\Pages\SahaEkrani;
use App\Filament\Pages\ZiyaretModu;
use App\Models\User;
use App\Support\KullaniciAyarlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SahaEkraniTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_varsayilan_kartlar_katalog_sirasiyla_gelir_dof_kapali(): void
    {
        $anahtarlar = array_keys(KullaniciAyarlari::sahaKisayollari($this->uzman));

        $this->assertSame(KullaniciAyarlari::SAHA_VARSAYILAN_KISAYOLLAR, $anahtarlar);
        $this->assertNotContains('dof', $anahtarlar);
        $this->assertFalse(KullaniciAyarlari::sahaAnaEkrani($this->uzman));
    }

    public function test_saha_ana_ekrani_secilince_ana_sayfa_saha_ekranina_yonlenir(): void
    {
        $this->uzman->forceFill(['ayarlar' => ['ana_ekran' => 'saha']])->save();
        KullaniciAyarlari::onbellegiTemizle();

        Livewire::test(AnaSayfa::class)->assertRedirect(SahaEkrani::getUrl());
    }

    public function test_standart_ana_sayfa_pano_olarak_kalir(): void
    {
        Livewire::test(AnaSayfa::class)->assertOk();
    }

    public function test_saha_ekrani_yalniz_secili_kartlari_gosterir(): void
    {
        $this->uzman->forceFill(['ayarlar' => ['saha_kisayollar' => ['ptw' => true, 'bulgu' => false]]])->save();
        KullaniciAyarlari::onbellegiTemizle();

        Livewire::test(SahaEkrani::class)
            ->assertSee('İş İzni Kapat')
            ->assertDontSee('Hızlı Bulgu');
    }

    public function test_ziyaret_modu_islem_parametresi_paneli_acar(): void
    {
        Livewire::withQueryParams(['islem' => 'ptw'])
            ->test(ZiyaretModu::class)
            ->assertSet('hizliIslem', 'ptw');

        Livewire::withQueryParams(['islem' => 'ramak'])
            ->test(ZiyaretModu::class)
            ->assertSet('hizliIslem', 'ramak');
    }

    public function test_ayarlardan_ana_ekran_ve_kartlar_kaydedilir(): void
    {
        Livewire::test(Ayarlar::class)
            ->set('data.ana_ekran', 'saha')
            ->set('data.saha_kisayollar', ['ziyaret', 'dof'])
            ->call('kaydet');

        $this->uzman->refresh();
        KullaniciAyarlari::onbellegiTemizle();

        $this->assertSame('saha', KullaniciAyarlari::hepsi($this->uzman)['ana_ekran']);
        $this->assertSame(['ziyaret', 'dof'], array_keys(KullaniciAyarlari::sahaKisayollari($this->uzman)));
    }
}
