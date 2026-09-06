<?php

namespace Tests\Feature;

use App\Filament\Pages\KontrolMerkezi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KontrolMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_portfoy_ozeti_risk_kapsamini_hesaplar(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 10]);
        $b = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 20]);
        Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 5]);
        Firma::factory()->create(); // başka uzman

        RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $ozet = PortfoyKarne::ozet($this->uzman->id);

        $this->assertSame(3, $ozet['firma']);
        $this->assertSame(1, $ozet['risk_olan']);
        $this->assertSame(2, $ozet['evrak_eksigi']);
        // 16 hazır kriter var (risk değ. + gerçek modülü bağlanan 15'i); firma A yalnız
        // risk değerlendirmesini karşılıyor, "tam uyumlu" sayılmaz.
        $this->assertSame(0, $ozet['tam_uyumlu']);
    }

    public function test_kriterler_hazir_olmayanlar_sifir_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $kriterler = collect(PortfoyKarne::kriterler($this->uzman->id))->keyBy('anahtar');

        $this->assertSame(1, $kriterler['risk_degerlendirmesi']['tamam']);
        $this->assertSame(100, $kriterler['risk_degerlendirmesi']['yuzde']);
        // periyodik_kontrol_raporu henüz gerçek modüle bağlanmadı (hazir=false) — hep 0 döner.
        $this->assertFalse($kriterler['periyodik_kontrol_raporu']['hazir']);
        $this->assertSame(0, $kriterler['periyodik_kontrol_raporu']['tamam']);
    }

    public function test_acil_durum_plani_kriteri_artik_gercek_modulu_bagli(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        \App\Models\AcilDurumPlani::create(['firma_id' => $firma->id, 'konular' => ['yangin']]);

        $kriterler = collect(PortfoyKarne::kriterler($this->uzman->id))->keyBy('anahtar');

        $this->assertTrue($kriterler['acil_durum_plani']['hazir']);
        $this->assertSame(1, $kriterler['acil_durum_plani']['tamam']);
    }

    public function test_calisan_temsilcisi_kriteri_atama_veya_secim_sonucuna_bagli(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kriterler = collect(PortfoyKarne::kriterler($this->uzman->id))->keyBy('anahtar');
        $this->assertTrue($kriterler['calisan_temsilcisi']['hazir']);
        $this->assertSame(0, $kriterler['calisan_temsilcisi']['tamam']);

        \App\Models\AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'calisan_temsilcisi',
            'tarih' => now(),
            'uyeler' => [['ad_soyad' => 'Test Çalışan', 'tc' => null, 'gorev' => null, 'bas_uye' => false]],
        ]);

        $kriterler = collect(PortfoyKarne::kriterler($this->uzman->id))->keyBy('anahtar');
        $this->assertSame(1, $kriterler['calisan_temsilcisi']['tamam']);
    }

    public function test_isg_kurulu_kriteri_yalniz_50_ustu_firmalari_kapsar(): void
    {
        Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 60]);
        Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 10]);

        $kurul = collect(PortfoyKarne::kriterler($this->uzman->id))->firstWhere('anahtar', 'isg_kurulu');

        $this->assertSame(1, $kurul['toplam']); // yalnız 60 çalışanlı firma
    }

    public function test_sayfa_uc_sekmeli_acilir_ve_sekme_degisir(): void
    {
        Firma::factory()->for($this->uzman)->create();

        Livewire::test(KontrolMerkezi::class)
            ->assertOk()
            ->assertSet('sekme', 'firma')
            ->call('sekmeSec', 'gunluk')->assertSet('sekme', 'gunluk')
            ->call('sekmeSec', 'calisan')->assertSet('sekme', 'calisan')
            ->call('sekmeSec', 'gecersiz')->assertSet('sekme', 'calisan');
    }

    public function test_gunluk_akis_geciken_risk_degerlendirmesini_listeler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create([
            'firma_id' => $firma->id, 'yontem' => 'matris_5x5',
            'rapor_tarihi' => now()->subYears(5), 'gecerlilik_tarihi' => now()->subMonth(),
        ]);

        $isler = Livewire::test(KontrolMerkezi::class)->instance()->yaklasanIsler();

        $this->assertCount(1, $isler);
        $this->assertSame('gecikti', $isler[0]['durum']);
    }

    public function test_calisan_karne_genc_calisani_isaretler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Genç İşçi', 'dogum_tarihi' => now()->subYears(17), 'aktif' => true]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Yetişkin', 'dogum_tarihi' => now()->subYears(30), 'aktif' => true]);

        $karne = PortfoyKarne::calisanKarne($firma);

        $this->assertSame(2, $karne['toplam']);
        $this->assertCount(1, $karne['genc']);
        $this->assertSame('Genç İşçi', $karne['genc'][0]->ad_soyad);
    }
}
