<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikDegerlendirmeRaporu;
use App\Filament\Pages\ZiyaretProgrami;
use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\CanliEgitimYonetici;
use App\Support\FirmaRiskBildirimQrUretici;
use App\Support\PortfoyKarne;
use App\Support\SahaZiyaretKanitYonetici;
use App\Support\SanalMufettis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsgSuiteArtilarTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip', 'name' => 'Mehmet Özdemir']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Test Sanayi A.Ş.',
            'aktif' => true,
            'tehlike_sinifi' => 'cok_tehlikeli',
            'calisan_sayisi' => 25,
        ]);
    }

    public function test_e1_sanal_mufettis_6331_ipc_riski_ve_pdf_uretir(): void
    {
        $rapor = SanalMufettis::denetle($this->firma);

        $this->assertSame(2.0, $rapor['carpan']);
        $this->assertGreaterThan(0, $rapor['min_ceza_tl']);
        $this->assertGreaterThan($rapor['min_ceza_tl'], $rapor['max_ceza_tl']);
        $this->assertGreaterThan(0, $rapor['eksik_sayisi']);
        $this->assertNotNull(SanalMufettis::pdf($this->firma));
    }

    public function test_e2_ziyaret_dortlu_kanit_qr_gps_ve_eksik_dakika_tablosu(): void
    {
        $qrKod = FirmaRiskBildirimQrUretici::imzaKodu($this->firma);
        $kanit = SahaZiyaretKanitYonetici::girisYap($this->uzman, $this->firma, 'Periyodik Denetim', 40.21, 28.94, $qrKod);
        $this->assertTrue($kanit->qr_dogrulandi);
        $this->assertTrue($kanit->gpsVarMi());

        $tamam = SahaZiyaretKanitYonetici::cikisYap($kanit, 40.21, 28.94, 120, true);
        $this->assertSame(120, $tamam->sure_dk);
        $this->assertSame(3, $tamam->kanitPuani()); // QR + GPS + İmza

        $tablo = SahaZiyaretKanitYonetici::eksikSureTablosu($this->uzman);
        $this->assertCount(1, $tablo);
        $this->assertSame(120, $tablo[0]['gerceklesen_dk']);

        Livewire::test(ZiyaretProgrami::class)
            ->call('gorunumSec', 'kanit')
            ->assertSet('gorunum', 'kanit')
            ->assertSee('Eksik Saha Süresi');
    }

    public function test_e4_ilkyardim_atama_ve_gecerli_belge_ayri_sayilir(): void
    {
        $ekip = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'ilk_yardim', 'ad' => 'İlk Yardım Ekibi']);
        AcilEkipUyesi::create([
            'acil_ekip_id' => $ekip->id,
            'ad_soyad' => 'Süresi Dolmuş İlkyardımcı',
            'uyelik' => 'asil',
            'belge_tarihi' => now()->subYears(5)->toDateString(),
            'belge_bitis' => now()->subYear()->toDateString(),
        ]);

        $kontenjan = PortfoyKarne::ekipKontenjanTablosu($this->uzman->id, $this->firma->id);
        $this->assertSame(1, $kontenjan[0]['ilkyardim_atanan']);
        $this->assertSame(0, $kontenjan[0]['ilkyardim_gecerli_belge']);
    }

    public function test_e5_ise_baslama_kuyrugu_ve_canli_egitim_sinav_qr_sertifika(): void
    {
        Calisan::create([
            'firma_id' => $this->firma->id,
            'ad_soyad' => 'Yeni Başlayan İşçi',
            'aktif' => true,
            'ise_giris' => now()->subDays(2)->toDateString(),
        ]);

        Livewire::test(EgitimKatilim::class, ['firmaId' => $this->firma->id])
            ->assertSee('Bugün Ne Yapmalıyım?')
            ->assertSee('Yeni Başlayan İşçi');

        $oturum = CanliEgitimYonetici::oturumOlustur($this->uzman, $this->firma, 'yuksekte_8s', 'Yüksekte Çalışma Canlı', now()->toDateTimeString());

        auth()->logout();
        $this->get(route('canli-egitim.form', ['token' => $oturum->katilim_token]))->assertOk()->assertSee('Yüksekte Çalışma');

        $this->post(route('canli-egitim.gonder', ['token' => $oturum->katilim_token]), [
            'ad_soyad' => 'Ahmet Demir',
            'tc' => '11122233344',
            'cevaplar' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0],
        ])->assertRedirect();

        $oturum->refresh();
        $this->assertCount(1, $oturum->sonuclar);
        $this->assertSame(100, $oturum->sonuclar[0]['puan']);
        $this->assertTrue($oturum->sonuclar[0]['gecti']);

        $this->get(route('canli-egitim.sertifika', ['token' => $oturum->katilim_token, 'kod' => $oturum->sonuclar[0]['kod']]))->assertOk();
    }

    public function test_e7_yillik_plan_otomatik_uret_ve_degerlendirmeye_senkronize_et(): void
    {
        Livewire::test(YillikCalismaPlani::class, ['firmaId' => $this->firma->id])
            ->callAction('otomatikPlanUret')
            ->assertNotified();

        Livewire::test(YillikDegerlendirmeRaporu::class, ['firmaId' => $this->firma->id])
            ->callAction('plandanDegerlendirmeSenkron')
            ->assertNotified();
    }

    public function test_e9_canli_konum_paylasimi_kaydedilir(): void
    {
        $this->postJson(route('mehse.konum.bildir'), [
            'aktif' => true,
            'enlem' => 40.214,
            'boylam' => 28.940,
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertTrue((bool) ($this->uzman->fresh()->ayarlar['canli_konum']['aktif'] ?? false));
    }
}
