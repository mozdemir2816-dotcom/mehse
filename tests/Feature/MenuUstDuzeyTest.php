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

    public function test_sade_menu_ust_duzey_ve_dort_grup(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $menu = collect(Filament::getNavigation());

        // 08.11.2026 sade menü: Ana Sayfa + Kontrol Merkezi üstte, altında 4 grup.
        $gorunen = $menu->filter(fn ($g) => $g->getItems()->isNotEmpty())
            ->mapWithKeys(fn ($g) => [(string) $g->getLabel() => $g->getItems()->map(fn ($i) => $i->getLabel())->values()->all()])->all();
        $this->assertSame(['', 'Günlük Saha İşi', 'Merkezler', 'Firmalar & Çalışanlar', 'Hesap & Arşiv'], array_keys($gorunen));
        $this->assertSame(['Ana Sayfa', 'Kontrol Merkezi'], $gorunen['']);
        $this->assertSame(['Saha', 'Ziyaret Planlama', 'Resmi Belgeler', 'KKD', 'Ekipman & Ölçüm', 'Arşiv'], $gorunen['Günlük Saha İşi']);
        $this->assertSame(['Risk Yönetimi', 'Eğitim & Sınav', 'Yıllık Planlar',
            'Sağlık & Kaza', 'Acil Durum & Yangın', 'İSG Kurulu'], $gorunen['Merkezler']);
        $this->assertSame(['Firmalar', 'Çalışanlar', 'İSG Profesyonelleri', 'Dış Paydaş', 'İSG-KATİP Robot'], $gorunen['Firmalar & Çalışanlar']);
        $this->assertSame(['Pazarlama & Talepler', 'OSGB Cari & Tahsilat', 'Abonelik & Fatura', 'Profil & Kaşe/İmza', 'Araçlar', 'Ayarlar'], $gorunen['Hesap & Arşiv']);

        $this->assertTrue(HizliSahaBulgusu::shouldRegisterNavigation());
        $this->assertFalse(SahaEkrani::shouldRegisterNavigation());
        $this->assertTrue(Profilim::shouldRegisterNavigation());
    }

    public function test_saha_ekrani_genel_ayarlar_sekmesinde_profilim_ayri_menude(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $ayarlar = SayfaAileleri::sekmeGruplari([SahaEkrani::class]);
        $this->assertSame('ayarlar', $ayarlar['aile']);
        $this->assertSame('Genel Ayarlar & Görünüm', collect($ayarlar['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertSame(['Ayarlar', 'Saha Ekranı'], array_column($ayarlar['alt'], 'etiket'));

        $this->assertNull(SayfaAileleri::sekmeGruplari([Profilim::class]));

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
