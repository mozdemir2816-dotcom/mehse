<?php

namespace Tests\Feature;

use App\Models\RiskProsedur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskProsedurTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_ilk_kullanimda_iki_gercek_prosedurle_baslar(): void
    {
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);

        $this->assertSame(2, RiskProsedur::where('user_id', $this->uzman->id)->count());

        $matris = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();
        $fineKinney = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'fine_kinney')->firstOrFail();

        $this->assertStringContainsString('Matris', $matris->ad);
        $this->assertStringContainsString('Fine-Kinney', $fineKinney->ad);
        $this->assertSame('baslik', $matris->icerik[0]['tip']);
        $this->assertSame('AMAÇ', $matris->icerik[0]['metin']);
    }

    public function test_seed_var_olan_kaydi_ezmez(): void
    {
        RiskProsedur::create([
            'user_id' => $this->uzman->id, 'yontem' => 'matris_5x5',
            'ad' => 'Özel Prosedürüm', 'icerik' => [['tip' => 'paragraf', 'metin' => 'x']],
        ]);

        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);

        $this->assertSame(1, RiskProsedur::where('user_id', $this->uzman->id)->count());
        $this->assertSame('Özel Prosedürüm', RiskProsedur::where('user_id', $this->uzman->id)->first()->ad);
    }

    public function test_aktif_icin_yonteme_gore_dogru_kaydi_dondurur(): void
    {
        $prosedur = RiskProsedur::aktifIcin($this->uzman->id, 'fine_kinney');

        $this->assertSame('fine_kinney', $prosedur->yontem);
        $this->assertStringContainsString('Fine-Kinney', $prosedur->ad);
    }

    public function test_baskasinin_prosedurleri_karismaz(): void
    {
        $baskasi = User::factory()->create();
        RiskProsedur::varsayilanlariSeedEt($baskasi->id);

        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);

        $this->assertSame(2, RiskProsedur::where('user_id', $this->uzman->id)->count());
        $this->assertSame(2, RiskProsedur::where('user_id', $baskasi->id)->count());
    }
}
