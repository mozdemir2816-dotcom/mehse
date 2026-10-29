<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_kok_adres_herkese_acik_tanitim_sayfasidir_panel_admin_de(): void
    {
        $this->get('/')->assertOk()->assertSee('Üye Girişi');
        $this->get('/admin')->assertRedirect();
    }
}
