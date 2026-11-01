<?php

namespace Tests\Feature;

use App\Filament\Pages\Ayarlar;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\Profilim;
use App\Filament\Pages\SahaEkrani;
use App\Models\User;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Saha üst düzey menü girişi; Saha Ekranı Ayarlar'ın, Profilim Kontrol Merkezi'nin alt sayfası (30.10.2026). */
class MenuUstDuzeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saha_ust_duzey_giris_saha_ekrani_ve_profilim_genel_grubundan_cikti(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $menu = collect(Filament::getNavigation());
        $ustDuzey = $menu->first(fn ($g) => $g->getLabel() === null)->getItems()->map(fn ($i) => $i->getLabel())->all();
        $this->assertContains('Saha', $ustDuzey);

        $genel = $menu->first(fn ($g) => $g->getLabel() === 'Genel')->getItems()->map(fn ($i) => $i->getLabel())->all();
        $this->assertSame(['Kontrol Merkezi', 'Ziyaret Planlama', 'Dış Paydaş', 'Pazarlama & Talepler', 'Fatura & Profil'], $genel);

        $this->assertTrue(HizliSahaBulgusu::shouldRegisterNavigation());
        $this->assertFalse(SahaEkrani::shouldRegisterNavigation());
        $this->assertFalse(Profilim::shouldRegisterNavigation());
    }

    public function test_saha_ekrani_genel_ayarlar_sekmesinde_profilim_portfoy_genel_bakista(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $ayarlar = SayfaAileleri::sekmeGruplari([SahaEkrani::class]);
        $this->assertSame('ayarlar', $ayarlar['aile']);
        $this->assertSame('Genel Ayarlar & Görünüm', collect($ayarlar['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertSame(['Ayarlar', 'Saha Ekranı'], array_column($ayarlar['alt'], 'etiket'));

        $kontrol = SayfaAileleri::sekmeGruplari([Profilim::class]);
        $this->assertSame('kontrol', $kontrol['aile']);
        $this->assertSame('Portföy Genel Bakış', collect($kontrol['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertSame(['Portföy Genel Bakış', 'Profilim'], array_column($kontrol['alt'], 'etiket'));

        foreach ([SahaEkrani::class, Profilim::class, Ayarlar::class, KontrolMerkezi::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful();
        }
    }

    public function test_kontrol_merkezi_yetkisi_olmayan_kullanici_profilimi_menude_gorur(): void
    {
        $kisitli = User::factory()->create(['rol' => 'uye']);
        $kisitli->sayfaYetkileri()->create(['sayfa_anahtari' => 'profilim']);
        $this->actingAs($kisitli);

        $this->assertFalse(KontrolMerkezi::canAccess());
        $this->assertTrue(Profilim::shouldRegisterNavigation());
    }
}
