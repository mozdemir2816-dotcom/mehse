<?php

namespace Tests\Feature;

use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\EgitimKonulari;
use App\Support\YillikEgitimPlanlayici;
use App\Support\YillikPlanExcelUretici;
use App\Support\YillikPlanUyarlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Yıllık Eğitim Planı Online / Yüz Yüze (Karma) planlama, RG 02.04.2026 (33212)
 * mevzuat kilidi, Excel çıktısı ve LMS tamamlama senkronizasyonu testleri.
 */
class YillikEgitimOnlineYuzYuzeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_tehlike_sinifina_gore_karma_lms_saat_dagilimi_dogru_hesaplanir(): void
    {
        $cok = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '41.00.01']);
        $teh = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);
        $az = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli', 'nace_kodu' => '62.01.01']);

        // Çok tehlikeli: 16 saat = 8s Online + 8s Yüz Yüze (Tekrar: 4s Online + 4s Yüz Yüze)
        $planCok = YillikPlan::firmaYilIcin($cok, 2026);
        $ozetCok = YillikEgitimPlanlayici::ozet($planCok->egitimler, $cok);
        $this->assertTrue($ozetCok['uyumlu']);
        $this->assertSame(8.0, $ozetCok['ilk_online']);
        $this->assertSame(8.0, $ozetCok['ilk_yuz_yuze']);
        $this->assertSame(16.0, $ozetCok['ilk_toplam']);
        $this->assertSame(4.0, $ozetCok['tekrar_online']);
        $this->assertSame(4.0, $ozetCok['tekrar_yuz_yuze']);

        // Tehlikeli: 12 saat = 8s Online + 4s Yüz Yüze
        $planTeh = YillikPlan::firmaYilIcin($teh, 2026);
        $ozetTeh = YillikEgitimPlanlayici::ozet($planTeh->egitimler, $teh);
        $this->assertTrue($ozetTeh['uyumlu']);
        $this->assertSame(8.0, $ozetTeh['ilk_online']);
        $this->assertSame(4.0, $ozetTeh['ilk_yuz_yuze']);
        $this->assertSame(12.0, $ozetTeh['ilk_toplam']);

        // Az tehlikeli: 8 saat = 8s Online + 0s Yüz Yüze
        $planAz = YillikPlan::firmaYilIcin($az, 2026);
        $ozetAz = YillikEgitimPlanlayici::ozet($planAz->egitimler, $az);
        $this->assertTrue($ozetAz['uyumlu']);
        $this->assertSame(8.0, $ozetAz['ilk_online']);
        $this->assertSame(0.0, $ozetAz['ilk_yuz_yuze']);
        $this->assertSame(8.0, $ozetAz['ilk_toplam']);
    }

    public function test_dorduncu_bolum_ve_diger_egitimler_tehlikeli_siniflarda_online_yapilamaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '41.00.01']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $iseOzguIdx = collect($plan->egitimler)->search(fn ($e) => ($e['kategori'] ?? null) === 'ise_ozgu');
        $genelIdx = collect($plan->egitimler)->search(fn ($e) => ($e['kategori'] ?? null) === 'genel');

        $this->assertNotFalse($iseOzguIdx);
        $this->assertNotFalse($genelIdx);

        // 4. bölüm tıklanınca yüz yüze kilitli kalmalı ve uyarı bildirimi göstermeli
        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->call('egitimYontemDegistir', (int) $iseOzguIdx)
            ->assertNotified();

        $this->assertSame('yuz_yuze', $plan->fresh()->egitimler[$iseOzguIdx]['yontem']);

        // Genel konu tıklanınca uzaktan → yüz yüze → karma döngüsüne girebilmeli
        $this->assertSame('uzaktan', $plan->fresh()->egitimler[$genelIdx]['yontem']);
        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->call('egitimYontemDegistir', (int) $genelIdx);

        $this->assertSame('yuz_yuze', $plan->fresh()->egitimler[$genelIdx]['yontem']);
    }

    public function test_mod_degisimi_ve_excel_ciktisinda_online_yuz_yuze_kirilimi_basilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek İnşaat A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
            'nace_kodu' => '41.00.01',
        ]);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        // Varsayılan karma_lms Excel kontrolü
        $s = YillikPlanExcelUretici::egitimDoldur($plan)->getSheet(0);
        $this->assertStringContainsString('ONLİNE (UZAKTAN)', (string) $s->getCell('D7')->getValue());
        $this->assertStringContainsString('YÜZ YÜZE', (string) $s->getCell('D28')->getValue());
        $this->assertStringContainsString('RG 02.04.2026 (33212) Md.12/3', (string) $s->getCell('BA28')->getValue());
        $this->assertStringContainsString('16 SAAT (8s ONLİNE + 8s YÜZ YÜZE)', (string) $s->getCell('C42')->getValue());

        // Modu karma_tavan'a geçir: 12s Online + 4s Yüz Yüze
        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->set('egitimPlanModu', 'karma_tavan')
            ->call('egitimModuUygula')
            ->assertNotified();

        $s2 = YillikPlanExcelUretici::egitimDoldur($plan->fresh())->getSheet(0);
        $this->assertStringContainsString('16 SAAT (12s ONLİNE + 4s YÜZ YÜZE)', (string) $s2->getCell('C42')->getValue());
    }

    public function test_baska_firmaya_uyarlamada_online_yuz_yuze_saatler_hedef_sinifa_uyarlanir(): void
    {
        $kaynakFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '41.00.01']);
        $hedefFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '25.62.01']);

        $kaynakPlan = YillikPlan::firmaYilIcin($kaynakFirma, 2026);
        $hedefPlan = YillikPlanUyarlayici::uyarla($kaynakPlan, $hedefFirma, ['egitimler']);

        $ozet = YillikEgitimPlanlayici::ozet($hedefPlan->egitimler, $hedefFirma);
        $this->assertSame(8.0, $ozet['ilk_online']);
        $this->assertSame(4.0, $ozet['ilk_yuz_yuze']);
        $this->assertSame(12.0, $ozet['ilk_toplam']);
    }

    public function test_tamamlanan_lms_atamasi_yillik_egitim_planinda_online_konulari_tamamlandi_isaretler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '41.00.01']);
        $calisan = Calisan::factory()->for($firma)->create();
        $paket = EgitimPaketi::create([
            'user_id' => $this->uzman->id,
            'ad' => 'Temel İSG 2026',
            'egitim_turu' => 'ilk_defa',
            'gecme_puani' => 60,
            'aktif' => true,
        ]);

        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        EgitimAtamasi::create([
            'calisan_id' => $calisan->id,
            'egitim_paketi_id' => $paket->id,
            'atayan_user_id' => $this->uzman->id,
            'egitim_turu' => 'ilk_defa',
            'durum' => 'tamamlandi',
            'atandi_at' => '2026-03-01 10:00:00',
            'tamamlandi_at' => '2026-03-15 14:00:00',
        ]);

        $isaretlenen = EgitimKonulari::kayitlariPlanaIsle($plan);
        $this->assertGreaterThan(0, $isaretlenen);

        $guncel = $plan->fresh()->egitimler;
        $genel = collect($guncel)->firstWhere('kategori', 'genel');
        $iseOzgu = collect($guncel)->firstWhere('kategori', 'ise_ozgu');

        // Mart (indeks 2): online genel konu tamamlandı olmalı; yüz yüze 4. bölüm yalnız LMS ile tamamlanmamalı
        $this->assertSame('tamamlandi', $genel['aylar'][2]);
        $this->assertNotSame('tamamlandi', $iseOzgu['aylar'][2]);
    }
}
