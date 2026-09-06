<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilDurumKonuSecici;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumWordUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AcilDurumPlaniTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_icin_plan_varsayilan_konular_ve_belge_no(): void
    {
        $firma = Firma::factory()->for($this->uzman)
            ->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '19.20']); // kimyasal eşleşir

        $plan = AcilDurumPlani::firmaIcin($firma);

        $this->assertTrue($plan->exists);
        $this->assertStringStartsWith('AD-'.now()->year.'-', $plan->dokuman_no);
        $this->assertContains('yangin', $plan->konular); // koşulsuz
        $this->assertContains('deprem', $plan->konular); // koşulsuz
        $this->assertContains('kimyasal', $plan->konular); // nace 19 + çok tehlikeli eşleşir
        $this->assertNotContains('asansor', $plan->konular); // yalnız elle seçilir
        // çok tehlikeli → 2 yıl geçerlilik
        $this->assertSame(
            $plan->rapor_tarihi->copy()->addYears(2)->toDateString(),
            $plan->gecerlilik_tarihi->toDateString(),
        );
    }

    public function test_konu_secici_firmaya_uymayan_kosullu_konuyu_secmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)
            ->create(['tehlike_sinifi' => 'az_tehlikeli', 'nace_kodu' => '99.99']);

        $secili = AcilDurumKonuSecici::firmaIcin($firma);

        $this->assertContains('sel', $secili); // koşulsuz
        $this->assertNotContains('kimyasal', $secili); // ne nace ne tehlike eşleşir
        $this->assertNotContains('asansor', $secili); // hiç otomatik seçilmez
    }

    public function test_sayfa_firma_secilince_formu_doldurur_ve_kaydeder(): void
    {
        $firma = Firma::factory()->for($this->uzman)
            ->create(['tehlike_sinifi' => 'az_tehlikeli', 'nace_kodu' => '99.99']);

        Livewire::test(AcilDurumSayfasi::class)
            ->assertOk()
            ->set('firmaId', $firma->id)
            ->assertSet('kapakCercevesi', 'klasik')
            ->call('konuToggle', 'kimyasal')        // ekle (varsayılan seçili değil)
            ->call('konuToggle', 'yangin')          // çıkar (koşulsuz varsayılan)
            ->set('ekipMetni.sondurme', 'Ali Veli, Ayşe Fatma')
            ->set('kapakCercevesi', 'altin')
            ->set('revizyonNo', 'Rev.01 — 01.01.2027')
            ->set('toplanmaYeri', 'Ana kapı önü açık saha')
            ->set('disaridanEtkileyebilecekIsyerleri', 'Komşu Akaryakıt A.Ş. — Akaryakıt istasyonu — Patlama riski')
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $this->assertContains('kimyasal', $plan->konular);
        $this->assertNotContains('yangin', $plan->konular);
        $this->assertSame('altin', $plan->kapak_cercevesi);
        $this->assertSame(['Ali Veli', 'Ayşe Fatma'], $plan->ekipListesi()['sondurme']);
        $this->assertSame('Rev.01 — 01.01.2027', $plan->revizyon_no);
        $this->assertSame('Ana kapı önü açık saha', $plan->toplanma_yeri);
        $this->assertStringContainsString('Akaryakıt', $plan->disaridan_etkileyebilecek_isyerleri);
    }

    public function test_firmalar_listesinden_query_ile_firma_onceden_secili_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Query Test Firma A.Ş.']);

        $this->get(AcilDurumSayfasi::getUrl(['firma' => $firma->id]))
            ->assertOk()
            ->assertSee('Query Test Firma A.Ş.');
    }

    public function test_word_ciktisi_sablon_degerlerini_firmaya_gore_degistirir(): void
    {
        $uzman = $this->uzman;
        $uzman->forceFill(['name' => 'Ayşe Yılmaz', 'unvan' => 'b_sinifi'])->save();

        $firma = Firma::factory()->for($uzman)->create([
            'unvan' => 'Deneme Tekstil Sanayi Ltd.',
            'adres' => 'Test Mahallesi No:5 İzmir',
            'sgk_sicil_no' => '11122233344',
            'tehlike_sinifi' => 'tehlikeli',
            'calisan_sayisi' => 47,
        ]);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill(['konular' => ['yangin', 'deprem', 'elektrik']])->save();

        $yanit = AcilDurumWordUretici::docx($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'adep_test').'.docx';
        file_put_contents($gecici, $icerik);

        $zip = new \ZipArchive();
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($gecici);

        $this->assertStringContainsString('DENEME TEKSTİL SANAYİ LTD.', $xml);
        $this->assertStringContainsString('Test Mahallesi No:5 İzmir', $xml);
        $this->assertStringContainsString('11122233344', $xml);
        $this->assertStringContainsString('AYŞE YILMAZ', $xml);
        $this->assertStringNotContainsString('ALTIN YAKUT', $xml);
        $this->assertStringNotContainsString('MEHMET ÖZDEMİR', $xml);
        $this->assertStringNotContainsString('44100010111205450770133000', $xml);
    }

    public function test_tahliye_plani_gorseli_yuklenir(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $firma = Firma::factory()->for($this->uzman)->create();
        AcilDurumPlani::firmaIcin($firma);

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('tahliyePlani', data: [
                'tahliye_plani_gorseli' => \Illuminate\Http\UploadedFile::fake()->image('kroki.jpg'),
            ]);

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $this->assertNotNull($plan->tahliye_plani_gorseli);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($plan->tahliye_plani_gorseli);
    }

    public function test_tum_konular_secilir_ve_kaldirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumKonular', true);

        $this->assertCount(count(config('isg.acil_durum.konular')), $component->get('konular'));

        $component->call('tumKonular', false);
        $this->assertCount(0, $component->get('konular'));
    }

    public function test_plan_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->update(['ekipler' => ['sondurme' => ['Ali Veli']]]);

        $yanit = AcilDurumPlaniUretici::pdf($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_plan_pdf_yonetmelik_gereği_zorunlu_alanlari_icerir(): void
    {
        $uzman = $this->uzman;
        $uzman->forceFill(['name' => 'Mehmet Özdemir', 'unvan' => 'a_sinifi'])->save();

        $firma = Firma::factory()->for($uzman)->create(['nace_kodu' => '41.00']);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill([
            'revizyon_no' => 'Rev.01 — 01.01.2027',
            'toplanma_yeri' => 'Ana kapı önü açık saha',
            'disaridan_etkileyebilecek_isyerleri' => 'Komşu Akaryakıt A.Ş. — Akaryakıt istasyonu',
            'konular' => ['yangin', 'deprem'],
        ])->save();

        // dompdf metin katmanini ayiklamak yerine, uretimin hata vermeden
        // gectigini ve HTML asamasinda beklenen icerigin goruntuye girdigini
        // dogrulamak icin blade'i dogrudan render ediyoruz.
        $html = view('pdf.acil-durum-plani', [
            'plan' => $plan->fresh('firma'),
            'firma' => $firma->fresh(),
            'hakkinda' => config('isg.acil_durum.hakkinda'),
        ])->render();

        $this->assertStringContainsString('Mehmet Özdemir', $html);
        $this->assertStringContainsString('Rev.01', $html);
        $this->assertStringContainsString('Ana kapı önü açık saha', $html);
        $this->assertStringContainsString('Komşu Akaryakıt A.Ş.', $html);
        $this->assertStringContainsString('İRTİBAT KURULACAK KURULUŞLAR', $html);
        $this->assertStringContainsString('112', $html);
        $this->assertStringContainsString('NACE Kodu: 41.00', $html);

        $yanit = AcilDurumPlaniUretici::pdf($plan);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_afis_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = AcilDurumPlaniUretici::afis($firma, 'yangin', 'a4');

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_hazir_dosyasi_olan_afis_o_dosyayi_indirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = AcilDurumPlaniUretici::afis($firma, 'sabotaj', 'a4');

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('%PDF', $icerik);
        $this->assertSame(
            file_get_contents(resource_path('belge/acil-durum-afisleri/sabotaj.pdf')),
            $icerik,
        );
    }

    public function test_konfigurasyondaki_tum_hazir_afis_dosyalari_mevcut(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        foreach (config('isg.acil_durum.afisler') as $tip => $afis) {
            if (! isset($afis['dosya'])) {
                continue;
            }

            $yol = resource_path('belge/acil-durum-afisleri/'.$afis['dosya']);
            $this->assertFileExists($yol, "Afiş dosyası eksik: {$tip} -> {$afis['dosya']}");

            ob_start();
            AcilDurumPlaniUretici::afis($firma, $tip, 'a4')->sendContent();
            $icerik = ob_get_clean();
            $this->assertStringStartsWith('%PDF', $icerik, "Geçersiz PDF: {$tip}");
        }
    }

    public function test_gecersiz_afis_tipi_404(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        AcilDurumPlaniUretici::afis($firma, 'gecersiz', 'a4');
    }

    public function test_kroki_plani_kisayolu_dogru_firmaya_gider(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionVisible('krokiPlani')
            ->assertActionHasUrl('krokiPlani', \App\Filament\Pages\AcilDurumKrokisi::getUrl(['firma' => $firma->id]));
    }

    public function test_eksik_firmalar_kutusu_plani_olmayan_firmayi_gosterir(): void
    {
        $eksik = Firma::factory()->for($this->uzman)->create(['unvan' => 'Planı Olmayan A.Ş.']);
        $tamam = Firma::factory()->for($this->uzman)->create(['unvan' => 'Planı Tamam A.Ş.']);
        AcilDurumPlani::create(['firma_id' => $tamam->id, 'konular' => ['yangin']]);

        Livewire::test(AcilDurumSayfasi::class)
            ->assertSee('Eksik Olan Firmalar (1)')
            ->assertSee('Planı Olmayan A.Ş.');
    }

    public function test_eksik_firmalar_kutusundan_tiklaninca_o_firma_secilir(): void
    {
        $eksik = Firma::factory()->for($this->uzman)->create(['unvan' => 'Planı Olmayan A.Ş.']);

        Livewire::test(AcilDurumSayfasi::class)
            ->call('$set', 'firmaId', $eksik->id) // "Eksik Olan Firmalar" butonunun yaptığıyla aynı
            ->assertSet('firmaId', $eksik->id);
    }
}
