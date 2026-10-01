<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Yıllık planlar üç ayrı menü sayfası (01.10.2026, kullanıcı isteği): her
 * sayfa yalnız kendi bölümünü gösterir, aynı firma + yıl kaydını düzenler.
 */
class YillikPlanSayfalariTest extends TestCase
{
    use RefreshDatabase;

    public function test_her_sayfa_yalniz_kendi_bolumunu_gosterir(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create();

        Livewire::test(YillikCalismaPlani::class)->set('firmaId', $firma->id)
            ->assertSet('sekme', 'calisma')
            ->assertSee('İş Sağlığı ve Güvenliği Yıllık Çalışma Planı')
            ->assertDontSeeHtml('placeholder="Eğitim konusu"');

        Livewire::test(YillikEgitimPlani::class)->set('firmaId', $firma->id)
            ->assertSet('sekme', 'egitim')
            ->assertSee('Yıllık Eğitim Planı —')
            ->assertSeeHtml('placeholder="Eğitim konusu"')
            ->assertDontSee('Mevzuat Dayanağı / Kayıt-Kanıt / Açıklama');

        Livewire::test(YillikDegerlendirmeRaporu::class)->set('firmaId', $firma->id)
            ->assertSet('sekme', 'degerlendirme')
            ->assertDontSee('Mevzuat Dayanağı / Kayıt-Kanıt / Açıklama');
    }

    public function test_sayfa_turu_istemciden_degistirilemez(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(YillikCalismaPlani::class)->set('sekme', 'egitim');
    }

    public function test_menude_uc_ayri_sayfa_ve_eski_adres_yonlendirir(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);

        $this->get(YillikCalismaPlani::getUrl())->assertOk();
        $this->get(YillikEgitimPlani::getUrl())->assertOk();
        $this->get(YillikDegerlendirmeRaporu::getUrl())->assertOk();
        $this->get('/admin/yillik-planlar')->assertRedirect('/admin/yillik-calisma-plani');
    }

    public function test_eski_yillik_planlar_yetkisi_uc_sayfada_da_gecerli(): void
    {
        $kisitli = User::factory()->kisitli()->create();
        $this->actingAs($kisitli);

        $this->assertFalse(YillikEgitimPlani::canAccess());

        $kisitli->sayfaYetkileri()->create(['sayfa_anahtari' => 'yillik-planlar']);

        $this->assertTrue(YillikCalismaPlani::canAccess());
        $this->assertTrue(YillikEgitimPlani::canAccess());
        $this->assertTrue(YillikDegerlendirmeRaporu::canAccess());
    }
}
