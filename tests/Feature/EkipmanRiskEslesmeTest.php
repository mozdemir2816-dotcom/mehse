<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\EkipmanRiskEslesme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EkipmanRiskEslesmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function ekipman(Firma $firma, string $ad): IsEkipmani
    {
        return IsEkipmani::create([
            'firma_id' => $firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => $ad,
            'muayene_periyodu_ay' => 12, 'aktif' => true,
        ]);
    }

    public function test_normalize_turkce_buyuk_kucuk_harf_ve_isaretleri_eslestirir(): void
    {
        $this->assertSame('kule vinç', EkipmanRiskEslesme::normalize('KULE VİNÇ'));
        $this->assertSame('silindir', EkipmanRiskEslesme::normalize('Silindir –'));
        $this->assertSame('kazıcı yükleyici', EkipmanRiskEslesme::normalize('Kazıcı/Yükleyici'));
    }

    public function test_adi_risk_analizinde_gecen_ekipman_eksik_sayilmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $this->ekipman($firma, 'Kule Vinç');
        $this->ekipman($firma, 'Forklift');

        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['bolum' => 'Kule Vinç — Raylı model', 'tehlike' => 'Devrilme', 'olasilik' => 2, 'frekans' => 3, 'siddet' => 15]);

        $eksik = EkipmanRiskEslesme::eksikEkipmanlar($firma)->pluck('ekipman_adi')->all();

        $this->assertSame(['Forklift'], $eksik);
    }

    public function test_hic_risk_degerlendirmesi_yoksa_bos_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $this->ekipman($firma, 'Forklift');

        $this->assertTrue(EkipmanRiskEslesme::eksikEkipmanlar($firma)->isEmpty());
    }

    public function test_pasif_ekipman_kontrol_edilmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['bolum' => 'Genel', 'tehlike' => 'Genel', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);
        $pasif = $this->ekipman($firma, 'Loder');
        $pasif->update(['aktif' => false]);

        $this->assertTrue(EkipmanRiskEslesme::eksikEkipmanlar($firma)->isEmpty());
    }
}
