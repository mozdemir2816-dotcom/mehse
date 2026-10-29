<?php

namespace Tests\Feature;

use App\Filament\Pages\JsaDegerlendirmesi;
use App\Filament\Pages\KimyasalSicili;
use App\Filament\Pages\KimyasalYonetimi;
use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Pages\TehlikeRiskAnalitigi;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Filament\Resources\RiskProsedurs\RiskProsedurResource;
use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use App\Filament\Resources\Tehlikes\TehlikeResource;
use App\Models\User;
use App\Support\SayfaAileleri;
use App\Models\RiskSablonu;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Risk & Tehlike Yönetimi (30.10.2026): 7+ menü satırı tek girişe, 12 sayfa 3 ana sekmeye indi. */
class RiskYonetimiSekmeleriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_uc_ana_sekme_ve_alt_sayfalar(): void
    {
        $g = SayfaAileleri::sekmeGruplari([RiskSihirbazi::class]);

        $this->assertSame('risk', $g['aile']);
        $this->assertSame(
            ['Risk Analiz Sihirbazı & Değerlendirmeler', 'Kimyasal & Tehlike Analitiği', 'Merkezi Kütüphane & Şablonlar'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame([true, false, false], array_column($g['ust'], 'aktif'));
        $this->assertSame(['Yeni Değerlendirme', 'Kayıtlı Değerlendirmeler', 'JSA (İşe Özgü Risk)'], array_column($g['alt'], 'etiket'));

        $kimya = SayfaAileleri::sekmeGruplari([TehlikeRiskAnalitigi::class]);
        $this->assertSame([false, true, false], array_column($kimya['ust'], 'aktif'));
        $this->assertSame(
            ['Kimyasal Yönetimi', 'Kimyasal Risk Değ.', 'Kimyasal Sicili / Afiş', 'Tehlike ve Risk Analitiği'],
            array_column($kimya['alt'], 'etiket'),
        );

        $kutuphane = SayfaAileleri::sekmeGruplari([RiskSablonuResource::class]);
        $this->assertSame([false, false, true], array_column($kutuphane['ust'], 'aktif'));
    }

    public function test_menude_tek_risk_girisi_kalir_ve_grup_adi_degisti(): void
    {
        $grup = collect(Filament::getNavigation())->first(fn ($g) => $g->getLabel() === 'Risk & Tehlike Yönetimi');
        $etiketler = $grup->getItems()->map(fn ($i) => $i->getLabel())->values()->all();

        $this->assertSame(['Risk Yönetim Merkezi'], $etiketler);

        foreach ([JsaDegerlendirmesi::class, KimyasalYonetimi::class, TehlikeRiskAnalitigi::class, KimyasalSicili::class,
            TehlikeResource::class, RiskSablonuResource::class, RiskProsedurResource::class, RiskDegerlendirmesiResource::class] as $sinif) {
            $this->assertFalse($sinif::shouldRegisterNavigation(), $sinif.' menüde görünmemeli');
        }
    }

    public function test_sekme_seridi_her_uc_merkezde_gorunur_eski_adresler_calisir(): void
    {
        foreach ([RiskSihirbazi::getUrl(), JsaDegerlendirmesi::getUrl(), KimyasalYonetimi::getUrl(), TehlikeRiskAnalitigi::getUrl(),
            TehlikeResource::getUrl('index'), RiskSablonuResource::getUrl('index'), RiskProsedurResource::getUrl('index')] as $url) {
            $this->get($url)->assertSuccessful()->assertSee('Merkezi Kütüphane &amp; Şablonlar', false);
        }
    }

    public function test_kutuphane_ve_sablon_listeleri_tek_ice_disa_aktar_menusu_kullanir(): void
    {
        $tehlike = Livewire::test(\App\Filament\Resources\Tehlikes\Pages\ListTehlikes::class)
            ->assertSee('Yeni Tehlike / Ekipman Tanımla')
            ->assertSee('Sektör / NACE')
            ->assertSee('Ekipman');
        $this->assertSame(1, substr_count($tehlike->html(), 'İçe / Dışa Aktar'));

        $sablon = Livewire::test(\App\Filament\Resources\RiskSablonus\Pages\ListRiskSablonus::class)->assertSee('Sihirbazdan Yeni Şablon');
        $this->assertSame(1, substr_count($sablon->html(), 'İçe / Dışa Aktar'));
    }

    public function test_firma_adli_sablon_toplu_olarak_nace_sektorune_baglanir(): void
    {
        $uzman = auth()->user();
        $a = RiskSablonu::olustur($uzman, 'Bursa Şehir Hastanesi', null, 'Bursa Şehir Hastanesi', 'fine_kinney', []);
        $b = RiskSablonu::olustur($uzman, 'ABC Makine A.Ş.', null, 'ABC Makine A.Ş.', 'fine_kinney', []);

        $this->assertSame('Bursa Şehir Hastanesi', $a->sektorEtiketi());
        $this->assertArrayHasKey('metal', RiskSablonu::sektorSecenekleri());

        Livewire::test(\App\Filament\Resources\RiskSablonus\Pages\ListRiskSablonus::class)
            ->selectTableRecords([$a->id, $b->id])
            ->callAction(TestAction::make('sektoreAta')->table()->bulk(), data: ['sektor' => 'metal'])
            ->assertHasNoFormErrors();

        $this->assertSame('Metal ve makine imalatı', $a->fresh()->sektorEtiketi());
        $this->assertNull($b->fresh()->sektor_adi);
    }
}
