<?php

namespace Tests\Feature;

use App\Filament\Pages\UzmanRaporMerkezi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\OlayKaydi;
use App\Models\RiskDegerlendirmesi;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\UzmanRaporu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UzmanRaporMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı', 'nace_kodu' => '41.00.01', 'tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function veriKur(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Mehmet Eğitimsiz', 'aktif' => true]);
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'elektrik', 'ekipman_adi' => 'Pano', 'muayene_periyodu_ay' => 12,
            'son_muayene_tarihi' => now()->subMonths(13), 'sonraki_vize_tarihi' => now()->subMonth()]);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['tehlike' => 'Yüksekte çalışma', 'olasilik' => 5, 'siddet' => 5, 'son_olasilik' => 5, 'son_siddet' => 5]);
        $rd->maddeler()->create(['tehlike' => 'İskele', 'olasilik' => 5, 'siddet' => 5, 'son_olasilik' => 1, 'son_siddet' => 1]);   // önlem sonrası puanlanmış
        $rd->maddeler()->create(['tehlike' => 'Toz', 'olasilik' => 1, 'siddet' => 1]);
        OlayKaydi::create(['firma_id' => $this->firma->id, 'olay_tipi' => 'ramak_kala', 'olay_tarihi' => now()]);
        SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [['calisan' => 'Gizli Hasta', 'tetkik' => 'Odyometri', 'sonraki_tarih' => now()->subDay()->toDateString()]]]);
    }

    public function test_rapor_gostergeler_dayanaklar_ve_aksiyonlar(): void
    {
        $this->veriKur();

        $r = UzmanRaporu::rapor($this->firma);

        $this->assertSame(1, $r['gostergeler']['aktif_calisan']);
        $this->assertSame(1, $r['gostergeler']['acik_risk']);
        $this->assertSame(1, $r['gostergeler']['acik_olay']);
        $this->assertSame('İşlem gerekli', $r['uygunluk']['durum']);

        $basliklar = $r['aksiyonlar']->pluck('baslik')->all();
        $this->assertContains('Periyodik kontrol — Pano', $basliklar);
        $this->assertContains('Eğitim kaydı eksik — Mehmet Eğitimsiz', $basliklar);
        $this->assertSame('gecikmis', $r['aksiyonlar']->first()['durum']);

        // Periyodik kontrol aksiyonu doğrulanmış resmî kaynağa bağlanır
        $periyodik = $r['aksiyonlar']->firstWhere('baslik', 'Periyodik kontrol — Pano');
        $this->assertTrue($periyodik['dayanak']['resmi']);
        $this->assertStringContainsString('MevzuatNo=18318', $periyodik['dayanak']['url']);

        // Sağlık / klinik veri rapora girmez
        $json = json_encode($r['aksiyonlar']->all(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Gizli Hasta', $json);
        $this->assertStringNotContainsString('Sağlık', $r['aksiyonlar']->pluck('kategori')->implode(','));
        $this->assertStringNotContainsString('Odyometri', UzmanRaporu::txt($this->firma));
    }

    public function test_firma_secilmeden_veri_yuklenmez_txt_ve_izolasyon(): void
    {
        $this->veriKur();
        $yabanci = Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı Firma']);

        $sayfa = Livewire::test(UzmanRaporMerkezi::class)
            ->assertSee('Rapor verisi için yukarıdan bir firma')
            ->assertDontSee('Öncelikli aksiyonlar');
        $this->assertNull($sayfa->instance()->rapor);

        $sayfa->set('firmaId', $this->firma->id)
            ->assertSee('İşyeri uygunluk özeti')
            ->assertSee('Öncelikli aksiyonlar')
            ->assertSee('Resmî kaynağı aç')
            ->call('txtIndir')->assertFileDownloaded('uzman-raporu-ahmet-yapi.txt');

        $sayfa->set('firmaId', $yabanci->id);
        $this->assertNull($sayfa->instance()->rapor);
        $sayfa->assertDontSee('Yabancı Firma');
    }

    public function test_dayanak_yoksa_mevzuat_sayfasina_yonlenir(): void
    {
        $d = UzmanRaporu::dayanak('SDS');

        $this->assertFalse($d['resmi']);
        $this->assertStringContainsString('mevzuat', $d['url']);
    }
}
