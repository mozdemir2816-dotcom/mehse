<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumKrokisi;
use App\Filament\Pages\AcilDurumPlani as AcilDurumSayfasi;
use App\Models\AcilDurumPlani;
use App\Models\AcilEkip;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\AcilDurumBelgeVerisi;
use App\Support\AcilDurumBelgeWord;
use App\Support\AcilDurumHazirlik;
use App\Support\AcilDurumKapakUretici;
use App\Support\AcilDurumKonuSecici;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumSektor;
use App\Support\AcilDurumWordUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    public function test_bos_kutular_ornek_metinle_gelir_yazilmis_metin_korunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $ornek = config('isg.acil_durum.ornek_metinler');
        AcilDurumPlani::firmaIcin($firma)->forceFill(['uygulama' => ['olcum_notu' => 'Kendi notum']])->save();

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSet('uygulama.onleyici_tedbirler', $ornek['onleyici_tedbirler'])
            ->assertSet('uygulama.mudahale_yontemi', $ornek['mudahale_yontemi'])
            ->assertSet('uygulama.olcum_notu', 'Kendi notum')
            ->set('uygulama.onleyici_tedbirler', 'Düzeltilmiş tedbir')
            ->call('ornekMetin', 'olcum_notu')
            ->assertSet('uygulama.olcum_notu', $ornek['olcum_notu'])
            ->call('ornekMetin', 'iletisim')         // örneği olmayan alan dokunulmaz
            ->call('kaydet');

        $u = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail()->uygulama;
        $this->assertSame('Düzeltilmiş tedbir', $u['onleyici_tedbirler']);
        $this->assertSame($ornek['ekipman_kkd'], $u['ekipman_kkd']);
        $this->assertIsArray($u['iletisim']);
    }

    public function test_plan_belgesi_bilgileri_kaydedilir_ve_pdfe_basilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_ad' => 'Hasan Kaya', 'calisan_sayisi' => 8]);

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('uygulama.koordinator_ad', 'Selin Ak')
            ->set('uygulama.koordinator_tel', '0532 999 88 77')
            ->set('uygulama.hastane_ad', 'Şehir Hastanesi')
            ->set('uygulama.hastane_tel', '0224 444 00 00')
            ->set('uygulama.risk_puanlari.deprem.olasilik', '4')
            ->set('uygulama.risk_puanlari.deprem.insan', '5')
            ->set('uygulama.risk_puanlari.deprem.isyeri', '9')        // geçersiz puan atılır
            ->set('uygulama.cevresel_riskler.1.durum', 'var')
            ->set('uygulama.cevresel_riskler.1.not', 'Karşıda LPG istasyonu')
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $u = $plan->uygulama;
        $this->assertSame(['olasilik' => 4, 'insan' => 5], $u['risk_puanlari']['deprem']);
        $this->assertArrayNotHasKey('yangin', $u['risk_puanlari']);
        $this->assertSame(['durum' => 'var', 'not' => 'Karşıda LPG istasyonu'], $u['cevresel_riskler'][1]);
        $this->assertNotEmpty($u['alarm_yontemi']);                  // örnek metinle geldi ve kaydedildi

        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh('firma'), 'firma' => $firma->fresh()])->render();
        $this->assertStringContainsString('Selin Ak', $html);
        $this->assertStringContainsString('0532 999 88 77', $html);
        $this->assertStringContainsString('Şehir Hastanesi', $html);
        $this->assertStringContainsString('<b>9</b>', $html);           // deprem toplamı 4+5
        $this->assertStringContainsString('Karşıda LPG istasyonu', $html);
        $this->assertStringContainsString('10’dan az çalışanı olan bu işyerinde', $html);
        $this->assertStringContainsString('TEKNİK MÜDAHALE EKİBİ', $html);
    }

    public function test_ilk_doldurulan_risk_puanlari_diger_firmalara_varsayilan_gelir(): void
    {
        [$a, $b, $c] = Firma::factory()->for($this->uzman)->count(3)->create()->all();

        // İlk firma: puanlar girilir, kaydedilince kullanıcının varsayılanı olur.
        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $a->id)
            ->set('uygulama.risk_puanlari.deprem.olasilik', '5')
            ->set('uygulama.risk_puanlari.sel.olasilik', '2')
            ->set('uygulama.cevresel_riskler.0.durum', 'yok')
            ->call('kaydet');

        // İkinci firma: boş plan varsayılanla gelir; farklı konum düzeltilip kaydedilir.
        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $b->id)
            ->assertSet('puanlarSablondan', true)
            ->assertSet('uygulama.risk_puanlari.deprem.olasilik', 5)
            ->set('uygulama.risk_puanlari.sel.olasilik', '4')
            ->call('kaydet')
            ->assertSet('puanlarSablondan', false);

        $this->assertSame(4, AcilDurumPlani::where('firma_id', $b->id)->first()->uygulama['risk_puanlari']['sel']['olasilik']);
        // Düzeltme varsayılanı değiştirmez; hiç açılmamış firmanın belgesine de varsayılan basılır.
        $this->assertSame(2, AcilDurumHazirlik::kullaniciSablonu($this->uzman->fresh())['risk_puanlari']['sel']['olasilik']);
        $this->assertSame(5, AcilDurumBelgeVerisi::hazirla(AcilDurumPlani::firmaIcin($c))['riskler'][0]['puan']['olasilik']);

        // Açık butonla varsayılan güncellenir; kayıtlı plan (A) değişmez.
        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $b->id)
            ->call('puanlariVarsayilanYap');
        $this->assertSame(4, AcilDurumHazirlik::kullaniciSablonu($this->uzman->fresh())['risk_puanlari']['sel']['olasilik']);
        $this->assertSame(2, AcilDurumPlani::where('firma_id', $a->id)->first()->uygulama['risk_puanlari']['sel']['olasilik']);
    }

    public function test_sektor_naceden_onerilir_elle_degisir_ve_belgeye_basilir(): void
    {
        $metal = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '25.62']);
        $market = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '47.11']);

        $this->assertSame('metal', AcilDurumSektor::oner($metal));
        $this->assertSame('genel', AcilDurumSektor::oner($market));

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $metal->id)
            ->assertSet('uygulama.sektor', 'metal')
            ->set('konular', ['makine_arizasi'])
            ->call('kaydet');

        $plan = AcilDurumPlani::where('firma_id', $metal->id)->firstOrFail();
        $html = view('pdf.acil-durum-plani', ['plan' => $plan, 'firma' => $metal])->render();
        $this->assertStringContainsString('Metal işleme / makine imalatı önlemleri:', $html);
        $this->assertStringContainsString('Talaş ve yanıcı atıklar düzenli uzaklaştırılır', $html);
        $this->assertStringContainsString('CNC, torna, matkap, freze', $html);

        // NACE tutmuyorsa uzman genel'e çevirir; seçim kalıcıdır.
        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $metal->id)
            ->set('uygulama.sektor', 'genel')
            ->call('kaydet');
        Livewire::test(AcilDurumSayfasi::class)->set('firmaId', $metal->id)->assertSet('uygulama.sektor', 'genel');

        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh(), 'firma' => $metal])->render();
        $this->assertStringNotContainsString('makine imalatı önlemleri', $html);
        $this->assertStringNotContainsString('CNC, torna, matkap, freze', $html);
        $this->assertStringContainsString('pres, torna, CNC, freze', $html);       // genel senaryo metni
    }

    public function test_insaat_sektorunde_yuksekten_dusme_ayrintilari_eklenir(): void
    {
        $santiye = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '41.00', 'tehlike_sinifi' => 'cok_tehlikeli']);
        $this->assertSame('insaat', AcilDurumSektor::oner($santiye));

        $plan = AcilDurumPlani::firmaIcin($santiye);
        $plan->update(['konular' => ['yuksekten_dusme', 'deprem']]);

        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh(), 'firma' => $santiye])->render();
        $this->assertStringContainsString('İnşaat / şantiye önlemleri:', $html);
        $this->assertStringContainsString('ASKIDA KALAN ÇALIŞANA ERİŞİM, TAHLİYE VE İLKYARDIM', $html);
        $this->assertSame(1, substr_count($html, 'ASKIDA KALAN ÇALIŞANA ERİŞİM'));                 // genel + şantiye birleşince tekrar yok
        $this->assertStringContainsString('İSKELELERİN KURULMASI, KULLANIMI VE KONTROLÜ', $html);
        $this->assertStringContainsString('Askıda kalan personele 10 dakika içinde müdahale edilir.', $html);
        $this->assertStringContainsString('Düşen kişiye gereksiz dokunulmaz', $html);              // genel senaryo korunur
        $this->assertSame(1, substr_count($html, 'SINIRLAYICI TEDBİRLER'));                     // yalnız yüksekten düşmede

        // Genel seçilince şantiye blokları çıkmaz; her işyerine uyan yüksekte çalışma ekleri kalır.
        $plan->update(['uygulama' => ['sektor' => 'genel']]);
        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh(), 'firma' => $santiye])->render();
        $this->assertStringNotContainsString('İSKELELERİN KURULMASI', $html);
        $this->assertStringNotContainsString('YÜKSEKTE ÇALIŞMA İÇİN ÖNLEYİCİ TEDBİRLER', $html);
        $this->assertSame(1, substr_count($html, 'ASKIDA KALAN ÇALIŞANA ERİŞİM'));
        $this->assertStringContainsString('MERDİVEN KURULUMU VE KULLANIMI', $html);
        $this->assertStringContainsString('Düşen kişiye gereksiz dokunulmaz', $html);
    }

    public function test_genel_senaryo_ekleri_her_sektorde_basilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['nace_kodu' => '47.11']);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->update(['konular' => ['yangin', 'deprem', 'is_kazasi', 'elektrik', 'kimyasal']]);

        $html = view('pdf.acil-durum-plani', ['plan' => $plan->fresh(), 'firma' => $firma])->render();
        $this->assertStringContainsString('YANGIN SINIFLARI VE UYGUN SÖNDÜRÜCÜ', $html);
        $this->assertStringContainsString('YANGINDA KİŞİSEL DAVRANIŞ', $html);
        $this->assertStringNotContainsStringIgnoringCase('halon', $html);
        $this->assertStringContainsString('DEPREM SIRASINDA BULUNULAN YERE GÖRE DAVRANIŞ', $html);
        $this->assertStringNotContainsString('Sivil Savunma', $html);
        $this->assertStringContainsString('ÖLÜMLÜ VEYA AĞIR YARALANMALI KAZADA', $html);
        $this->assertStringNotContainsString('155', $html);
        $this->assertStringContainsString('ORTAMIN GÜVENLİ HALE GETİRİLMESİ', $html);
        $this->assertStringNotContainsString('TEDAŞ', $html);
        $this->assertStringNotContainsString('giysilerinden çekerek', $html);
        $this->assertStringContainsString('KİMYASAL YAYILIMA KARŞI ÖNLEYİCİ VE SINIRLANDIRICI TEDBİRLER', $html);
        $this->assertStringContainsString('300 mA', $html);
        $this->assertStringContainsString('rüzgâr üstüne', $html);
        $this->assertStringNotContainsString('ASKIDA KALAN', $html);          // seçili olmayan senaryonun eki basılmaz
    }

    public function test_tum_konularin_senaryo_metni_var(): void
    {
        foreach (config('isg.acil_durum.konular') as $k) {
            $s = config('acil_durum_senaryolari.'.$k['anahtar']);
            $this->assertNotEmpty($s['aciklama'] ?? null, $k['anahtar']);
            $this->assertNotEmpty($s['mudahale'] ?? null, $k['anahtar']);
            $this->assertNotEmpty($s['olay_sonrasi'] ?? null, $k['anahtar']);
        }
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

        $yanit = AcilDurumWordUretici::docx($plan, 'orijinal');

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'adep_test').'.docx';
        file_put_contents($gecici, $icerik);

        $zip = new \ZipArchive;
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($gecici);

        $this->assertStringContainsString('DENEME TEKSTİL SANAYİ LTD.', $xml);
        $this->assertStringContainsString('Test Mahallesi No:5 İzmir', $xml);
        $this->assertStringContainsString('11122233344', $xml);
        $this->assertStringContainsString('AYŞE YILMAZ', $xml);   // Md.12(1)(b): hazırlayanın adı

        $this->assertStringNotContainsString('ALTIN YAKUT', $xml);
        $this->assertStringNotContainsString('MEHMET ÖZDEMİR', $xml);
        $this->assertStringNotContainsString('44100010111205450770133000', $xml);
    }

    public function test_kapak_sayfasi_sablon_degerlerini_firmaya_gore_degistirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Deneme Tekstil Sanayi Ltd.',
            'adres' => 'Test Mahallesi No:5 İzmir',
            'sgk_sicil_no' => '11122233344',
            'nace_kodu' => '10.71',
            'tehlike_sinifi' => 'tehlikeli',
        ]);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill(['rapor_tarihi' => '2027-05-10', 'gecerlilik_tarihi' => '2031-05-10'])->save();

        $yanit = AcilDurumKapakUretici::pptx($plan);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'adep_kapak_test').'.pptx';
        file_put_contents($gecici, $icerik);

        $zip = new \ZipArchive;
        $zip->open($gecici);
        $xml = $zip->getFromName('ppt/slides/slide1.xml');
        $zip->close();
        unlink($gecici);

        $this->assertStringContainsString('DENEME TEKSTİL SANAYİ LTD.', $xml);
        $this->assertStringContainsString('Test Mahallesi No:5 İzmir', $xml);
        $this->assertStringContainsString('11122233344', $xml);
        $this->assertStringContainsString('10.71', $xml);
        $this->assertStringContainsString('10.05.2027', $xml);
        $this->assertStringContainsString('10.05.2031', $xml);
        $this->assertStringNotContainsString('ALTIN YAKUT', $xml);
        $this->assertStringNotContainsString('44100010111205450770133000', $xml);
        $this->assertStringNotContainsString('41.00.01', $xml);
    }

    public function test_kapagi_olmayan_sablon_icin_kapak_aksiyonu_404_doner(): void
    {
        config(['isg.acil_durum.word_sablonlari.bos' => [
            'ad' => 'Kapaksız Şablon', 'aciklama' => 'test', 'dosya' => 'acil-durum-plani-sablonu.docx',
        ]]);

        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);

        $this->expectException(HttpException::class);
        AcilDurumKapakUretici::pptx($plan, 'bos');
    }

    public function test_sayfada_sablon_secimi_word_ve_kapak_aksiyonuna_yansir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertSet('sablonId', 'fom')
            ->assertActionHidden('kapakSayfasi')          // kapsamlı planın kapağı belgenin içinde
            ->callAction('word')
            ->assertSuccessful()
            ->set('sablonId', 'orijinal')
            ->assertActionVisible('kapakSayfasi')
            ->callAction('word')
            ->assertSuccessful()
            ->callAction('kapakSayfasi')
            ->assertSuccessful();
    }

    public function test_kapsamli_word_ciktisi_fom_sablonunu_firma_verisiyle_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'BERT PLASTİK KAUÇUK LTD. ŞTİ.', 'sgk_sicil_no' => '22212010112247420160872000', 'isveren_ad' => 'Serdar Özgürel',
        ]);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill([
            'konular' => ['yangin', 'sel'], 'revizyon_no' => 'Rev.02 — 05.05.2027',
            'uygulama' => ['koordinator_ad' => 'Selin Ak', 'onleyici_tedbirler' => "Birinci tedbir\nİkinci tedbir"],
        ])->save();
        $ekip = AcilEkip::create(['firma_id' => $firma->id, 'tur' => 'teknik', 'ad' => 'Teknik Müdahale Ekibi']);
        $ekip->uyeler()->create(['ad_soyad' => 'Kemal Usta', 'telefon' => '0533 222 33 44']);

        $icerik = (new AcilDurumBelgeWord)->uret($plan->fresh(), resource_path('belge/acil-durum-plani-fom.docx'));
        $yol = tempnam(sys_get_temp_dir(), 'adep');
        file_put_contents($yol, $icerik);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($yol) === true);
        $belge = $zip->getFromName('word/document.xml');
        $ust = $zip->getFromName('word/header1.xml');
        $iliski = $zip->getFromName('word/_rels/document.xml.rels');
        $zip->close();
        unlink($yol);

        // Geçerli XML (Word açabilir)
        $this->assertTrue((new \DOMDocument)->loadXML($belge));
        // Kapak + üst bilgi yeni firmaya göre
        $this->assertStringContainsString('BERT PLASTİK KAUÇUK LTD. ŞTİ.', $belge);
        $this->assertStringContainsString('22212010112247420160872000', $belge);
        $this->assertStringContainsString('Adı Soyadı: Serdar Özgürel', $belge);
        $this->assertStringNotContainsString('FOM MAKİNA', $belge);
        $this->assertStringNotContainsString('22841010115350010161228000', $belge);
        $this->assertStringContainsString('BERT PLASTİK KAUÇUK LTD. ŞTİ.', $ust);
        $this->assertStringContainsString('Rev.02', $ust);
        $this->assertStringContainsString('05.05.2027', $ust);
        // Gövde: FOM stilleri + sistem verisi
        $this->assertStringContainsString('<w:pStyle w:val="Balk1"/>', $belge);
        $this->assertStringContainsString('<w:pStyle w:val="ListeParagraf"/>', $belge);
        $this->assertStringContainsString('Selin Ak', $belge);
        $this->assertStringContainsString('İkinci tedbir', $belge);
        $this->assertStringContainsString('Kemal Usta', $belge);
        $this->assertStringContainsString('1. ACİL DURUM: YANGIN', $belge);
        $this->assertStringContainsString('2. ACİL DURUM: SEL VE SU BASKINI', $belge);
        $this->assertStringContainsString('EK-7 TAHLİYE VE ÇIKIŞ PLANI', $belge);
        // Yangın akış şeması resim olarak eklendi
        $this->assertStringContainsString('rIdAdep', $belge);
        $this->assertMatchesRegularExpression('/Id="rIdAdep\d+"[^>]*media\/adep\d+\.png/', $iliski);
    }

    public function test_tahliye_plani_gorseli_yuklenir(): void
    {
        Storage::fake('public');

        $firma = Firma::factory()->for($this->uzman)->create();
        AcilDurumPlani::firmaIcin($firma);

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('tahliyePlani', data: [
                'tahliye_plani_gorseli' => UploadedFile::fake()->image('kroki.jpg'),
            ]);

        $plan = AcilDurumPlani::where('firma_id', $firma->id)->firstOrFail();
        $this->assertNotNull($plan->tahliye_plani_gorseli);
        Storage::disk('public')->assertExists($plan->tahliye_plani_gorseli);
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

        $firma = Firma::factory()->for($uzman)->create(['nace_kodu' => '41.00', 'isveren_ad' => 'Hasan Kaya', 'adres' => 'OSB 3. Cad. No:7']);
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill([
            'revizyon_no' => 'Rev.01 — 01.01.2027',
            'toplanma_yeri' => 'Ana kapı önü açık saha',
            'disaridan_etkileyebilecek_isyerleri' => 'Komşu Akaryakıt A.Ş. — Akaryakıt istasyonu',
            'konular' => ['yangin', 'deprem'],
            'uygulama' => [
                'onleyici_tedbirler' => 'Talaşlar her vardiya sonu uzaklaştırılır',
                'mudahale_yontemi' => 'Alarm → 112 → tahliye → sayım',
                'ozel_risk_alanlari' => 'var', 'ozel_risk_notu' => 'Kaynak tüpü deposu (arka avlu)',
                'enerji_kesme' => 'var', 'enerji_kesme_notu' => 'Ana pano giriş sağında',
                'ozel_destek' => 'Gebe çalışana vardiya amiri refakat eder',
                'iletisim' => [['ad' => 'Devlet Hastanesi', 'telefon' => '0224 000 00 00', 'aciklama' => 'En yakın hastane']],
                'son_tatbikat' => '2026-05-04',
            ],
        ])->save();
        $ekip = AcilEkip::create(['firma_id' => $firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme Ekibi']);
        $ekip->uyeler()->create(['ad_soyad' => 'Ali Veli', 'gorev' => 'Torna Operatörü', 'bolum' => 'Atölye', 'telefon' => '0532 111 22 33', 'lider' => true]);
        $ekip->uyeler()->create(['ad_soyad' => 'Can Er', 'uyelik' => 'yedek', 'vardiya' => 'Gece']);

        // dompdf metin katmanini ayiklamak yerine, uretimin hata vermeden
        // gectigini ve HTML asamasinda beklenen icerigin goruntuye girdigini
        // dogrulamak icin blade'i dogrudan render ediyoruz.
        $html = view('pdf.acil-durum-plani', [
            'plan' => $plan->fresh('firma'),
            'firma' => $firma->fresh(),
            'hakkinda' => config('isg.acil_durum.hakkinda'),
        ])->render();

        // Md.12(1)(a)-(c): unvan, adres, işveren, hazırlayan adı-unvanı, tarihler
        $this->assertStringContainsString('Mehmet Özdemir', $html);
        $this->assertStringContainsString('Hasan Kaya', $html);
        $this->assertStringContainsString('OSB 3. Cad. No:7', $html);
        $this->assertStringContainsString('Rev.01', $html);
        $this->assertStringContainsString('Ana kapı önü açık saha', $html);
        $this->assertStringContainsString('Komşu Akaryakıt A.Ş.', $html);
        // (d)-(e): tedbirler, müdahale/tahliye, refakat, özel risk bölümleri, kesim noktaları
        $this->assertStringContainsString('Talaşlar her vardiya sonu', $html);
        $this->assertStringContainsString('Alarm → 112 → tahliye → sayım', $html);
        $this->assertStringContainsString('Gebe çalışana vardiya amiri', $html);
        $this->assertStringContainsString('Kaynak tüpü deposu (arka avlu)', $html);
        $this->assertStringContainsString('Ana pano giriş sağında', $html);
        // (f)(4): görevli adı, unvanı, sorumluluk alanı, iletişim, yedek
        $this->assertStringContainsString('Torna Operatörü', $html);
        $this->assertStringContainsString('0532 111 22 33', $html);
        $this->assertStringContainsString('(Ekip başı)', $html);
        $this->assertStringContainsString('Can Er', $html);
        $this->assertStringContainsString('Yedek görevli', $html);
        $this->assertStringContainsString('Gece', $html);
        // (f)(5): ulusal + yerel irtibat (EK-2) — hastane listeden "EN YAKIN HASTANE" satırına
        $this->assertStringContainsString('EK-2 ACİL İLETİŞİM LİSTESİ', $html);
        $this->assertStringContainsString('AMBULANS İLKYARDIM', $html);
        $this->assertStringContainsString('Devlet Hastanesi', $html);
        $this->assertSame(1, substr_count($html, 'Devlet Hastanesi'));
        $this->assertStringNotContainsString('155', $html);
        // FOM düzeni: bölümler ve ekler
        foreach (['1. GİRİŞ', '3.2.7 Teknik Müdahale Ekibi', 'OLASI ACİL DURUMLAR ve RİSK DEĞERLENDİRMESİ', 'EK-1 ACİL DURUMLAR VE MÜDAHALE YÖNTEMLERİ',
            'EK-3 FAALİYETLERE GÖRE', 'EK-4 DESTEK ELEMANI ATAMA YAZISI', 'EK-5 FİRMA BİLGİLERİ', 'EK-6 ÇEVRESEL RİSKLER', 'EK-7 TAHLİYE VE ÇIKIŞ PLANI', 'DEĞİŞME VE DÜZELTMELER'] as $baslik) {
            $this->assertStringContainsString($baslik, $html);
        }
        // EK-1: seçili konuların senaryo metni + akış şeması
        $this->assertStringContainsString('1. ACİL DURUM: YANGIN', $html);
        $this->assertStringContainsString('2. ACİL DURUM: DEPREM', $html);
        $this->assertStringContainsString('acil-durum-akis', $html);
        $this->assertStringContainsString('Çök – Kapan – Tutun', $html);
        $this->assertStringNotContainsString('ACİL DURUM: SEL', $html);
        // Md.13-15: tatbikat ve yenileme
        $this->assertStringContainsString('04.05.2026', $html);
        $this->assertStringContainsString('NACE: 41.00', $html);

        $yanit = AcilDurumPlaniUretici::pdf($plan);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_plan_pdf_onay_bolumunde_uzman_ve_hekimin_kase_imzasi_gorunur(): void
    {
        $this->uzman->forceFill(['kase_gorseli' => 'uzman-kase/test-kase.png', 'imza_gorseli' => 'uzman-kase/test-imza.png'])->save();
        $hekim = IsgProfesyoneli::factory()->create(['tip' => 'isyeri_hekimi', 'kase_gorseli' => 'hekim-kase/test-kase.png']);

        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);
        $plan = AcilDurumPlani::firmaIcin($firma);

        $html = view('pdf.acil-durum-plani', [
            'plan' => $plan->fresh('firma'),
            'firma' => $firma->fresh(),
            'hakkinda' => config('isg.acil_durum.hakkinda'),
        ])->render();

        $this->assertStringContainsString('uzman-kase/test-kase.png', $html);
        $this->assertStringContainsString('uzman-kase/test-imza.png', $html);
        $this->assertStringContainsString('hekim-kase/test-kase.png', $html);
        $this->assertStringNotContainsString($hekim->ad_soyad, $html);

        // Görsel gerçekte diskte olmasa bile dompdf üretimi hata vermemeli.
        $yanit = AcilDurumPlaniUretici::pdf($plan);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_imzasiz_secilince_kase_ve_imza_gorselleri_basilmaz(): void
    {
        $this->uzman->forceFill(['kase_gorseli' => 'uzman-kase/test-kase.png', 'imza_gorseli' => 'uzman-kase/test-imza.png'])->save();
        $hekim = IsgProfesyoneli::factory()->create(['tip' => 'isyeri_hekimi', 'kase_gorseli' => 'hekim-kase/test-kase.png']);

        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);
        $plan = AcilDurumPlani::firmaIcin($firma);

        $veri = [
            'plan' => $plan->fresh('firma'),
            'firma' => $firma->fresh(),
            'hakkinda' => config('isg.acil_durum.hakkinda'),
        ];

        $imzasiz = view('pdf.acil-durum-plani', [...$veri, 'imzali' => false])->render();
        $this->assertStringNotContainsString('uzman-kase/test-kase.png', $imzasiz);
        $this->assertStringNotContainsString('uzman-kase/test-imza.png', $imzasiz);
        $this->assertStringNotContainsString('hekim-kase/test-kase.png', $imzasiz);
        $this->assertStringNotContainsString($hekim->ad_soyad, $imzasiz);

        $yanit = AcilDurumPlaniUretici::pdf($plan, false);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
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

    public function test_hazir_dosyasi_olan_afis_firma_seridiyle_yeniden_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $yanit = AcilDurumPlaniUretici::afis($firma, 'sabotaj', 'a4');

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('%PDF', $icerik);

        // Statik kaynak dosya AYNEN akıtılmıyor — her firma için firma bilgi
        // şeridiyle yeniden birleştiriliyor (aksi halde tüm firmalar hazır
        // dosyada sabit yazılı olan BAŞKA bir firmanın adını görürdü).
        $this->assertNotSame(
            file_get_contents(resource_path('belge/acil-durum-afisleri/sabotaj.pdf')),
            $icerik,
        );
    }

    public function test_a3_secimi_gercekten_daha_buyuk_fiziksel_sayfa_uretir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        ob_start();
        AcilDurumPlaniUretici::afis($firma, 'yangin', 'a4')->sendContent();
        $a4 = ob_get_clean();

        ob_start();
        AcilDurumPlaniUretici::afis($firma, 'yangin', 'a3')->sendContent();
        $a3 = ob_get_clean();

        preg_match('/\/MediaBox\s*\[([^\]]+)\]/', $a4, $a4Kutu);
        preg_match('/\/MediaBox\s*\[([^\]]+)\]/', $a3, $a3Kutu);

        $this->assertNotEmpty($a4Kutu);
        $this->assertNotEmpty($a3Kutu);
        $this->assertNotSame($a4Kutu[1], $a3Kutu[1]);

        // A3'ün alanı A4'ten büyük olmalı — yalnız dosya adı değişmiyor.
        $a4Alan = (float) explode(' ', trim($a4Kutu[1]))[2] * (float) explode(' ', trim($a4Kutu[1]))[3];
        $a3Alan = (float) explode(' ', trim($a3Kutu[1]))[2] * (float) explode(' ', trim($a3Kutu[1]))[3];
        $this->assertGreaterThan($a4Alan * 1.5, $a3Alan);
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

        $this->expectException(NotFoundHttpException::class);
        AcilDurumPlaniUretici::afis($firma, 'gecersiz', 'a4');
    }

    public function test_plan_olusturulunca_firmaya_uygun_afisler_otomatik_tanimlanir(): void
    {
        // kimyasal koşullu (nace 19-...+çok tehlikeli) — kosulsuz + kimyasal seçili olmalı
        $kimyasalFirma = Firma::factory()->for($this->uzman)
            ->create(['tehlike_sinifi' => 'cok_tehlikeli', 'nace_kodu' => '19.20']);
        $plan1 = AcilDurumPlani::firmaIcin($kimyasalFirma);

        $tipler1 = AcilDurumPlaniUretici::afisTipleri($plan1);
        $this->assertContains('yangin', $tipler1);
        $this->assertContains('deprem', $tipler1);
        $this->assertContains('sabotaj', $tipler1);
        $this->assertContains('is_kazasi', $tipler1);
        $this->assertContains('elektrik', $tipler1);
        $this->assertContains('sel', $tipler1);
        $this->assertContains('kimyasal', $tipler1);
        $this->assertCount(7, $tipler1);

        // koşulu tutmayan firma — kimyasal afişi otomatik listede olmamalı
        $sadeFirma = Firma::factory()->for($this->uzman)
            ->create(['tehlike_sinifi' => 'az_tehlikeli', 'nace_kodu' => '99.99']);
        $plan2 = AcilDurumPlani::firmaIcin($sadeFirma);

        $tipler2 = AcilDurumPlaniUretici::afisTipleri($plan2);
        $this->assertNotContains('kimyasal', $tipler2);
        $this->assertCount(6, $tipler2);
    }

    public function test_konu_elle_kaldirilinca_afis_de_otomatik_listeden_duser(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);
        $plan->forceFill(['konular' => array_values(array_diff($plan->konular, ['yangin']))])->save();

        $this->assertNotContains('yangin', AcilDurumPlaniUretici::afisTipleri($plan->fresh()));
    }

    public function test_tum_afisler_zip_olarak_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $plan = AcilDurumPlani::firmaIcin($firma);

        $yanit = AcilDurumPlaniUretici::afisZip($firma, AcilDurumPlaniUretici::afisTipleri($plan), 'a4');

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'test-afis-zip').'.zip';
        file_put_contents($gecici, $icerik);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($gecici) === true);
        $this->assertSame(count(AcilDurumPlaniUretici::afisTipleri($plan)), $zip->numFiles);
        $zip->close();
        unlink($gecici);
    }

    public function test_tanimli_afis_yoksa_zip_404_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->expectException(HttpException::class);
        AcilDurumPlaniUretici::afisZip($firma, [], 'a4');
    }

    public function test_sayfadan_tum_afisler_zip_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('afislerZipIndir')
            ->assertSuccessful();
    }

    public function test_kroki_plani_kisayolu_dogru_firmaya_gider(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AcilDurumSayfasi::class)
            ->set('firmaId', $firma->id)
            ->assertActionVisible('krokiPlani')
            ->assertActionHasUrl('krokiPlani', AcilDurumKrokisi::getUrl(['firma' => $firma->id]));
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
