<?php

namespace Tests\Feature;

use App\Models\TehlikeKategorisi;
use App\Support\NaceGrubu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskKutuphanesiNaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nace_kodu_sabit_gruba_cevrilir(): void
    {
        $this->assertSame('insaat', NaceGrubu::bul('43.34.01'));
        $this->assertSame('kimya', NaceGrubu::bul('20.30.90'));
        $this->assertNull(NaceGrubu::bul(null));
        $this->assertNull(NaceGrubu::bul('9'));
    }

    public function test_kategori_yontem_ve_nace_ile_suzulur(): void
    {
        TehlikeKategorisi::create(['anahtar' => 'a', 'ad' => 'İnşaat FK', 'sira' => 1, 'yontem' => 'fine_kinney', 'nace_gruplari' => ['insaat']]);
        TehlikeKategorisi::create(['anahtar' => 'b', 'ad' => 'Vinç', 'sira' => 2, 'yontem' => 'fine_kinney', 'nace_gruplari' => ['ortak']]);
        TehlikeKategorisi::create(['anahtar' => 'c', 'ad' => 'Boya HAZOP', 'sira' => 3, 'yontem' => 'hazop', 'nace_gruplari' => ['kimya']]);
        TehlikeKategorisi::create(['anahtar' => 'd', 'ad' => 'Etiketsiz', 'sira' => 4, 'yontem' => null, 'nace_gruplari' => null]);

        $adlar = TehlikeKategorisi::uygun('fine_kinney', 'insaat')->orderBy('sira')->pluck('ad')->all();

        $this->assertSame(['İnşaat FK', 'Vinç'], $adlar);
    }
}
