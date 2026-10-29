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
        $this->firma->update(['calisan_sayisi' => 1]);   // varsayılan maruz kişi = 1 → eski hesapla aynı
        $a = RiskAnalitigi::analiz($this->firma->fresh(), $this->rd());

        $this->assertSame('fiziksel', $a['baskin']);
        $this->assertEquals(100, $a['dagilim']['fiziksel']['skor']);
        $this->assertEquals(50.0, $a['dagilim']['fiziksel']['pay']);   // 100 / 200
        $this->assertEquals(40.0, $a['dagilim']['ergonomik']['pay']);
        $this->assertSame(11, $a['maruz_toplam']);   // 4 + 1 (varsayılan) + 5 + 1 (varsayılan)
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

    public function test_maruz_kisi_bos_ise_firmanin_calisan_sayisi_varsayilir(): void
    {
        $this->firma->update(['calisan_sayisi' => 6]);
        $a = RiskAnalitigi::analiz($this->firma->fresh(), $this->rd());

        // Kimyasal madde: puan 20 × 6 çalışan; girilmiş maddeler (4 ve 5 kişi) değişmez.
        $this->assertEquals(120, $a['dagilim']['kimyasal']['skor']);
        $this->assertEquals(100, $a['dagilim']['fiziksel']['skor']);
        $this->assertSame(6, $a['maruz_varsayilan']);
        $this->assertSame(2, $a['maruz_eksik']);
        $this->assertSame(4 + 6 + 5 + 6, $a['maruz_toplam']);
        $this->assertCount(3, array_filter($a['en_yuksek']->all(), fn ($s) => $s['dominans'] > 0));
        $this->assertLessThanOrEqual(5, $a['en_yuksek']->count());
    }

    public function test_pano_risk_renkleri_fine_kinney_esikleri(): void
    {
        $this->assertSame('#e11d48', \App\Support\RiskSkorlama::panoRengi('fine_kinney', 450)['renk']);
        $this->assertSame('#d97706', \App\Support\RiskSkorlama::panoRengi('fine_kinney', 300)['renk']);
        $this->assertSame('#eab308', \App\Support\RiskSkorlama::panoRengi('fine_kinney', 100)['renk']);
        $this->assertSame('#059669', \App\Support\RiskSkorlama::panoRengi('fine_kinney', 40)['renk']);
        $this->assertSame('Çok Yüksek', \App\Support\RiskSkorlama::panoRengi('fine_kinney', 400)['duzey']);
    }

    public function test_jsa_sayfasi_risk_analizinden_yuksek_riskli_isleri_kartlar(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Kapalı alanda tank temizliği', 'olasilik' => 3, 'siddet' => 15, 'frekans' => 6]);
        $rd->maddeler()->create(['sira' => 2, 'tehlike' => 'Kaynak işlerinde yangın', 'olasilik' => 3, 'siddet' => 7, 'frekans' => 6]);

        $isler = collect(\App\Support\YuksekRiskliIsler::firma($this->firma))->keyBy('tur');

        $this->assertSame(['kapali_alan', 'sicak_is'], $isler->keys()->sort()->values()->all());
        $this->assertSame(1, $isler['kapali_alan']['adet']);

        Livewire::test(\App\Filament\Pages\JsaDegerlendirmesi::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('Yüksek Riskli İşler')
            ->assertSee('Kapalı / Dar Alan')
            ->assertSee('İş İzni Formu');

        // JSA kartından gelen tür iş izni formunda ön seçili açılır.
        $this->get(\App\Filament\Pages\IsIzinFormu::getUrl(['firma' => $this->firma->id, 'tur' => 'sicak_is']))->assertSuccessful();
        Livewire::withQueryParams(['firma' => $this->firma->id, 'tur' => 'sicak_is'])
            ->test(\App\Filament\Pages\IsIzinFormu::class)
            ->assertSet('izinTurleri', ['sicak_is']);
    }
}
