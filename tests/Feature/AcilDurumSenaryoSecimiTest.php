<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\User;
use App\Support\AcilDurumBelgeVerisi;
use App\Support\AcilDurumKonuSecici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 01.11.2026: Acil durum planı firmaya özel senaryolar — zorunlu senaryolar, risk analizinden otomatik
 * belirleme, risk tablosu / EK-1 / EK-3 KKD / EK-6 yalnız seçili ve doğrulanmış verilerle.
 */
class AcilDurumSenaryoSecimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function firmaRiskli(array $tehlikeler): Firma
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli', 'nace_kodu' => '99.99']);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        foreach ($tehlikeler as $t) {
            RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => $t, 'olasilik' => 3, 'frekans' => 6, 'siddet' => 15]);
        }

        return $firma;
    }

    public function test_zorunlu_senaryolar_her_planda_bulunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->update(['konular' => ['deprem']]);

        $this->assertSame(['yangin', 'deprem', 'is_kazasi', 'elektrik'], $plan->fresh()->konular);
    }

    public function test_risk_analizinden_senaryo_ve_kkd_belirlenir(): void
    {
        $firma = $this->firmaRiskli([
            'Eksantrik pres kalıp bölgesinde el sıkışması',
            'Boya kabininde tiner buharı',
            'Tavan vinci ile yük kaldırma',
            'Kompresör hava tankı basıncı', // "pres" kompresörde eşleşmemeli
        ]);

        $oneri = AcilDurumKonuSecici::riskAnalizindenOner($firma);
        foreach (['makine_arizasi', 'kimyasal', 'patlama', 'yuksekten_dusme', 'basincli_kap'] as $k) {
            $this->assertContains($k, $oneri, $k);
        }

        $sadeceKompresor = $this->firmaRiskli(['Kompresör hava tankı basıncı']);
        $this->assertSame(['basincli_kap'], AcilDurumKonuSecici::riskAnalizindenOner($sadeceKompresor));

        $kkd = AcilDurumKonuSecici::kkdFaaliyetleri($firma);
        $this->assertContains('KİMYASALLA ÇALIŞMA', $kkd);
        $this->assertContains('TEMİZLİK', $kkd);
        $this->assertNotContains('ARITMA BÖLGESİ', $kkd);
        $this->assertNotContains('TANK/SİLO GİRİŞ', $kkd); // hava tankı ≠ tank içi çalışma

        $cnc = $this->firmaRiskli(['CNC tezgahında bor yağı sıçraması', 'Kaynak dumanı solunması', 'Doğalgaz hattında kaçak']);
        $this->assertSame(['kimyasal', 'zehirlenme', 'patlama', 'makine_arizasi'], AcilDurumKonuSecici::riskAnalizindenOner($cnc));
        $this->assertNotContains('heyelan', AcilDurumKonuSecici::riskAnalizindenOner($cnc));

        $bos = Firma::factory()->for($this->uzman)->create();
        $this->assertSame([], AcilDurumKonuSecici::riskAnalizindenOner($bos));
        $this->assertNull(AcilDurumKonuSecici::kkdFaaliyetleri($bos));
    }

    public function test_sayfada_otomatik_belirle_ekler_ve_kaydedilir(): void
    {
        $firma = $this->firmaRiskli(['CNC torna tezgahında dönen ayna, bakım sırasında sıkışma']);

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('konuToggle', 'deprem')          // uzman deprem'i kapatır
            ->call('riskAnalizindenBelirle')
            ->assertNotified('1 senaryo eklendi')
            ->call('kkdFaaliyetToggle', 'GÜVENLİK')
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $this->assertContains('makine_arizasi', $plan->konular);
        $this->assertNotContains('deprem', $plan->konular); // otomatik belirleme kaldırılanı geri eklemez
        $this->assertNotContains('GÜVENLİK', $plan->uygulama['kkd_faaliyetler']);
        $this->assertContains('MEKANİK TAMİR', $plan->uygulama['kkd_faaliyetler']);
    }

    public function test_belge_yalniz_secili_senaryolari_ve_dogrulanmis_verileri_basar(): void
    {
        $firma = $this->firmaRiskli(['Kaynak dumanı']);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->update([
            'konular' => ['yangin', 'is_kazasi', 'elektrik', 'sel'], // deprem kapalı
            'uygulama' => ['cevresel_riskler' => [0 => ['durum' => 'var', 'not' => 'Doğu sınırında trafo'], 2 => ['durum' => 'yok', 'not' => '']]],
        ]);

        $d = AcilDurumBelgeVerisi::hazirla($plan->fresh());

        $this->assertSame(['Yangın', 'İş Kazası ve Sağlık Olayları', 'Elektrik Çarpması', 'Sel ve Su Baskını'], array_column($d['senaryolar'], 'ad'));
        $riskAdlari = array_column($d['riskler'], 'ad');
        $this->assertContains('Sel ve Ani Su Basması', $riskAdlari);
        $this->assertContains('Elektrik Çarpması', $riskAdlari);
        $this->assertNotContains('Deprem', $riskAdlari);
        $this->assertNotContains('Bomba İhbarı, Patlaması', $riskAdlari);

        $this->assertTrue($d['cevresel_dogrulandi']);
        $this->assertSame(['var', 'yok'], array_column($d['cevresel'], 'durum'));

        $this->assertContains('KAYNAK', $d['kkd']['faaliyetler']);
        $this->assertNotContains('ARITMA BÖLGESİ', $d['kkd']['faaliyetler']);
        $this->assertArrayHasKey('KAYNAK MASKESİ', $d['kkd']['kkd']);
        $this->assertArrayNotHasKey('CAN YELEĞİ/SİMİDİ', $d['kkd']['kkd']); // yalnız arıtma bölgesinde gerekir
        $this->assertSame(count($d['kkd']['faaliyetler']), strlen(array_values($d['kkd']['kkd'])[0]));
    }
}
