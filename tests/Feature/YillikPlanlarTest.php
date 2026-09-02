<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlanlar as PlanSayfasi;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\YillikPlanUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class YillikPlanlarTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_ve_yil_secilince_varsayilan_faaliyetler_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2027);

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2027)->firstOrFail();
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_faaliyetler')), $plan->faaliyetler);
        $this->assertSame(array_fill(0, 12, 'bos'), $plan->faaliyetler[0]['aylar']);
    }

    public function test_ay_durumu_tiklaninca_sirayla_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'faaliyetler', 0, 0);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('planlandi', $plan->faaliyetler[0]['aylar'][0]);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'faaliyetler', 0, 0);

        $this->assertSame('tamamlandi', $plan->fresh()->faaliyetler[0]['aylar'][0]);
    }

    public function test_farkli_yillar_ayri_plan_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2026);
        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2027);

        $this->assertSame(2, YillikPlan::where('firma_id', $firma->id)->count());
    }

    public function test_faaliyet_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFaaliyet', 'Özel Denetim')
            ->set('yeniSorumlu', 'İSG Uzmanı')
            ->call('faaliyetEkle');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_faaliyetler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->faaliyetler);
        $this->assertSame('Özel Denetim', $plan->faaliyetler[$varsayilanSayisi]['faaliyet']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('faaliyetSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->faaliyetler);
    }

    public function test_varsayilana_sifirlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id);
        $component->call('ayDurumDegistir', 'faaliyetler', 0, 0)->call('varsayilanaSifirla');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('bos', $plan->faaliyetler[0]['aylar'][0]);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $yanit = YillikPlanUretici::pdf($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(PlanSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_firma_secilince_varsayilan_egitimler_ve_degerlendirmeler_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_egitimler')), $plan->egitimler);
        $this->assertSame(array_fill(0, 12, 'bos'), $plan->egitimler[0]['aylar']);
        $this->assertCount(count(config('isg.yillik_plan.varsayilan_degerlendirmeler')), $plan->degerlendirmeler);
        $this->assertNull($plan->degerlendirmeler[0]['tarih']);
    }

    public function test_egitim_ay_durumu_degisir_ve_egitim_eklenip_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ayDurumDegistir', 'egitimler', 0, 0);

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('planlandi', $plan->egitimler[0]['aylar'][0]);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniEgitimKonu', 'Forklift Operatörlüğü')
            ->set('yeniEgitimEgitici', 'İSG Uzmanı')
            ->call('egitimEkle');

        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_egitimler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->fresh()->egitimler);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('egitimSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->egitimler);
    }

    public function test_degerlendirme_satiri_guncellenir_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('degerlendirmeGuncelle', 0, 'tarih', '2026-03-05')
            ->call('degerlendirmeGuncelle', 0, 'tekrar_sayisi', '2');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('2026-03-05', $plan->degerlendirmeler[0]['tarih']);
        $this->assertSame('2', $plan->degerlendirmeler[0]['tekrar_sayisi']);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniDegerlendirmeCalisma', 'Gürültü haritası güncellemesi')
            ->call('degerlendirmeEkle');

        $varsayilanSayisi = count(config('isg.yillik_plan.varsayilan_degerlendirmeler'));
        $this->assertCount($varsayilanSayisi + 1, $plan->fresh()->degerlendirmeler);

        Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('degerlendirmeSil', $varsayilanSayisi);

        $this->assertCount($varsayilanSayisi, $plan->fresh()->degerlendirmeler);
    }

    public function test_egitim_sekmesi_varsayilana_sifirlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(PlanSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sekme', 'egitim')
            ->call('ayDurumDegistir', 'egitimler', 0, 0)
            ->call('varsayilanaSifirla');

        $plan = YillikPlan::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('bos', $plan->egitimler[0]['aylar'][0]);
    }
}
