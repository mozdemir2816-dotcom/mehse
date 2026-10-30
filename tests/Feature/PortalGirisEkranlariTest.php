<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /isyeri ve /egitim giriş ekranları: hedef kitleye göre farklı metinli bölünmüş tanıtım paneli. */
class PortalGirisEkranlariTest extends TestCase
{
    use RefreshDatabase;

    public function test_isyeri_girisinde_isverene_yonelik_panel_var(): void
    {
        $this->get('/isyeri/login')
            ->assertOk()
            ->assertSee('portal-giris--teal', false)
            ->assertSee('Çalışanlarınızın eğitim, muayene ve belge durumunu tek ekrandan izleyin.')
            ->assertDontSee('sertifikanızı indirin');
    }

    public function test_egitim_girisinde_calisana_yonelik_panel_var(): void
    {
        $this->get('/egitim/login')
            ->assertOk()
            ->assertSee('portal-giris--blue', false)
            ->assertSee('Eğitiminize katılın, testi çözün, sertifikanızı indirin.')
            ->assertDontSee('belge durumunu tek ekrandan');
    }
}
