<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Profilim yeniden tasarımı (29.10.2026): hero + kaşe önizleme, tek satır KPI, ilk 5 firma, gizli Arşiv sekmesi. */
class ProfilimYenidenTasarimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function kritikRiskliFirma(string $unvan, int $calisan, int $kritikMadde): Firma
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => $unvan, 'calisan_sayisi' => $calisan]);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);

        for ($i = 1; $i <= $kritikMadde; $i++) {
            $rd->maddeler()->create(['sira' => $i, 'tehlike' => "Madde {$i}", 'olasilik' => 10, 'frekans' => 6, 'siddet' => 7]); // 420 > 140
        }
        $rd->maddeler()->create(['sira' => 99, 'tehlike' => 'Hafif', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]);

        return $firma;
    }

    public function test_oncelikli_firmalar_calisan_x_kritik_risk_sirasiyla_ilk_bes(): void
    {
        $this->kritikRiskliFirma('Büyük ama az riskli', 100, 1);   // 100
        $this->kritikRiskliFirma('Orta ve riskli', 40, 5);          // 200
        $this->kritikRiskliFirma('Küçük çok riskli', 5, 10);        // 50
        $this->kritikRiskliFirma('Riski yok', 500, 0);              // 0
        $yabanci = Firma::factory()->for(User::factory())->create(['calisan_sayisi' => 900]);
        RiskDegerlendirmesi::create(['firma_id' => $yabanci->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()])
            ->maddeler()->create(['sira' => 1, 'tehlike' => 'X', 'olasilik' => 10, 'frekans' => 6, 'siddet' => 7]);

        $liste = PortfoyKarne::oncelikliFirmalar($this->uzman->id, 3);

        $this->assertSame(['Orta ve riskli', 'Büyük ama az riskli', 'Küçük çok riskli'], array_column($liste, 'unvan'));
        $this->assertSame([5, 1, 10], array_column($liste, 'kritik_risk'));
        $this->assertSame([40, 100, 5], array_column($liste, 'calisan'));
    }

    public function test_hero_kpi_seridi_ve_alt_tablo_gorunur_arsiv_sekmesi_gizli(): void
    {
        $this->kritikRiskliFirma('Alfa İnşaat', 12, 2);

        Livewire::test(Profilim::class)
            ->assertSee('Kaşe / İmza Yükle')
            ->assertSee('Ünvan & İletişim Düzenle')
            ->assertSee('Excel Portföy Raporu İndir')
            ->assertSee('Aktif Firma')
            ->assertSee('Sektör Şablonu')
            ->assertSee('Kritik Risk Maddesi')
            ->assertSee('Yasal Uyum & Risk Dağılımı')
            ->assertSee('Saha & Denetim Aktivite Yoğunluğu')
            ->assertSee('En Yüksek Çalışanlı ve Riskli İlk 5 Firma')
            ->assertSee('Alfa İnşaat')
            ->assertDontSee('Raporlarım')
            ->assertDontSee("sekmeSec('arsiv')", false)
            ->assertSee("sekmeSec('pazarlama')", false);

        // Eski adres ve test çağrısı yine çalışır (sekme gizli, silinmedi).
        Livewire::test(Profilim::class)->call('sekmeSec', 'arsiv')->assertSet('sekme', 'arsiv');
    }

    public function test_kase_durumu_yuklu_dosya_yok_ve_eksik(): void
    {
        Storage::fake('public');

        Livewire::test(Profilim::class)->assertSee('Kaşe eksik');

        $this->uzman->forceFill(['kase_gorseli' => 'uzman-kase/yok.png'])->save();
        Livewire::test(Profilim::class)->assertSee('Kaşe dosyası bulunamadı');

        Storage::disk('public')->put('uzman-kase/var.png', 'png-icerik');
        $this->uzman->forceFill(['kase_gorseli' => 'uzman-kase/var.png'])->save();
        Livewire::test(Profilim::class)->assertSee('Kaşe Yüklü')->assertSee('/mehse/ayar/uzman-gorsel/kase', false);
    }

    public function test_kase_gorseli_rotasi_dosyayi_dogrudan_sunar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uzman-kase/var.png', 'png-icerik');

        $this->uzman->forceFill(['kase_gorseli' => 'uzman-kase/var.png', 'imza_gorseli' => 'uzman-imza/yok.png'])->save();

        $this->get('/mehse/ayar/uzman-gorsel/kase')->assertOk();
        $this->get('/mehse/ayar/uzman-gorsel/imza')->assertNotFound();      // kayıtta var, dosya yok
        $this->get('/mehse/ayar/uzman-gorsel/baska')->assertNotFound();     // geçersiz tür

        // Başka kullanıcı yalnız kendi görselini görür (yolu yok → 404).
        $this->actingAs(User::factory()->create());
        $this->get('/mehse/ayar/uzman-gorsel/kase')->assertNotFound();

        auth()->logout();
        $this->get('/mehse/ayar/uzman-gorsel/kase')->assertRedirect();
    }
}
