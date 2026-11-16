<?php

namespace Tests\Feature;

use App\Filament\Pages\OlayKomuta360;
use App\Models\Firma;
use App\Models\OlayKaydi;
use App\Models\User;
use App\Support\OlayKomuta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OlayKomuta360Test extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    private function olay(array $o): OlayKaydi
    {
        return OlayKaydi::create($o + ['firma_id' => $this->firma->id, 'olay_tipi' => 'is_kazasi', 'olay_tarihi' => '2026-03-10', 'durum' => 'acik']);
    }

    public function test_bos_durum_gosterilir(): void
    {
        Livewire::test(OlayKomuta360::class)->assertSee('Henüz olay kaydı yok');
    }

    public function test_ozet_ve_bolge_hesabi(): void
    {
        $this->olay(['sonuc_turu' => 'tibbi_mudahale', 'yaralanan_bolge' => 'el_bilek', 'bolum' => 'Üretim', 'durum' => 'kapandi']);
        $this->olay(['sonuc_turu' => 'is_gunu_kaybi', 'yaralanan_bolge' => 'el_bilek', 'kayip_gun_sayisi' => 4, 'bolum' => 'Üretim']);
        $this->olay(['sonuc_turu' => 'ilk_yardim', 'yaralanan_bolge' => 'diz', 'olay_tipi' => 'ilk_yardim']);
        $this->olay(['olay_tipi' => 'ramak_kala', 'sonuc_turu' => 'yaralanmasiz']);

        $olaylar = OlayKaydi::all();
        $ozet = OlayKomuta::ozet($olaylar);

        $this->assertSame(4, $ozet['toplam']);
        $this->assertSame(2, $ozet['yaralanma']);
        $this->assertSame(1, $ozet['kayipli']);
        $this->assertSame(4, $ozet['kayip_gun']);
        $this->assertSame(1, $ozet['ilk_yardim']);
        $this->assertSame(1, $ozet['ramak_kala']);
        $this->assertSame(25, $ozet['kapanis_orani']);

        $b = OlayKomuta::bolgeler($olaylar);
        $this->assertSame(3, $b['toplam']);
        $this->assertSame(2, $b['bolgeler']['el_bilek']['adet']);
        $this->assertSame(1, $b['bolgeler']['el_bilek']['kirilim']['tibbi']);
        $this->assertSame(1, $b['bolgeler']['el_bilek']['kirilim']['kayip']);

        $this->assertSame(1.0, OlayKomuta::oranlar($ozet, 200000)['trir'] * 1.0 / 2);
        $this->assertNull(OlayKomuta::oranlar($ozet, 0)['trir']);
    }

    public function test_sayfa_tum_sekmeleri_hatasiz_acilir(): void
    {
        $this->olay(['sonuc_turu' => 'tibbi_mudahale', 'yaralanan_bolge' => 'bas', 'bolum' => 'Bakım', 'kok_neden_kategorileri' => ['egitim_eksikligi']]);

        $sayfa = Livewire::test(OlayKomuta360::class)->assertSee('Toplam olay');
        foreach (array_keys(OlayKomuta360::SEKMELER) as $sekme) {
            $sayfa->call('sekmeAc', $sekme)->assertHasNoErrors();
        }
        $sayfa->call('sekmeAc', 'vucut')->assertSee('Baş / Yüz')
            ->call('bolgeSec', 'bas')->assertSet('bolge', 'bas')
            ->call('sekmeAc', 'rapor')->call('$set', 'raporModu', 'yillik')->assertSee('Yıllık karşılaştırma');
    }

    public function test_baska_kullanicinin_olayi_gorunmez(): void
    {
        $baskasi = Firma::factory()->for(User::factory()->create())->create();
        OlayKaydi::create(['firma_id' => $baskasi->id, 'olay_tipi' => 'is_kazasi', 'olay_tarihi' => '2026-03-10', 'olay_ozeti' => 'GİZLİ KAYIT']);

        Livewire::test(OlayKomuta360::class)->assertDontSee('GİZLİ KAYIT')->assertSee('Henüz olay kaydı yok');
    }

    public function test_excel_indirilir(): void
    {
        $this->olay(['sonuc_turu' => 'tibbi_mudahale']);

        Livewire::test(OlayKomuta360::class)->callAction('excel')->assertHasNoErrors();
    }
}
