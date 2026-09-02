<?php

namespace Tests\Feature;

use App\Filament\Pages\AtamaYazilari as AtamaSayfasi;
use App\Filament\Pages\EgitimKatilim;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\AtamaYazisiUretici;
use App\Support\AtamaYazisiWordUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AtamaYazilariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_tekli_rol_manuel_uye_ile_kaydedilir_ve_pdf_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_ad' => 'Ali Patron']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'calisan_temsilcisi')
            ->set('tekAdSoyad', 'Ahmet Yılmaz')
            ->set('tekTc', '12345678901')
            ->set('basTemsilci', true)
            ->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('calisan_temsilcisi', $kayit->rol_anahtari);
        $this->assertCount(1, $kayit->uyeler);
        $this->assertSame('Ahmet Yılmaz', $kayit->uyeler[0]['ad_soyad']);
        $this->assertTrue($kayit->uyeler[0]['bas_uye']);
        $this->assertStringStartsWith('ATM-'.now()->year.'-', $kayit->dokuman_no);
        $this->assertNotNull($kayit->gorev_baslangic);
    }

    public function test_firma_secilince_isveren_adi_otomatik_dolar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['isveren_vekili' => 'Vekil Bey', 'isveren_ad' => 'Asıl Patron']);

        $component = Livewire::test(AtamaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('Vekil Bey', $component->get('isverenVekiliAdi'));
    }

    public function test_ekip_rolu_coklu_calisan_ve_bas_uye_ile_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c1 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan Bir']);
        $c2 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan İki']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'sondurme_ekibi')
            ->call('calisanToggle', $c1->id)
            ->call('calisanToggle', $c2->id)
            ->call('basUyeSec', $c1->id)
            ->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(2, $kayit->uyeler);
        $this->assertNull($kayit->gorev_baslangic);
        $basUye = collect($kayit->uyeler)->firstWhere('bas_uye', true);
        $this->assertSame('Çalışan Bir', $basUye['ad_soyad']);
    }

    public function test_isg_kurulu_firma_profilinden_otomatik_doldur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->count(3)->create();

        $component = Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu')
            ->call('firmaProfilindenDoldur');

        $this->assertCount(3, $component->get('secilenCalisanIdler'));
    }

    public function test_isg_kurulu_atanmis_igu_ve_hekim_otomatik_secili_gelir_ve_kaydedilir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'ad_soyad' => 'İGU Ayşe', 'kase_gorseli' => 'isg-profesyonel-kase/ayse.png']);
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Mehmet']);
        $dsp = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'dsp', 'ad_soyad' => 'DSP Fatma']);
        $firma = Firma::factory()->for($this->uzman)->create([
            'igu_id' => $igu->id,
            'isyeri_hekimi_id' => $hekim->id,
            'dsp_id' => $dsp->id,
        ]);

        $component = Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu');

        $this->assertEqualsCanonicalizing([$igu->id, $hekim->id], $component->get('secilenProfesyonelIdler'));

        $component->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $uyeler = collect($kayit->uyeler);
        $this->assertTrue($uyeler->contains('ad_soyad', 'İGU Ayşe'));
        $this->assertTrue($uyeler->contains('ad_soyad', 'Dr. Mehmet'));
        $this->assertFalse($uyeler->contains('ad_soyad', 'DSP Fatma'));
        $this->assertSame('isg-profesyonel-kase/ayse.png', $uyeler->firstWhere('ad_soyad', 'İGU Ayşe')['kase_gorseli']);
    }

    public function test_isg_kurulu_profesyonel_toggle_ile_dsp_manuel_eklenir(): void
    {
        $dsp = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'dsp', 'ad_soyad' => 'DSP Fatma']);
        $firma = Firma::factory()->for($this->uzman)->create(['dsp_id' => $dsp->id]);

        $component = Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu')
            ->call('profesyonelToggle', $dsp->id);

        $this->assertSame([$dsp->id], $component->get('secilenProfesyonelIdler'));
    }

    public function test_isg_kurulu_calisana_kurul_gorevi_atanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'İK Sorumlusu Ayşe', 'gorev' => 'Muhasebe']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu')
            ->call('calisanToggle', $c->id)
            ->set('kurulGorevleri.'.$c->id, 'insan_kaynaklari')
            ->callAction('pdf');

        $kayit = AtamaYazisi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('İnsan Kaynakları Sorumlusu', collect($kayit->uyeler)->firstWhere('ad_soyad', 'İK Sorumlusu Ayşe')['gorev']);
    }

    public function test_uye_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'calisan_temsilcisi')
            ->callAction('pdf');

        $this->assertDatabaseCount('atama_yazilari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'isg_kurulu',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Üye Bir', 'tc' => null, 'gorev' => 'İGU', 'bas_uye' => true],
            ],
        ]);

        $yanit = AtamaYazisiUretici::pdf($kayit);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'bilgi_sahibi',
            'tarih' => now(),
            'uyeler' => [['ad_soyad' => 'Test', 'tc' => null, 'gorev' => null, 'bas_uye' => false]],
        ]);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $kayit->id);

        $this->assertDatabaseMissing('atama_yazilari', ['id' => $kayit->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(AtamaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_pdf_govde_metninde_rol_aciklamasi_yer_almaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'sondurme_ekibi',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Üye Bir', 'tc' => null, 'gorev' => null, 'bas_uye' => false],
                ['ad_soyad' => 'Üye İki', 'tc' => null, 'gorev' => null, 'bas_uye' => false],
            ],
        ]);

        $html = view('pdf.atama-yazisi', ['kayit' => $kayit, 'firma' => $firma, 'rol' => $kayit->rol()])->render();

        $this->assertStringNotContainsString(config('isg.atama.roller.sondurme_ekibi.aciklama'), $html);
        $this->assertStringContainsString('Üye Bir', $html);
        $this->assertStringContainsString('Üye İki', $html);
    }

    public function test_ekip_rolunde_her_uye_icin_ayri_sayfa_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'sondurme_ekibi',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Üye Bir', 'tc' => null, 'gorev' => null, 'bas_uye' => false],
                ['ad_soyad' => 'Üye İki', 'tc' => null, 'gorev' => null, 'bas_uye' => false],
            ],
        ]);

        $html = view('pdf.atama-yazisi', ['kayit' => $kayit, 'firma' => $firma, 'rol' => $kayit->rol()])->render();

        $this->assertSame(2, substr_count($html, 'class="sayfa"'));
    }

    public function test_word_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'isveren_vekili',
            'tarih' => now(),
            'uyeler' => [['ad_soyad' => 'Üye Bir', 'tc' => null, 'gorev' => null, 'bas_uye' => false]],
        ]);

        $yanit = AtamaYazisiWordUretici::docx($kayit);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('PK', $icerik);
    }

    private function docxMetni(string $binaryIcerik): string
    {
        $gecici = tempnam(sys_get_temp_dir(), 'tst').'.docx';
        file_put_contents($gecici, $binaryIcerik);

        $zip = new \ZipArchive();
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($gecici);

        return trim(preg_replace('/<[^>]*>/', ' ', preg_replace('/\s+/', ' ', $xml)));
    }

    public function test_word_sablonundaki_ornek_veriler_gercek_verilerle_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME SANAYİ A.Ş.']);
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'isveren_vekili',
            'tarih' => '2026-05-10',
            'uyeler' => [['ad_soyad' => 'Ali Veli', 'tc' => '11122233344', 'gorev' => 'Genel Müdür', 'bas_uye' => false]],
        ]);

        $yanit = AtamaYazisiWordUretici::docx($kayit);
        ob_start();
        $yanit->sendContent();
        $metin = $this->docxMetni(ob_get_clean());

        $this->assertStringContainsString('ACME SANAYİ A.Ş.', $metin);
        $this->assertStringContainsString('ALİ VELİ', $metin);
        $this->assertStringContainsString('11122233344', $metin);
        $this->assertStringContainsString('GENEL MÜDÜR', $metin);
        $this->assertStringContainsString('10.05.2026', $metin);
        $this->assertStringNotContainsString('NİL UNLU MAMULLER', $metin);
        $this->assertStringNotContainsString('MEHMET ÖZDEMİR', $metin);
        $this->assertStringNotContainsString('35479473338', $metin);
    }

    public function test_ekip_word_sablonunda_uye_satirlari_gercek_sayida_klonlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        Calisan::factory()->for($firma)->count(3)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'sondurme_ekibi',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Kişi Bir', 'tc' => '11111111111', 'gorev' => 'Operatör', 'bas_uye' => true],
                ['ad_soyad' => 'Kişi İki', 'tc' => '22222222222', 'gorev' => 'Teknisyen', 'bas_uye' => false],
            ],
        ]);

        $yanit = AtamaYazisiWordUretici::docx($kayit);
        ob_start();
        $yanit->sendContent();
        $metin = $this->docxMetni(ob_get_clean());

        $this->assertStringContainsString('KİŞİ BİR', $metin);
        $this->assertStringContainsString('KİŞİ İKİ', $metin);
        $this->assertStringContainsString('Ekip Başı', $metin);
        $this->assertStringContainsString('Tehlikeli', $metin);
        $this->assertStringContainsString('Çalışan Sayısı: 3', $metin);
        $this->assertStringContainsString('Asgari Görevlendirme: 1 kişi', $metin);
    }

    public function test_isg_kurulu_word_sablonunda_bes_ornek_satir_gercek_uyelerle_degisir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'isg_kurulu',
            'tarih' => now(),
            'uyeler' => [
                ['ad_soyad' => 'Kurul Üyesi', 'tc' => '33333333333', 'gorev' => 'İnsan Kaynakları Sorumlusu', 'bas_uye' => false],
            ],
        ]);

        $yanit = AtamaYazisiWordUretici::docx($kayit);
        ob_start();
        $yanit->sendContent();
        $metin = $this->docxMetni(ob_get_clean());

        $this->assertStringContainsString('KURUL ÜYESİ', $metin);
        $this->assertStringContainsString('İNSAN KAYNAKLARI SORUMLUSU', $metin);
        $this->assertStringNotContainsString('DFDFDF', $metin);
        $this->assertStringNotContainsString('HASAN ASDAD', $metin);
    }

    public function test_acil_durum_koordinatoru_icin_word_sablonu_yok(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = AtamaYazisi::create([
            'firma_id' => $firma->id,
            'rol_anahtari' => 'acil_durum_koordinatoru',
            'tarih' => now(),
            'uyeler' => [['ad_soyad' => 'Test', 'tc' => null, 'gorev' => null, 'bas_uye' => false]],
        ]);

        $this->assertFalse(AtamaYazisiWordUretici::sablonVarMi('acil_durum_koordinatoru'));
        $this->assertNull(AtamaYazisiWordUretici::docx($kayit));
    }

    public function test_word_butonu_sablonu_olmayan_rolde_gizli(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'acil_durum_koordinatoru')
            ->assertActionHidden('word');
    }

    public function test_word_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'calisan_temsilcisi')
            ->set('tekAdSoyad', 'Ahmet Yılmaz')
            ->callAction('word');

        $this->assertDatabaseCount('atama_yazilari', 1);
    }

    public function test_egitim_formu_olustur_katilimcilari_aktarir_ve_yonlendirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c1 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan Bir']);
        $c2 = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan İki']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'sondurme_ekibi')
            ->call('calisanToggle', $c1->id)
            ->call('calisanToggle', $c2->id)
            ->call('egitimFormuOlustur')
            ->assertRedirect(EgitimKatilim::getUrl());

        $this->assertDatabaseCount('atama_yazilari', 1);

        $component = Livewire::test(EgitimKatilim::class);

        $this->assertSame($firma->id, $component->get('firmaId'));
        $this->assertSame('sondurme_ekibi', $component->get('baslikAnahtari'));
        $this->assertCount(2, $component->get('manuelKatilimcilar'));
    }

    public function test_egitim_formu_olustur_isg_kurulu_profesyonelini_katilimciya_dahil_etmez(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'ad_soyad' => 'İGU Ayşe']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Çalışan Bir']);

        Livewire::test(AtamaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('rolAnahtari', 'isg_kurulu')
            ->call('calisanToggle', $c->id)
            ->call('egitimFormuOlustur');

        $component = Livewire::test(EgitimKatilim::class);

        $adlar = collect($component->get('manuelKatilimcilar'))->pluck('ad_soyad');
        $this->assertTrue($adlar->contains('Çalışan Bir'));
        $this->assertFalse($adlar->contains('İGU Ayşe'));
    }
}
