<?php

namespace Tests\Feature;

use App\Filament\Pages\JsaDegerlendirmesi;
use App\Filament\Pages\KimyasalRiskDegerlendirmesi;
use App\Filament\Pages\PkdSicili;
use App\Models\Firma;
use App\Models\JsaSablonu;
use App\Models\KimyasalUrun;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Grup2IcerikVeMantikTest extends TestCase
{
    use RefreshDatabase;

    public function test_kimyasal_sicilinden_tum_eksikler_tek_tikla_aktarilir_ve_pkd_parlayici_onerir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Boya A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        KimyasalUrun::create(['firma_id' => $f->id, 'urun_adi' => 'Selülozik Tiner', 'fiziksel_hal' => 'sivi', 'ghs' => ['ghs02', 'ghs08'], 'aktif' => true]);
        KimyasalUrun::create(['firma_id' => $f->id, 'urun_adi' => 'Su Bazlı Temizleyici', 'fiziksel_hal' => 'sivi', 'ghs' => ['ghs07'], 'aktif' => true]);

        $sayfa = Livewire::test(KimyasalRiskDegerlendirmesi::class)
            ->set('firmaId', $f->id)
            ->call('tumEnvanteriAktar');

        $this->assertCount(2, $sayfa->get('satirlar'));

        // İkinci kez çağrıldığında mükerrer satır eklenmez
        $sayfa->call('tumEnvanteriAktar');
        $this->assertCount(2, $sayfa->get('satirlar'));

        // PKD yalnız parlayıcı/patlayıcı (GHS02) olan Tiner'i önerir
        $pkd = Livewire::test(PkdSicili::class)->set('firmaId', $f->id);
        $metin = $pkd->instance()->parlayiciKimyasalMetni();
        $this->assertStringContainsString('Selülozik Tiner', (string) $metin);
        $this->assertStringNotContainsString('Su Bazlı Temizleyici', (string) $metin);
    }

    public function test_jsa_secilen_firmanin_yuksek_riskli_islerine_gore_sablonu_one_cikarir(): void
    {
        $u = User::factory()->create(['rol' => 'sahip', 'aktif' => true]);
        $this->actingAs($u);
        $f = Firma::create(['user_id' => $u->id, 'unvan' => 'Çelik A.Ş.', 'tehlike_sinifi' => 'cok_tehlikeli', 'aktif' => true]);

        $rd = RiskDegerlendirmesi::create(['firma_id' => $f->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create([
            'sira' => 1,
            'faaliyet' => 'Çatı montajı ve yüksekte çalışma',
            'tehlike' => 'Yüksekte çalışma sırasında düşme',
            'risk' => 'Ölüm / ağır yaralanma',
            'olasilik' => 6,
            'frekans' => 6,
            'siddet' => 15,
        ]);

        $ilgisiz = JsaSablonu::create(['user_id' => $u->id, 'baslik' => 'Ofis Ergonomi Analizi', 'adimlar' => []]);
        $ilgili = JsaSablonu::create(['user_id' => $u->id, 'baslik' => 'Yüksekte Çalışma & İskele JSA', 'adimlar' => []]);

        $jsa = Livewire::test(JsaDegerlendirmesi::class)->set('firmaId', $f->id);
        $onerilen = $jsa->get('onerilenSablonIdleri');

        $this->assertContains($ilgili->id, $onerilen);
        $this->assertNotContains($ilgisiz->id, $onerilen);
        $this->assertSame($ilgili->id, $jsa->get('sablonlar')->first()->id);
    }
}
