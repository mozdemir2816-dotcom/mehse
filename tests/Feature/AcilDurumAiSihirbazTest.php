<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\AcilDurumBelgeVerisi;
use App\Support\GeminiAcilIletisimBulucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 02.11.2026: Acil Durum Planı — yapay zekâ ile özel senaryo (EK-1) ve saha bilgileri / adrese göre
 * kurum iletişim sihirbazı (EK-2, enerji kesme noktaları).
 */
class AcilDurumAiSihirbazTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-anahtar', 'services.gemini.yedek_modeller' => []]);
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['adres' => 'Minareliçavuş OSB, Nilüfer / Bursa']);
    }

    private function gemini(string $metin, int $durum = 200): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $metin]]]]]];
    }

    public function test_yapay_zeka_ozel_senaryo_onizlenir_onaylanir_ve_ek1_sonuna_numaralanir(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->gemini(json_encode([
            'baslik' => 'Lityum İyon Batarya Termal Kaçağı',
            'tanim' => 'Şarj istasyonunda bataryanın aşırı ısınarak yanması.',
            'mudahale_adimlari' => ['Alarm verilir', '112 aranır', 'Alan tahliye edilir'],
            'olay_sonrasi' => ['Sayım yapılır'],
            'onleyici_tedbirler' => ['Şarj alanı yanıcılardan ayrılır'],
            'kisisel_davranis_ve_kkd' => ['Dumanı solumadan uzaklaşın'],
        ], JSON_UNESCAPED_UNICODE)))]);

        $sayfa = Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('ozelBaslik', 'batarya yangını')
            ->call('ozelSenaryoUret')
            ->assertSet('ozelOnizleme.ad', 'Lityum İyon Batarya Termal Kaçağı')
            ->assertSet('ozelOnizleme.mudahale', "Alarm verilir\n112 aranır\nAlan tahliye edilir")
            ->set('ozelOnizleme.mudahale', "1. Alarm verilir\n- 112 aranır\n\nŞarj kesilir") // uzman düzeltir
            ->call('ozelSenaryoOnayla')
            ->assertSet('ozelOnizleme', null);

        $plan = AcilDurumPlani::where('firma_id', $this->firma->id)->firstOrFail();
        $ozel = $plan->uygulama['ozel_senaryolar'][0];
        $this->assertSame(['Alarm verilir', '112 aranır', 'Şarj kesilir'], $ozel['mudahale']);
        $this->assertStringStartsWith('ozel_', $ozel['anahtar']);

        // Kaydet özel senaryoyu silmez; kütüphaneye de kaydedildi.
        $sayfa->call('kaydet');
        $this->assertCount(1, $plan->fresh()->uygulama['ozel_senaryolar']);
        $this->assertSame('Lityum İyon Batarya Termal Kaçağı', $this->uzman->fresh()->ayarlar['acil_durum_ozel_senaryolari'][0]['ad']);

        $d = AcilDurumBelgeVerisi::hazirla($plan->fresh());
        $son = end($d['senaryolar']);
        $this->assertSame('Lityum İyon Batarya Termal Kaçağı', $son['ad']);
        $this->assertSame(count($plan->konular) + 1, count($d['senaryolar']));
        $this->assertSame(['ÖNLEYİCİ TEDBİRLER', 'KİŞİSEL DAVRANIŞ VE KKD'], array_column($son['ek'], 'baslik'));

        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh(), 'firma' => $this->firma, 'd' => $d])->render();
        $this->assertStringContainsString(count($d['senaryolar']).'. ACİL DURUM: LİTYUM İYON BATARYA TERMAL KAÇAĞI', $html);

        // Başka firmaya kütüphaneden tek tıkla eklenir.
        $diger = Firma::factory()->for($this->uzman)->create();
        Livewire::test(AcilDurumSayfasi::class)->set('firmaId', $diger->id)->call('kutuphanedenEkle', 0);
        $this->assertSame('Lityum İyon Batarya Termal Kaçağı', AcilDurumPlani::where('firma_id', $diger->id)->first()->uygulama['ozel_senaryolar'][0]['ad']);
    }

    public function test_adrese_gore_kurumlar_dogrulaninca_ek2ye_yazilir(): void
    {
        $json = json_encode(['kurumlar' => [
            ['kurum' => 'hastane', 'ad' => 'Bursa Şehir Hastanesi', 'telefon' => '0224 975 00 00', 'not' => 'Nilüfer'],
            ['kurum' => 'itfaiye', 'ad' => 'Bursa İtfaiyesi Nilüfer Grubu', 'telefon' => '0224 000 00 00 (santral)', 'not' => ''],
            ['kurum' => 'dogalgaz', 'ad' => 'Bursagaz', 'telefon' => '', 'not' => 'Arıza 187'],
            ['kurum' => 'bilinmeyen', 'ad' => 'Atlanmalı', 'telefon' => '1'],
        ]], JSON_UNESCAPED_UNICODE);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->gemini("Sonuç:\n```json\n{$json}\n```"))]);

        $sayfa = Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('iletisimBul')
            ->assertSet('iletisimAramali', true)
            ->assertCount('iletisimOnerileri', 3)
            ->assertSet('iletisimOnerileri.1.telefon', '0224 000 00 00 ()') // harfler ayıklanır
            ->assertSet('iletisimOnerileri.2.sec', false);                  // telefonsuz satır işaretsiz gelir

        Http::assertSent(fn ($r) => isset($r['tools'][0]['google_search']));

        $sayfa->set('iletisimOnerileri.1.telefon', '0224 444 11 22')->call('iletisimOnerileriniEkle');

        $u = AcilDurumPlani::where('firma_id', $this->firma->id)->first()->uygulama;
        $this->assertSame('Bursa Şehir Hastanesi', $u['hastane_ad']);
        $this->assertContains('Bursa İtfaiyesi Nilüfer Grubu', array_column($u['iletisim'], 'ad'));
        $this->assertNotContains('Bursagaz', array_column($u['iletisim'], 'ad'));

        $d = AcilDurumBelgeVerisi::hazirla(AcilDurumPlani::where('firma_id', $this->firma->id)->first());
        $dis = collect($d['dis_iletisim']);
        $this->assertSame('0224 975 00 00', $dis->firstWhere('birim', 'EN YAKIN HASTANE')['tel']);
        $this->assertSame('0224 444 11 22', $dis->firstWhere('birim', 'BURSA İTFAİYESİ NİLÜFER GRUBU')['tel']);
    }

    public function test_arama_araci_desteklenmezse_yapilandirilmis_yanita_duser(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Search grounding is not supported']], 400)
            ->push($this->gemini(json_encode(['kurumlar' => [['kurum' => 'emniyet', 'ad' => 'Nilüfer İlçe Emniyet Müdürlüğü', 'telefon' => '']]], JSON_UNESCAPED_UNICODE)))]);

        $sonuc = GeminiAcilIletisimBulucu::bul($this->firma->adres);

        $this->assertFalse($sonuc['aramali']);
        $this->assertSame('Nilüfer İlçe Emniyet Müdürlüğü', $sonuc['satirlar'][0]['ad']);
        $this->assertNull($sonuc['hata']);
        $this->assertSame('Firmanın adresi girilmemiş. Önce Firmalar ekranında açık adresi (ilçe / il) doldurun.', GeminiAcilIletisimBulucu::bul('')['hata']);
    }

    public function test_sihirbaz_koordinatoru_calisanlardan_secer_ve_salter_vana_belgeye_girer(): void
    {
        $calisan = Calisan::factory()->create(['firma_id' => $this->firma->id, 'ad_soyad' => 'Ayşe Kaya', 'gorev' => 'Üretim Şefi', 'telefon' => '0532 111 22 33', 'aktif' => true, 'isten_cikis' => null]);

        $sayfa = Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('koordinatorSec', 'koordinator', $calisan->id)
            ->assertSet('uygulama.koordinator_ad', 'Ayşe Kaya (Üretim Şefi)')
            ->assertSet('uygulama.koordinator_tel', '0532 111 22 33')
            ->set('toplanmaYeri', 'Ana nizamiye önü otopark')
            ->set('uygulama.elektrik_salteri', 'Trafo binası ana giriş panosu')
            ->set('uygulama.gaz_vanasi', 'Saha girişindeki gaz istasyonu');

        $eksik = $sayfa->instance()->eksikSorular();
        foreach (['toplanma_yeri', 'koordinator_ad', 'koordinator_tel', 'elektrik_salteri', 'gaz_vanasi'] as $alan) {
            $this->assertArrayNotHasKey($alan, $eksik);
        }
        $this->assertArrayHasKey('yedek_toplanma', $eksik);

        $sayfa->call('kaydet');

        $d = AcilDurumBelgeVerisi::hazirla(AcilDurumPlani::where('firma_id', $this->firma->id)->first());
        $this->assertSame('Elektrik ana şalteri: Trafo binası ana giriş panosu. Doğalgaz / LPG ana kesme vanası: Saha girişindeki gaz istasyonu.', $d['enerji_kesme']);
        $this->assertSame('Ayşe Kaya (Üretim Şefi)', $d['koordinator']['ad']);
        $this->assertSame('Ana nizamiye önü otopark', $d['toplanma']);
    }
}
