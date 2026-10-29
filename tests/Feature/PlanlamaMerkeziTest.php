<?php

namespace Tests\Feature;

use App\Filament\Pages\SahaTakvimi;
use App\Filament\Pages\ZiyaretProgrami as PlanlamaMerkezi;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use App\Support\YasalZiyaretSuresi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Saha & Ziyaret Planlama Merkezi (29.10.2026): görünüm seçici + otomatik yasal süre. */
class PlanlamaMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function firma(string $sinif, int $calisan): Firma
    {
        return Firma::factory()->for($this->uzman)->create(['unvan' => 'Beta '.$sinif, 'tehlike_sinifi' => $sinif, 'calisan_sayisi' => $calisan, 'katip_aylik_dk' => null]);
    }

    public function test_yasal_sure_tehlike_sinifina_gore_hesaplanir_ve_okunur_yazilir(): void
    {
        $this->assertSame(100, YasalZiyaretSuresi::dakika($this->firma('az_tehlikeli', 10)));   // 10 × 10
        $this->assertSame(400, YasalZiyaretSuresi::dakika($this->firma('tehlikeli', 20)));      // 20 × 20
        $this->assertSame(480, YasalZiyaretSuresi::dakika($this->firma('cok_tehlikeli', 12)));  // 12 × 40

        $this->assertSame('3 sa 20 dk', YasalZiyaretSuresi::etiket(200));
        $this->assertSame('8 sa', YasalZiyaretSuresi::etiket(480));
        $this->assertSame('45 dk', YasalZiyaretSuresi::etiket(45));
        $this->assertSame(3.33, YasalZiyaretSuresi::saat(200));
        $this->assertSame(200, YasalZiyaretSuresi::dakikayaCevir(3.33));
    }

    public function test_onerilen_siklik_tehlike_sinifina_gore(): void
    {
        $this->assertSame('Her ay', YasalZiyaretSuresi::siklik('cok_tehlikeli')['etiket']);
        $this->assertSame('Ayda bir', YasalZiyaretSuresi::siklik('tehlikeli')['etiket']);
        $this->assertSame('2 ayda bir', YasalZiyaretSuresi::siklik('az_tehlikeli')['etiket']);

        $this->assertTrue(YasalZiyaretSuresi::ayOnerilirMi('az_tehlikeli', 0));
        $this->assertFalse(YasalZiyaretSuresi::ayOnerilirMi('az_tehlikeli', 1));
        $this->assertTrue(YasalZiyaretSuresi::ayOnerilirMi('cok_tehlikeli', 7));
    }

    public function test_gorunum_secici_ajanda_ve_yillik_plan_arasinda_gecer(): void
    {
        $firma = $this->firma('cok_tehlikeli', 5); // 200 dk

        Livewire::test(PlanlamaMerkezi::class, ['firmaId' => $firma->id])
            ->assertSet('gorunum', 'plan')
            ->assertSee('Firma Bazlı Yıllık Plan (12 Ay)')
            ->assertSee('Aylık yasal asgari süre')
            ->assertSee('3 sa 20 dk')
            ->assertSee('Her ay')
            ->assertSee('Boş Aylara Yasal Süreyi Yaz')
            ->call('gorunumSec', 'ajanda')
            ->assertSet('gorunum', 'ajanda')
            ->assertSee('Salt okunur')
            ->assertDontSee('Aylık yasal asgari süre')
            ->call('gorunumSec', 'gecersiz')
            ->assertSet('gorunum', 'ajanda');
    }

    public function test_bos_aylara_yasal_sure_yazilir_elle_girilene_dokunulmaz(): void
    {
        $firma = $this->firma('cok_tehlikeli', 5); // 200 dk → 3.33 sa
        $program = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->format('Y'));
        $ziyaretler = $program->ziyaretler;
        $ziyaretler[2][0]['sure_saat'] = 6; // Mart: elle girilmiş
        $program->update(['ziyaretler' => $ziyaretler]);

        Livewire::test(PlanlamaMerkezi::class, ['firmaId' => $firma->id])->call('yasalSureyiYaz');

        $ay = $program->fresh()->ziyaretler;
        $this->assertSame(3.33, $ay[0][0]['sure_saat']);
        $this->assertSame(3.33, $ay[11][0]['sure_saat']);
        $this->assertEquals(6, $ay[2][0]['sure_saat'], 'Elle girilen süre korunmalı.');
    }

    public function test_tarih_girilince_bos_sure_yasal_sureyle_dolar(): void
    {
        $firma = $this->firma('tehlikeli', 15); // 300 dk → 5 sa
        $program = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->format('Y'));

        Livewire::test(PlanlamaMerkezi::class, ['firmaId' => $firma->id])
            ->call('ayGuncelle', 3, 0, 'tarih', now()->format('Y').'-04-14');

        $girdi = $program->fresh()->ziyaretler[3][0];
        $this->assertSame(now()->format('Y').'-04-14', $girdi['tarih']);
        $this->assertEquals(5, $girdi['sure_saat']);

        // Süre zaten doluysa tarih değişikliği süreyi ezmez.
        Livewire::test(PlanlamaMerkezi::class, ['firmaId' => $firma->id])
            ->call('ayGuncelle', 3, 0, 'sure_saat', '2')
            ->call('ayGuncelle', 3, 0, 'tarih', now()->format('Y').'-04-20');
        $this->assertEquals(2, $program->fresh()->ziyaretler[3][0]['sure_saat']);
    }

    public function test_katip_suresi_varsa_yasal_sure_o_olur(): void
    {
        $firma = $this->firma('tehlikeli', 15);
        $firma->update(['katip_aylik_dk' => 360]);

        Livewire::test(PlanlamaMerkezi::class, ['firmaId' => $firma->id])
            ->assertSee('6 sa')
            ->assertSee('İSG-KATİP sözleşme süresi');
    }

    public function test_eski_saha_takvimi_adresi_ajanda_gorunumune_yonlenir(): void
    {
        $this->get(SahaTakvimi::getUrl(['tarih' => '2026-05-20', 'ay' => '2026-05']))
            ->assertRedirect(PlanlamaMerkezi::getUrl(['gorunum' => 'ajanda', 'ajanda_tarih' => '2026-05-20', 'ajanda_ay' => '2026-05']));
    }

    public function test_menude_tek_planlama_girisi_var(): void
    {
        $this->get(PlanlamaMerkezi::getUrl())->assertOk()->assertSee('Saha & Ziyaret Planlama Merkezi')->assertSee('Genel Ajanda (Portföy Takvimi)');
    }
}
