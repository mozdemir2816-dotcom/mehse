<?php

namespace Tests\Feature;

use App\Models\RiskSablonu;
use App\Models\User;
use App\Support\RiskSkorlama;
use Database\Seeders\RiskSablonuInsaatFineKinneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "İnşaat / Yapı" Fine-Kinney master risk analizi şablonu (1671 madde) —
 * kaynak Excel'deki O/F/Ş değerleri ve risk seviyeleri birebir korunur.
 */
class InsaatFineKinneySablonuTest extends TestCase
{
    use RefreshDatabase;

    private function seedle(): RiskSablonu
    {
        User::factory()->create();
        (new RiskSablonuInsaatFineKinneySeeder)->setContainer($this->app)->run();

        return RiskSablonu::where('sektor', 'insaat')->where('yontem', 'fine_kinney')->sole();
    }

    public function test_seeder_paylasimli_fine_kinney_sablonu_olusturur(): void
    {
        $sablon = $this->seedle();

        $this->assertTrue($sablon->paylasildi);
        $this->assertSame('fine_kinney', $sablon->yontem);
        $this->assertSame('insaat', $sablon->sektor);
        $this->assertCount(1671, $sablon->maddeler);
    }

    public function test_seeder_idempotent_ikinci_calismada_kopya_olusmaz(): void
    {
        $this->seedle();
        (new RiskSablonuInsaatFineKinneySeeder)->setContainer($this->app)->run();

        $this->assertSame(1, RiskSablonu::where('sektor', 'insaat')->count());
        $this->assertCount(1671, RiskSablonu::where('sektor', 'insaat')->sole()->maddeler);
    }

    public function test_kullanici_yoksa_seeder_sessizce_atlar(): void
    {
        (new RiskSablonuInsaatFineKinneySeeder)->setContainer($this->app)->run();

        $this->assertSame(0, RiskSablonu::count());
    }

    public function test_ilk_madde_ofs_degerleri_excel_ile_ayni(): void
    {
        $ilk = collect($this->seedle()->maddeler)->firstWhere('anahtar', 'ifk-0001');

        $this->assertSame('HAVALANDIRMA TESİSAT VE EKİPMANLARIN MONTAJI', $ilk['bolum']);
        $this->assertSame('Takılıp düşme', $ilk['tehlike']);
        $this->assertEqualsWithDelta(3.0, $ilk['olasilik'], 0.001);
        $this->assertEqualsWithDelta(6.0, $ilk['frekans'], 0.001);
        $this->assertEqualsWithDelta(3.0, $ilk['siddet'], 0.001);
    }

    public function test_risk_seviyesi_dagilimi_kaynak_excel_ile_birebir(): void
    {
        $sayac = [];

        foreach ($this->seedle()->maddeler as $m) {
            $duzey = RiskSkorlama::hesapla('fine_kinney', (float) $m['olasilik'], (float) $m['siddet'], (float) $m['frekans'])['duzey'];
            $sayac[$duzey] = ($sayac[$duzey] ?? 0) + 1;
        }

        // Kaynak dosyanın kendi formülünün ürettiği dağılım (Risk Seviyesi 1 sütunu)
        $this->assertSame(667, $sayac['Olası Risk']);
        $this->assertSame(439, $sayac['Önemli Risk']);
        $this->assertSame(344, $sayac['Yüksek Risk']);
        $this->assertSame(221, $sayac['Çok Yüksek Risk']);
        $this->assertSame(1671, array_sum($sayac));
    }

    public function test_fine_kinney_olcek_ve_bantlar_master_analizle_hizali(): void
    {
        $this->assertArrayHasKey('0.1', config('isg.risk_fine_kinney.olasilik'));
        $this->assertArrayHasKey('100', config('isg.risk_fine_kinney.siddet'));

        $bantAdlari = collect(config('isg.risk_fine_kinney.bantlar'))->pluck('ad', 'min');
        $this->assertSame('Yüksek Risk', $bantAdlari[200]);
        $this->assertSame('Kabul Edilebilir Risk', $bantAdlari[0]);
    }

    public function test_sablon_baska_kullaniciya_da_gorunur(): void
    {
        $this->seedle();
        $baska = User::factory()->create();

        $this->assertSame(1, RiskSablonu::gorunur($baska->id)->where('sektor', 'insaat')->count());
    }
}
