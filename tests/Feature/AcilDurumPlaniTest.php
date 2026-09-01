<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilDurumPlaniUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AcilDurumPlaniTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_icin_plan_varsayilan_konular_ve_belge_no(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $plan = AcilDurumPlani::firmaIcin($firma);

        $this->assertTrue($plan->exists);
        $this->assertStringStartsWith('AD-'.now()->year.'-', $plan->dokuman_no);
        $this->assertContains('yangin', $plan->konular);
        $this->assertContains('deprem', $plan->konular);
        $this->assertNotContains('sel', $plan->konular); // varsayılan false
        // çok tehlikeli → 2 yıl geçerlilik
        $this->assertSame(
            $plan->rapor_tarihi->copy()->addYears(2)->toDateString(),
            $plan->gecerlilik_tarihi->toDateString(),
        );
    }

    public function test_sayfa_firma_secilince_formu_doldurur_ve_kaydeder(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AcilDurumSayfasi::class)
            ->assertOk()
            ->set('firmaId', $firma->id)
            ->assertSet('kapakCercevesi', 'klasik')
            ->call('konuToggle', 'sel')            // ekle
            ->call('konuToggle', 'yangin')          // çıkar
            ->set('ekipMetni.sondurme', 'Ali Veli, Ayşe Fatma')
            ->set('kapakCercevesi', 'altin')
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $this->assertContains('sel', $plan->konular);
        $this->assertNotContains('yangin', $plan->konular);
        $this->assertSame('altin', $plan->kapak_cercevesi);
        $this->assertSame(['Ali Veli', 'Ayşe Fatma'], $plan->ekipListesi()['sondurme']);
    }

    public function test_tum_konular_secilir_ve_kaldirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumKonular', true);

        $this->assertCount(count(config('isg.acil_durum.konular')), $component->get('konular'));

        $component->call('tumKonular', false);
        $this->assertCount(0, $component->get('konular'));
    }

    public function test_plan_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->update(['ekipler' => ['sondurme' => ['Ali Veli']]]);

        $yanit = AcilDurumPlaniUretici::pdf($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_afis_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = AcilDurumPlaniUretici::afis($firma, 'yangin', 'a4');

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecersiz_afis_tipi_404(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        AcilDurumPlaniUretici::afis($firma, 'gecersiz', 'a4');
    }
}
