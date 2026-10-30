<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Firma;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\SayfaAileleri;
use App\Support\YillikPlanGerceklesme;
use App\Support\YillikPlanSablonu;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** İSG Yıllık Planlama & Değerlendirme Merkezi: tek menü girişi, otomatik G, gerçekleşme oranı, kurul periyodu. */
class YillikPlanMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 10:00:00');
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function firma(array $ek = []): Firma
    {
        return Firma::factory()->for($this->uzman)->create($ek + ['sozlesme_baslangic' => '2026-01-01', 'calisan_sayisi' => 80, 'tehlike_sinifi' => 'cok_tehlikeli']);
    }

    public function test_yillik_planlar_egitim_merkezinin_sekmesi_ve_menuden_gizli(): void
    {
        foreach ([YillikCalismaPlani::class, YillikEgitimPlani::class, YillikDegerlendirmeRaporu::class] as $sayfa) {
            $this->assertFalse($sayfa::shouldRegisterNavigation());
        }

        $menu = collect(Filament::getNavigation());
        $grup = $menu->first(fn ($g) => $g->getLabel() === 'Planlama & Arşiv');
        $this->assertSame(['Arşiv', 'Araçlar'], $grup->getItems()->map(fn ($i) => $i->getLabel())->values()->all());

        $g = SayfaAileleri::sekmeGruplari([YillikCalismaPlani::class]);
        $this->assertSame('egitim', $g['aile']);
        $this->assertSame('Yıllık Planlar & Değerlendirme', collect($g['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertSame(
            ['Yıllık Eğitim Planı', 'Yıllık Çalışma Planı', 'Yıllık Değerlendirme Raporu'],
            array_column($g['alt'], 'etiket'),
        );

        foreach ([YillikCalismaPlani::class, YillikEgitimPlani::class, YillikDegerlendirmeRaporu::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful()->assertSee('Yıllık Planlar &amp; Değerlendirme', false);
        }
    }

    public function test_tatbikat_kaydi_plandaki_tatbikat_satirini_g_yapar(): void
    {
        $firma = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_tarihi' => '2026-03-12']);
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'taslak', 'tatbikat_tarihi' => '2026-05-12']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $this->assertGreaterThan(0, YillikPlanGerceklesme::isle($plan));

        $satir = collect($plan->fresh()->faaliyetler)->first(fn ($f) => str_contains($f['faaliyet'], 'tatbikat'));
        $this->assertSame('tamamlandi', $satir['aylar'][2]);   // Mart: yapıldı
        $this->assertNotSame('tamamlandi', $satir['aylar'][4]);   // Mayıs: taslak, sayılmaz
        $this->assertSame(0, YillikPlanGerceklesme::isle($plan->fresh()));   // ikinci çalıştırma değişiklik yapmaz
    }

    public function test_gerceklesme_orani_ve_geciken_sayisi(): void
    {
        $plan = new YillikPlan([
            'yil' => 2026,
            'faaliyetler' => [['aylar' => ['tamamlandi', 'tamamlandi', 'planlandi', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'planlandi', 'planlandi', 'planlandi']]],
            'egitimler' => [['aylar' => ['planlandi', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos', 'bos']]],
        ]);

        // Ekim 2026 (indeks 9): sınır = 9 → vadesi gelen: 2 G + P(Mart) + P(Ocak, eğitim) ; Ekim/Kasım/Aralık henüz vadesinde değil
        $oz = $plan->gerceklesmeOzeti();
        $this->assertSame(['planlanan' => 4, 'gerceklesen' => 2, 'geciken' => 2, 'oran' => 50], $oz);
        $this->assertSame(9, $plan->gecikmeAyIndeksi());

        $gelecek = new YillikPlan(['yil' => 2027, 'faaliyetler' => [['aylar' => ['planlandi']]], 'egitimler' => []]);
        $this->assertSame(0, $gelecek->gecikmeAyIndeksi());
        $this->assertNull($gelecek->gerceklesmeOzeti()['oran']);
    }

    public function test_kurul_aylari_tehlike_sinifina_ve_elli_calisana_gore(): void
    {
        $sablon = fn (Firma $f) => collect(YillikPlanSablonu::icerik($f, 2026)['faaliyetler'])->first(fn ($x) => $x['ana_konu'] === 'İSG KURULU');
        $planli = fn (array $s) => array_keys(array_filter($s['aylar'], fn ($d) => $d === 'planlandi'));

        $this->assertSame(range(0, 11), $planli($sablon($this->firma(['tehlike_sinifi' => 'cok_tehlikeli']))));
        $this->assertSame([0, 2, 4, 6, 8, 10], $planli($sablon($this->firma(['tehlike_sinifi' => 'tehlikeli']))));
        $this->assertSame([0, 3, 6, 9], $planli($sablon($this->firma(['tehlike_sinifi' => 'az_tehlikeli']))));
        $this->assertSame([8], $planli($sablon($this->firma(['calisan_sayisi' => 30]))));   // şablon varsayılanı
    }

    public function test_sayfa_acilinca_g_islenir_ve_oran_gorunur(): void
    {
        $firma = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_tarihi' => '2026-03-12']);

        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)
            ->assertSee('Yıllık Plan Gerçekleşme Oranı');

        $satir = collect(YillikPlan::firmaYilIcin($firma, 2026)->faaliyetler)->first(fn ($f) => str_contains($f['faaliyet'], 'tatbikat'));
        $this->assertSame('tamamlandi', $satir['aylar'][2]);
    }
}
