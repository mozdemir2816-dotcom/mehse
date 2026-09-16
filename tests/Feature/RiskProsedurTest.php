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

    public function test_ilk_kullanimda_dort_gercek_prosedurle_baslar(): void
    {
        RiskProsedur::varsayilanlariSeedEt($this->uzman->id);

        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());

        $matris = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->firstOrFail();
        $fineKinney = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'fine_kinney')->firstOrFail();
        $hazop = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'hazop')->firstOrFail();
        $fmea = RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'fmea')->firstOrFail();

        $this->assertStringContainsString('Matris', $matris->ad);
        $this->assertStringContainsString('Fine-Kinney', $fineKinney->ad);
        $this->assertStringContainsString('HAZOP', $hazop->ad);
        $this->assertStringContainsString('FMEA', $fmea->ad);
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

        // matris_5x5'teki özel kayda dokunulmaz, ama eksik olan diğer 3 yöntem eklenir.
        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());
        $this->assertSame('Özel Prosedürüm', RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'matris_5x5')->first()->ad);
        $this->assertNotNull(RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'hazop')->first());
        $this->assertNotNull(RiskProsedur::where('user_id', $this->uzman->id)->where('yontem', 'fmea')->first());
    }

    public function test_yeni_yontem_eklendiginde_eski_kullanicilarda_da_geriye_donuk_tamamlanir(): void
    {
        // Kullanıcı HAZOP/FMEA kod'a eklenmeden önceki bir durumu taklit ediyor:
        // sadece matris_5x5 + fine_kinney var, "hiç kaydı yok" şartı yanlışlıkla
        // hiç tetiklenmesin diye VARSAYILANLAR'daki TÜM anahtarlar tek tek kontrol edilir.
        RiskProsedur::create([
            'user_id' => $this->uzman->id, 'yontem' => 'matris_5x5',
            'ad' => 'Risk Analizi Prosedürü (Matris Yöntemi)', 'icerik' => [['tip' => 'baslik', 'metin' => 'AMAÇ']],
        ]);
        RiskProsedur::create([
            'user_id' => $this->uzman->id, 'yontem' => 'fine_kinney',
            'ad' => 'Risk Analizi Prosedürü (Fine-Kinney Yöntemi)', 'icerik' => [['tip' => 'baslik', 'metin' => '1. AMAÇ']],
        ]);

        $hazop = RiskProsedur::aktifIcin($this->uzman->id, 'hazop');
        $fmea = RiskProsedur::aktifIcin($this->uzman->id, 'fmea');

        $this->assertNotNull($hazop);
        $this->assertStringContainsString('HAZOP', $hazop->ad);
        $this->assertNotNull($fmea);
        $this->assertStringContainsString('FMEA', $fmea->ad);
        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());
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

        $this->assertSame(4, RiskProsedur::where('user_id', $this->uzman->id)->count());
        $this->assertSame(4, RiskProsedur::where('user_id', $baskasi->id)->count());
    }
}
