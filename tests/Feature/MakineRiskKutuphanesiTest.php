<?php

namespace Tests\Feature;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Support\RiskKutuphanesi;
use Database\Seeders\MakineRiskKutuphanesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Makine atölyesi risk kütüphanesi (RA-MAK-2026-01 176 madde + CNC-RA-01 80 madde). */
class MakineRiskKutuphanesiTest extends TestCase
{
    use RefreshDatabase;

    public function test_makine_ve_cnc_maddeleri_13_kategoriye_yuklenir_ve_tekrar_calistirinca_cogalmaz(): void
    {
        $this->seed(MakineRiskKutuphanesiSeeder::class);
        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $this->assertSame(13, TehlikeKategorisi::whereIn('anahtar', array_keys(MakineRiskKutuphanesiSeeder::KATEGORILER))->count());
        $this->assertSame(176, Tehlike::where('kod', 'like', 'MAK-%')->count());
        $this->assertSame(80, Tehlike::where('kod', 'like', 'CNC-%')->count());
        $this->assertSame(80, TehlikeKategorisi::where('anahtar', 'atolye_cnc_isleme_merkezi')->sole()->tehlikeler()->count());
        $this->assertSame(20, TehlikeKategorisi::where('anahtar', 'atolye_torna_tezgahi')->sole()->tehlikeler()->count());
    }

    public function test_madde_sihirbaza_tedbir_ve_fine_kinney_puaniyla_gelir(): void
    {
        $this->seed(MakineRiskKutuphanesiSeeder::class);

        $madde = RiskKutuphanesi::maddeyeCevir(Tehlike::where('kod', 'MAK-001')->sole());

        $this->assertSame('Ekstrüzyon hattı', $madde['bolum']);
        $this->assertSame('Hazneden dönen vidaya erişim', $madde['tehlike']);
        $this->assertStringStartsWith('Hazne açıklığı ve vida erişimini ölçün.', $madde['mevcut_onlem']);
        $this->assertSame([6.0, 6.0, 15.0], [$madde['olasilik'], $madde['frekans'], $madde['siddet']]);
        $this->assertStringContainsString('ISO 12100', $madde['mevzuat']);
    }
}
