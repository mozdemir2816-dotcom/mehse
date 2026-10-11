<?php

namespace Tests\Feature;

use App\Filament\Pages;
use App\Models\User;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menü sadeleştirmesi (11.10.2026): aileye ait alt sayfalar menüden gizlenir,
 * ana sayfanın sekme şeridinden açılır; URL'ler değişmez.
 */
class MenuSadelestirmeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_her_ailenin_yalniz_ana_sayfasi_menude(): void
    {
        foreach (SayfaAileleri::aileler() as $ad => $uyeler) {
            foreach ($uyeler as $i => [$sinif]) {
                $this->assertSame($i === 0, $sinif::shouldRegisterNavigation(), "$ad ailesi: $sinif");
            }
        }
    }

    public function test_gizli_sayfalar_hala_acilir_ve_sekme_seridi_gosterir(): void
    {
        $this->get(Pages\SertifikaOlustur::getUrl())
            ->assertSuccessful()
            ->assertSee('mehse-sayfa-sekmeleri', false)
            ->assertSee('Eğitim Katılım');

        $this->get(Pages\KazaIstatistikleri::getUrl())
            ->assertSuccessful()
            ->assertSee('Kaza İnceleme Raporu');
    }

    public function test_ana_sayfa_gizli_sekmelerde_de_menude_vurgulu(): void
    {
        foreach (SayfaAileleri::aileler() as $ad => $uyeler) {
            $desen = (array) $uyeler[0][0]::getNavigationItemActiveRoutePattern();
            $this->assertCount(count($uyeler), $desen, "$ad ailesi ana sayfasında AileAnaSayfasi trait'i eksik");
        }

        $this->assertContains(
            \App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource::getRouteBaseName().'.*',
            Pages\RiskSihirbazi::getNavigationItemActiveRoutePattern(),
        );
    }

    public function test_ailesiz_sayfada_sekme_seridi_yok(): void
    {
        $this->get(Pages\TatbikatTutanagi::getUrl())
            ->assertSuccessful()
            ->assertDontSee('mehse-sayfa-sekmeleri', false);
    }

    public function test_sekmeler_aktif_sayfayi_isaretler_ve_firma_parametresini_tasir(): void
    {
        $this->get('/admin/saglik-gozetimi?firma=7');

        $sekmeler = SayfaAileleri::sekmeler([Pages\MuayeneFormu::class]);

        $this->assertSame(['Sağlık Gözetimi', 'Muayene Formu (EK-2)', 'İşe Dönüş Belgesi', 'Meslek Hastalığı Bildirimi'], array_column($sekmeler, 'etiket'));
        $this->assertTrue($sekmeler[1]['aktif']);
        $this->assertFalse($sekmeler[0]['aktif']);
        $this->assertSame([], SayfaAileleri::sekmeler([Pages\TatbikatTutanagi::class]));
    }

    public function test_menude_arama_kutusu_ve_gizli_sayfa_sonuclari_var(): void
    {
        $this->get(Pages\KontrolMerkezi::getUrl())
            ->assertSuccessful()
            ->assertSee('Menüde ara', false)
            // gizli sayfalar arama verisinde (JSON, Türkçe karakterler kaçışlı)
            ->assertSee('e-recetem', false)
            ->assertSee('kaza-istatistikleri', false);
    }

    public function test_ayarlar_ve_kutuphaneler_grubu_kapali_baslar(): void
    {
        $grup = collect(Filament::getPanel('admin')->getNavigationGroups())
            ->first(fn ($g) => $g->getLabel() === 'Ayarlar ve Kütüphaneler');

        $this->assertTrue($grup->isCollapsed());
    }
}
