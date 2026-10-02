<?php

namespace Tests\Feature;

use App\Filament\Pages\TehlikeRiskAnalitigi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\RiskAnalitigi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TehlikeRiskAnalitigiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet İnşaat', 'nace_kodu' => '41.00.01', 'tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function rd(): RiskDegerlendirmesi
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'bolum' => 'Şantiye', 'tehlike' => 'Yüksekten düşme', 'olasilik' => 5, 'siddet' => 5, 'maruz_kisi' => 4]);   // 25×4=100 fiziksel
        $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Solvent BUHARI soluma', 'olasilik' => 4, 'siddet' => 5]);                      // 20×1 kimyasal
        $rd->maddeler()->create(['sira' => 3, 'tehlike' => 'Elle taşıma', 'olasilik' => 4, 'siddet' => 4, 'maruz_kisi' => 5]);              // 16×5=80 ergonomik
        $rd->maddeler()->create(['sira' => 4, 'tehlike' => 'Elektrik çarpması', 'tehlike_turu' => 'psikososyal', 'olasilik' => 0, 'siddet' => 0]);

        return $rd;
    }

    public function test_tur_tahmini_ve_secilen_tur_onceligi(): void
    {
        $m = $this->rd()->maddeler()->orderBy('sira')->get();

        $this->assertSame('fiziksel', RiskAnalitigi::tur($m[0]));
        $this->assertSame('kimyasal', RiskAnalitigi::tur($m[1]));   // büyük harf İ/I duyarsız
        $this->assertSame('ergonomik', RiskAnalitigi::tur($m[2]));
        $this->assertSame('psikososyal', RiskAnalitigi::tur($m[3])); // elle seçilen tür tahmini ezer
    }

    public function test_dominans_payi_ve_kpi(): void
    {
        $a = RiskAnalitigi::analiz($this->firma, $this->rd());

        $this->assertSame('fiziksel', $a['baskin']);
        $this->assertEquals(100, $a['dagilim']['fiziksel']['skor']);
        $this->assertEquals(50.0, $a['dagilim']['fiziksel']['pay']);   // 100 / 200
        $this->assertEquals(40.0, $a['dagilim']['ergonomik']['pay']);
        $this->assertSame(9, $a['maruz_toplam']);
        $this->assertSame(2, $a['maruz_eksik']);
        $this->assertSame(3, $a['tur_tahmin']);
        $this->assertSame('Yüksekten düşme', $a['en_yuksek']->first()['madde']->tehlike);
        $this->assertCount(3, $a['en_yuksek']);   // puansız madde sıralamada yok
    }

    public function test_nace_kaynaklari_kayitla_eslesir(): void
    {
        $k = collect(RiskAnalitigi::analiz($this->firma, $this->rd())['kaynaklar'])->keyBy('baslik');

        $this->assertGreaterThan(0, $k['Yüksekte çalışma ve düşmeye karşı koruma']['eslesen']);
        $this->assertSame(0, $k['Kazı ve göçük']['eslesen']);
        $this->assertSame([], RiskAnalitigi::analiz(Firma::factory()->for($this->uzman)->create(['nace_kodu' => null]), null)['kaynaklar']);
    }

    public function test_sayfa_tur_ve_maruz_kisi_kaydeder(): void
    {
        $rd = $this->rd();
        $m = $rd->maddeler()->where('sira', 2)->first();
        $yabanci = RiskDegerlendirmesi::create(['firma_id' => Firma::factory()->create()->id, 'yontem' => 'matris_5x5']);
        $ym = $yabanci->maddeler()->create(['sira' => 1, 'tehlike' => 'X', 'olasilik' => 1, 'siddet' => 1]);

        Livewire::test(TehlikeRiskAnalitigi::class)
            ->assertOk()
            ->assertSee('Baskın tehlike türü')
            ->assertSee('Saha doğrulaması bekliyor')
            ->call('maddeGuncelle', $m->id, 'maruz_kisi', '7')
            ->call('maddeGuncelle', $m->id, 'tehlike_turu', 'biyolojik')
            ->call('maddeGuncelle', $ym->id, 'maruz_kisi', '9')
            ->call('maddeGuncelle', $m->id, 'puan', '1')
            ->call('tahminleriKaydet');

        $m->refresh();
        $this->assertSame(7, (int) $m->maruz_kisi);
        $this->assertSame('biyolojik', $m->tehlike_turu);
        $this->assertNull($ym->fresh()->maruz_kisi);
        $this->assertSame('fiziksel', $rd->maddeler()->where('sira', 1)->value('tehlike_turu'));
    }
}
