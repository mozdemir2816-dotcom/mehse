<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\IsverenWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** 02.11.2026: ADEP — WhatsApp ile işverene gönderim ve yeni planda NACE + risk analizinden otomatik senaryo. */
class AcilDurumWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_adep_whatsapp_mesaji_ve_imzali_pdf_baglantisi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ABC İnşaat Ltd.', 'telefon' => '0532 111 22 33', 'tehlike_sinifi' => 'cok_tehlikeli']);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill(['rapor_tarihi' => '2026-11-02', 'gecerlilik_tarihi' => '2028-11-02',
            'uygulama' => ['koordinator_ad' => 'Ali Veli (Şantiye Şefi)', 'ozel_senaryolar' => [['baslik' => 'Vinç devrilmesi']]]])->save();
        $plan->refresh();

        $mesaj = IsverenWhatsApp::adepMesaji($plan);

        $this->assertStringContainsString('(ABC İnşaat Ltd.),', $mesaj);
        $this->assertStringContainsString('02.11.2026 tarihli ve 6331 sayılı Kanun kapsamındaki Acil Durum Eylem Planınız ('.$plan->dokuman_no.') hazırlanmıştır.', $mesaj);
        $this->assertStringContainsString('- Acil Durum Konu Sayısı: '.(count($plan->konular) + 1), $mesaj);
        $this->assertStringContainsString('- Acil Durum Koordinatörü: Ali Veli (Şantiye Şefi)', $mesaj);
        $this->assertStringContainsString('- Gözden Geçirme Tarihi: 02.11.2028', $mesaj);
        $this->assertStringContainsString('Bilgilerinize sunarız.', $mesaj);

        preg_match('#https?://\S+/paylasim/adep/'.$plan->id.'\S+#', $mesaj, $e);
        $this->assertNotEmpty($e);
        auth()->logout();
        $this->get($e[0])->assertOk()->assertDownload('acil-durum-plani-abc-insaat-ltd.pdf');
        $this->get(str_replace('signature=', 'signature=x', $e[0]))->assertForbidden();
    }

    public function test_plan_ciktisi_yaninda_whatsapp_dugmesi(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['telefon' => '0532 111 22 33']);

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionVisible('whatsapp')
            ->callAction('whatsapp')
            ->assertNotified("Acil Durum Eylem Planı WhatsApp'ta hazırlandı");

        $telefonsuz = Firma::factory()->for($this->uzman)->create(['telefon' => null]);
        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $telefonsuz->id)
            ->callAction('whatsapp')
            ->assertNotified('Firma yetkili telefonu tanımlı değil');
    }

    public function test_yeni_planda_senaryolar_nace_ve_risk_analizinden_otomatik_secilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00.01', 'tehlike_sinifi' => 'cok_tehlikeli']);

        $sayfa = Livewire::test(AcilDurumSayfasi::class)->set('firmaId', $firma->id)
            ->assertNotified();
        $konular = $sayfa->get('konular');

        $this->assertContains('yuksekten_dusme', $konular);   // inşaat NACE'si
        $this->assertContains('heyelan', $konular);
        $this->assertContains('makine_arizasi', $konular);
        $this->assertContains('yangin', $konular);             // zorunlu
        $this->assertSame($konular, AcilDurumPlani::where('firma_id', $firma->id)->first()->konular);

        // Kayıtlı planda kullanıcının seçimi yeniden hesaplanmaz.
        AcilDurumPlani::where('firma_id', $firma->id)->first()->forceFill(['konular' => ['yangin', 'deprem']])->save();
        $tekrar = Livewire::test(AcilDurumSayfasi::class)->set('firmaId', $firma->id)->get('konular');
        $this->assertNotContains('yuksekten_dusme', $tekrar);
        $this->assertContains('deprem', $tekrar);
    }

    public function test_yeni_planda_risk_analizindeki_senaryo_de_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '62.01.01', 'tehlike_sinifi' => 'az_tehlikeli']);
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Yüksekte çalışma sırasında iskeleden düşme', 'durum' => 'acik']);

        $konular = Livewire::test(AcilDurumSayfasi::class)->set('firmaId', $firma->id)->get('konular');

        $this->assertContains('yuksekten_dusme', $konular);   // ofis NACE'si ama risk analizinde yüksekte çalışma var
    }
}
