<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\KkdMatrisi;
use App\Models\RiskDegerlendirmesi;
use App\Models\Talimat;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\IsKalemiEvrakHazirlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsKalemiEvrakHazirlayiciTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'is_kalemleri' => ['hafriyat_kazi', 'kaynak_kesim'],
        ]);

        $kategori = TehlikeKategorisi::create(['ad' => 'Hafriyat ve Kazı İşleri', 'anahtar' => 'hafriyat-kazi']);
        Tehlike::create([
            'tehlike_kategorisi_id' => $kategori->id,
            'tehlike' => 'Kazı kenarında göçük',
            'olasilik' => 3, 'frekans' => 3, 'siddet' => 7,
        ]);
        Tehlike::create([
            'tehlike_kategorisi_id' => $kategori->id,
            'tehlike' => 'Yeraltı tesisatına zarar verme',
            'olasilik' => 2, 'frekans' => 3, 'siddet' => 6,
        ]);
    }

    public function test_kkd_talimat_ve_risk_hazirlanir(): void
    {
        $ozet = IsKalemiEvrakHazirlayici::hazirla($this->firma);

        $this->assertSame(2, $ozet['kkd_eklenen']);
        $this->assertGreaterThan(0, $ozet['talimat_eklenen']);
        $this->assertTrue($ozet['risk_olusturuldu']);

        $matris = KkdMatrisi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $matris->satirlar);
        $this->assertContains('Hafriyat ve Kazı İşleri', collect($matris->satirlar)->pluck('is_kalemi'));

        $this->assertTrue(Talimat::where('firma_id', $this->firma->id)
            ->where('baslik', 'Kazı İşleri Güvenlik Talimatı')->exists());
        $this->assertTrue(Talimat::where('firma_id', $this->firma->id)
            ->where('baslik', 'Oksijen-Asetilen Kaynak/Kesme Talimatı')->exists());

        $rd = RiskDegerlendirmesi::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('fine_kinney', $rd->yontem);
        $this->assertSame(2, $rd->maddeler()->count());

        $this->assertNotEmpty($this->firma->isKalemiEgitimKonulari());
    }

    public function test_ikinci_calistirmada_tekrar_eklenmez(): void
    {
        IsKalemiEvrakHazirlayici::hazirla($this->firma);
        $ozet = IsKalemiEvrakHazirlayici::hazirla($this->firma->fresh());

        $this->assertSame(0, $ozet['kkd_eklenen']);
        $this->assertSame(0, $ozet['talimat_eklenen']);
        $this->assertFalse($ozet['risk_olusturuldu']);
        $this->assertSame('Firmanın zaten bir risk değerlendirmesi var', $ozet['risk_atlandi_neden']);

        $this->assertSame(1, RiskDegerlendirmesi::where('firma_id', $this->firma->id)->count());
    }

    public function test_is_kalemi_secili_degilse_hicbir_sey_olusturmaz(): void
    {
        $bosFirma = Firma::factory()->for($this->uzman)->create(['is_kalemleri' => null]);

        $ozet = IsKalemiEvrakHazirlayici::hazirla($bosFirma);

        $this->assertSame(0, $ozet['kkd_eklenen']);
        $this->assertSame(0, $ozet['talimat_eklenen']);
        $this->assertFalse($ozet['risk_olusturuldu']);
        $this->assertSame([], $bosFirma->isKalemiEgitimKonulari());
    }
}
