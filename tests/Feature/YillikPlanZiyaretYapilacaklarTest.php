<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlanlar as PlanSayfasi;
use App\Filament\Pages\ZiyaretProgrami as ZiyaretSayfasi;
use App\Filament\Widgets\BuAyZiyaretlerWidget;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Yıllık Çalışma Planı P/G hücreleri + ziyaret yapılacaklar listesinin plandan
 * otomatik gelmesi (01.10.2026, kullanıcı isteği).
 */
class YillikPlanZiyaretYapilacaklarTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function firma(): Firma
    {
        return Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => null]);
    }

    public function test_p_ve_g_hucreleri_ay_durumunu_ayarlar(): void
    {
        $firma = $this->firma();
        $sayfa = Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2026);
        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2026)->firstOrFail();
        $this->assertSame('bos', $plan->faaliyetler[0]['aylar'][0]);

        $sayfa->call('ayHucresiDegistir', 'faaliyetler', 0, 0, 'P');
        $this->assertSame('planlandi', $plan->fresh()->faaliyetler[0]['aylar'][0]);

        $sayfa->call('ayHucresiDegistir', 'faaliyetler', 0, 0, 'G');
        $this->assertSame('tamamlandi', $plan->fresh()->faaliyetler[0]['aylar'][0]);

        $sayfa->call('ayHucresiDegistir', 'faaliyetler', 0, 0, 'G');
        $this->assertSame('planlandi', $plan->fresh()->faaliyetler[0]['aylar'][0]);

        $sayfa->call('ayHucresiDegistir', 'faaliyetler', 0, 0, 'P');
        $this->assertSame('bos', $plan->fresh()->faaliyetler[0]['aylar'][0]);
    }

    public function test_cizelge_excel_duzeninde_gosterilir(): void
    {
        $firma = $this->firma();

        Livewire::test(PlanSayfasi::class)->set('firmaId', $firma->id)->set('yil', 2026)
            ->assertSee('Mevzuat Dayanağı / Kayıt-Kanıt / Açıklama')
            ->assertSee('PLANLAMA VE DOKÜMANTASYON')
            ->assertSee('Yıllık çalışma planının hazırlanması / gözden geçirilmesi')
            ->assertSee('İşveren / İGU / İşyeri Hekimi');
    }

    public function test_genel_durum_planlandi_ve_gerceklesti_metni(): void
    {
        $this->assertSame('Planlandı', YillikPlan::faaliyetDurumu(['planlandi', 'bos']));
        $this->assertSame('Devam Ediyor', YillikPlan::faaliyetDurumu(['tamamlandi', 'planlandi']));
        $this->assertSame('Gerçekleşti', YillikPlan::faaliyetDurumu(['tamamlandi', 'bos']));
        $this->assertSame('—', YillikPlan::faaliyetDurumu(['bos']));
    }

    public function test_ayin_yapilacaklari_o_ay_planlanan_maddelerdir(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->firma(), 2026);

        $ekim = collect($plan->ayinYapilacaklari(9));
        $basliklar = $ekim->pluck('baslik');

        // Şablonda yalnız Ekim'de P olan maddeler + her ay P olanlar gelir.
        $this->assertTrue($basliklar->contains('Acil durum planının gözden geçirilmesi ve gerektiğinde güncellenmesi'));
        $this->assertTrue($basliklar->contains('Saha gözetimi, tespit ve önerilerin kayıt altına alınması'));
        // Yalnız Aralık'ta olan madde Ekim listesinde yok.
        $this->assertFalse($basliklar->contains('Yıllık çalışma planının hazırlanması / gözden geçirilmesi'));
        // Eğitim planındaki maddeler de listeye girer.
        $this->assertTrue($ekim->contains('alan', 'egitimler'));
        $this->assertFalse($ekim->contains('gerceklesti', true));
    }

    public function test_ziyaret_programinda_ayin_yapilacaklari_gelir_ve_gerceklesti_plana_yazilir(): void
    {
        $firma = $this->firma();

        $sayfa = Livewire::test(ZiyaretSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->call('yapilacakAySec', 9)
            ->assertSee('Ekim Ziyaretinde Yapılacaklar')
            ->assertSee('Acil durum planının gözden geçirilmesi ve gerektiğinde güncellenmesi');

        $plan = YillikPlan::where('firma_id', $firma->id)->where('yil', 2026)->firstOrFail();
        $madde = collect($plan->ayinYapilacaklari(9))
            ->firstWhere('baslik', 'Acil durum planının gözden geçirilmesi ve gerektiğinde güncellenmesi');

        $sayfa->call('yapilacakGerceklesti', $madde['alan'], $madde['index']);
        $this->assertSame('tamamlandi', $plan->fresh()->faaliyetler[$madde['index']]['aylar'][9]);

        // Tekrar tıklama geri alır (P olarak kalır).
        $sayfa->call('yapilacakGerceklesti', $madde['alan'], $madde['index']);
        $this->assertSame('planlandi', $plan->fresh()->faaliyetler[$madde['index']]['aylar'][9]);
    }

    public function test_widget_ziyaretin_yapilacak_sayisini_gosterir(): void
    {
        $firma = $this->firma();
        $plan = YillikPlan::firmaYilIcin($firma, (int) now()->year);
        $ay = now()->month - 1;
        $toplam = count($plan->ayinYapilacaklari($ay));

        $program = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->year);
        $ziyaretler = $program->ziyaretler;
        $ziyaretler[$ay] = [[...ZiyaretProgrami::bosGirdi(), 'tarih' => now()->startOfMonth()->addDays(2)->toDateString(), 'durum' => 'planlandi']];
        $program->update(['ziyaretler' => $ziyaretler]);

        $this->assertGreaterThan(0, $toplam);

        Livewire::test(BuAyZiyaretlerWidget::class)
            ->assertSee('0/'.$toplam.' iş');
    }
}
