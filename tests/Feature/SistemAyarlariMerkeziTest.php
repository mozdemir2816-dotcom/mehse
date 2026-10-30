<?php

namespace Tests\Feature;

use App\Filament\Pages\Ayarlar;
use App\Filament\Pages\Basvurular;
use App\Filament\Pages\Guvenlik;
use App\Filament\Pages\IseOzguEgitimKonulari;
use App\Filament\Pages\IsgKatipRobot;
use App\Filament\Pages\KullaniciYonetimi;
use App\Filament\Pages\Mevzuat;
use App\Models\User;
use App\Support\KontrolKriterGruplari;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Sistem Yönetim & Entegrasyon Merkezi: tek menü girişi, 3 ana sekme, kontrol kriterleri kategorileri. */
class SistemAyarlariMerkeziTest extends TestCase
{
    use RefreshDatabase;

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $menu = collect(Filament::getNavigation());
        $this->assertNull($menu->first(fn ($g) => $g->getLabel() === 'Ayarlar ve Kütüphaneler'));
        $this->assertCount(1, $menu->flatMap(fn ($g) => $g->getItems())->filter(fn ($i) => $i->getLabel() === 'Ayarlar'));
        $this->assertFalse(Mevzuat::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([Mevzuat::class]);
        $this->assertSame('ayarlar', $g['aile']);
        $this->assertSame(
            ['Genel Ayarlar & Görünüm', 'Güvenlik, Oturumlar & Ekip Yetkileri', 'İSG-KATİP Robotu & Mevzuat Kütüphanesi'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame('İSG-KATİP Robotu & Mevzuat Kütüphanesi', collect($g['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertCount(4, $g['alt']);
        $this->assertCount(3, SayfaAileleri::sekmeGruplari([Guvenlik::class])['alt']);

        foreach ([Ayarlar::class, Guvenlik::class, KullaniciYonetimi::class, Basvurular::class, IsgKatipRobot::class, Mevzuat::class, IseOzguEgitimKonulari::class] as $sayfa) {
            $this->get($sayfa::getUrl())->assertSuccessful()->assertSee('Genel Ayarlar &amp; Görünüm', false);
        }
    }

    public function test_kontrol_kriterleri_dort_kategoride_ve_hizli_secimler(): void
    {
        $tum = KontrolKriterGruplari::tumAnahtarlar();
        $gruplu = collect(KontrolKriterGruplari::gruplar())->flatMap(fn ($g) => KontrolKriterGruplari::grubunAnahtarlari($g))->all();

        $this->assertEqualsCanonicalizing($tum, $gruplu);   // her kriter tam bir kategoride
        $this->assertSame(array_unique($gruplu), $gruplu);

        $yasal = KontrolKriterGruplari::yasalAnahtarlar();
        $this->assertContains('risk_degerlendirmesi', $yasal);
        $this->assertContains('isg_kurulu', $yasal);
        $this->assertNotContains('saglik_raporu', $yasal);   // "takip" kriteri
        $this->assertNotContains('diger_evrak', $yasal);

        $sirali = array_column(KontrolKriterGruplari::sirali(), 'anahtar');
        $this->assertSame(KontrolKriterGruplari::grubunAnahtarlari(KontrolKriterGruplari::YASAL), array_slice($sirali, 0, count(KontrolKriterGruplari::grubunAnahtarlari(KontrolKriterGruplari::YASAL))));
    }

    public function test_ayarlar_sayfasi_hizli_secim_dugmelerini_gosterir(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        Livewire::test(Ayarlar::class)
            ->assertSee('Yalnızca Yasal Olanları Seç')
            ->assertSee('Tümünü Seç')
            ->assertSee('Eğitim ve Kurul')
            ->assertSee('Sağlık ve Olaylar');
    }

    public function test_modern_tema_secilebilir_ve_hesaba_kaydedilir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($u);

        $this->assertArrayHasKey('modern', \App\Support\KullaniciAyarlari::TEMALAR);
        $this->assertSame('mehse', \App\Support\KullaniciAyarlari::VARSAYILAN_TEMA);   // yeni tema varsayılanı değiştirmez

        $this->post(route('mehse.ayar.tema'), ['tema' => 'modern'])->assertNoContent();
        $this->assertSame('modern', \App\Support\KullaniciAyarlari::tema($u->fresh()));

        $this->post(route('mehse.ayar.tema'), ['tema' => 'olmayan'])->assertSessionHasErrors('tema');
    }
}
