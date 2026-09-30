<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giriş ekranı (isgsuite.tr referansı + MEHSE logosu) ve panel logosu.
 */
class GirisEkraniTest extends TestCase
{
    use RefreshDatabase;

    public function test_giris_ekrani_tanitim_paneli_logo_ve_kayit_secenegini_gosterir(): void
    {
        $yanit = $this->get('/admin/login')->assertOk();

        $yanit->assertSee('mehse-giris-tanitim', escape: false);
        $yanit->assertSee('images/marka/mehse-logo.png', escape: false);
        $yanit->assertSee('İş sağlığı ve güvenliği süreçlerinizi tek panelden yönetin.');
        $yanit->assertSee('Bireysel kayıt');
        $yanit->assertSee('images/marka/favicon-32.png', escape: false);
    }

    public function test_oturum_acikken_ust_kisimda_logo_var_tanitim_paneli_yok(): void
    {
        $this->actingAs(User::factory()->create());

        $yanit = $this->get('/admin/kurul-toplantisi')->assertOk();

        $yanit->assertSee('images/marka/mehse-logo-acik.png', escape: false);
        $yanit->assertDontSee('mehse-giris-tanitim-logo', escape: false);
    }

    public function test_logo_dosyalari_mevcut(): void
    {
        foreach (['mehse-logo.png', 'mehse-logo-acik.png', 'mehse-isaret.png', 'favicon-32.png'] as $dosya) {
            $this->assertFileExists(public_path('images/marka/'.$dosya));
        }
    }
}
