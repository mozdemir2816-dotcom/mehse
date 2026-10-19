<?php

namespace Tests\Feature;

use App\Filament\Pages\MuayeneFormu as MuayeneSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\MuayeneFormu;
use App\Models\User;
use App\Support\MuayeneFormuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class MuayeneFormuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_kontrol_listeleri_konfigurasyondan_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id);

        $this->assertCount(count(config('isg.muayene.sistemik_muayene_basliklari')), $component->get('sistemikMuayene'));
        $this->assertCount(count(config('isg.muayene.tetkikler')), $component->get('tetkikler'));
        $this->assertSame('normal', $component->get('sistemikMuayene')[0]['sonuc']);
    }

    public function test_firma_calisanindan_hizli_secim_alanlari_doldurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '12345678901', 'gorev' => 'Operatör']);

        $component = Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanHizliSecId', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('calisanAdSoyad'));
        $this->assertSame('12345678901', $component->get('calisanTc'));
        $this->assertSame('Operatör', $component->get('calisanGorevi'));
    }

    public function test_firma_secilince_hekim_adi_otomatik_dolar(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Dr. Ayşe Kaya', 'tip' => 'isyeri_hekimi']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        $component = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('Dr. Ayşe Kaya', $component->get('hekimAdi'));
    }

    public function test_tehlike_sinifina_gore_kontrol_tarihi_onerilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $component = Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('muayeneTarihi', '2026-01-15')
            ->call('kontrolTarihiOner');

        $this->assertSame('2027-01-15', $component->get('onerilenKontrolTarihi'));
    }

    public function test_calisan_veya_sonuc_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('muayene_formlari', 0);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png', 'tip' => 'isyeri_hekimi']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('sonucKanaati', 'sartli_uygun')
            ->set('sartAciklamasi', 'Gece vardiyasında çalışamaz')
            ->set('sistemikMuayene.0.sonuc', 'anormal')
            ->set('tetkikler.0.yapildi', true)
            ->set('tetkikler.0.sonuc', 'normal')
            ->callAction('pdf');

        $m = MuayeneFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Ahmet Yılmaz', $m->calisan_ad_soyad);
        $this->assertSame('sartli_uygun', $m->sonuc_kanaati);
        $this->assertSame('Gece vardiyasında çalışamaz', $m->sart_aciklamasi);
        $this->assertSame('anormal', $m->sistemik_muayene[0]['sonuc']);
        $this->assertTrue($m->tetkikler[0]['yapildi']);
        $this->assertSame('isg-profesyonel-kase/x.png', $m->hekim_kase);
        $this->assertStringStartsWith('MF-'.now()->year.'-', $m->belge_no);
    }

    public function test_uygun_disinda_sonucta_sart_aciklamasi_kaydedilmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('sonucKanaati', 'uygun')
            ->set('sartAciklamasi', 'bu asla kaydedilmemeli')
            ->callAction('pdf');

        $m = MuayeneFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertNull($m->sart_aciklamasi);
    }

    public function test_ek2_firma_ve_calisandan_alanlar_onceden_dolar(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Dr. Ayşe Kaya', 'tip' => 'isyeri_hekimi', 'sertifika_no' => 'IH-4567']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id, 'telefon' => '0262 111 22 33', 'eposta' => 'ik@ornek.com']);
        $c = Calisan::factory()->for($firma)->create([
            'ad_soyad' => 'Ahmet Yılmaz', 'gorev' => 'Pres Operatörü', 'departman' => 'Pres Hattı',
            'kan_grubu' => 'A Rh(+)', 'telefon' => '0555 000 11 22', 'cinsiyet' => 'erkek', 'dogum_tarihi' => '1990-05-17',
        ]);

        $sayfa = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id);
        $this->assertSame('0262 111 22 33', $sayfa->get('ek2')['isyeri_tel']);
        $this->assertSame('ik@ornek.com', $sayfa->get('ek2')['isyeri_eposta']);
        $this->assertSame('IH-4567', $sayfa->get('ek2')['hekim']['belge_no']);

        $sayfa->set('calisanHizliSecId', $c->id);
        $ek2 = $sayfa->get('ek2');
        $this->assertSame('A Rh(+)', $ek2['kan_grubu']);
        $this->assertSame('Erkek', $ek2['cinsiyet']);
        $this->assertSame('0555 000 11 22', $ek2['calisan_tel']);
        $this->assertSame('Pres Hattı', $ek2['calistigi_bolum']);
        $this->assertSame('Pres Operatörü', $ek2['yaptigi_is']);
        $this->assertSame('1990-05-17', $sayfa->get('calisanDogumTarihi'));
    }

    public function test_ek2_hizli_doldurma_ve_onceki_isyeri_satirlari(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $sayfa = Livewire::test(MuayeneSayfasi::class)->set('firmaId', $firma->id)
            ->set('ek2.fizik.kvs', 'Elle yazılan bulgu')
            ->call('anamnezHayir')
            ->call('fizikNormalDoldur');

        $ek2 = $sayfa->get('ek2');
        $this->assertCount(10, $ek2['oz']);
        $this->assertSame(['Hayır'], array_values(array_unique(array_column($ek2['oz'], 'cevap'))));
        $this->assertSame('Elle yazılan bulgu', $ek2['fizik']['kvs']);   // dolu olana dokunulmaz
        $this->assertStringContainsString('Batın rahat', $ek2['fizik']['sindirim']);
        $this->assertCount(9, array_filter($ek2['fizik']));

        // Önceki işyeri satırı: varsayılan 2, ekle/sil, en az 1 kalır
        $this->assertCount(2, $ek2['onceki']);
        $sayfa->call('oncekiIsEkle');
        $this->assertCount(3, $sayfa->get('ek2')['onceki']);
        $sayfa->call('oncekiIsSil', 0)->call('oncekiIsSil', 0)->call('oncekiIsSil', 0);
        $this->assertCount(1, $sayfa->get('ek2')['onceki']);
    }

    public function test_ek2_kaydedilir_ve_pdf_referans_sablon_bolumlerini_icerir(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Dr. Ayşe Kaya', 'tip' => 'isyeri_hekimi', 'sertifika_no' => 'IH-4567', 'kase_gorseli' => 'isg-profesyonel-kase/k.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek Metal A.Ş.', 'sgk_sicil_no' => '1234567', 'tehlike_sinifi' => 'tehlikeli', 'isyeri_hekimi_id' => $hekim->id]);

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('calisanAdSoyad', 'Ahmet Yılmaz')
            ->set('calisanGorevi', 'Operatör')
            ->set('muayeneTuru', 'ise_giris')
            ->set('sonucKanaati', 'sartli_uygun')
            ->set('sartAciklamasi', 'Gürültülü ortamda kulak koruyucu kullanır')
            ->set('ek2.kan_grubu', '0 Rh(+)')
            ->set('ek2.dogum_yeri', 'Kocaeli')
            ->set('ek2.onceki.0.isyeri', 'Eski Tekstil Ltd.')
            ->set('ek2.asi.tetanoz.durum', 'Tam')
            ->set('ek2.soygecmis.anne', 'HT')
            ->set('ek2.oz.3.cevap', 'Evet')
            ->set('ek2.oz.3.aciklama', 'Apandisit')
            ->set('ek2.oz.1.cevap', 'Hayır')
            ->set('ek2.sigara.durum', 'Evet')
            ->set('ek2.sigara.detay', 'Günde 10 adet')
            ->set('ek2.vital.boy', '180')
            ->set('ek2.vital.kilo', '80')
            ->set('ek2.vital.kan_basinci', '120/80')
            ->set('ek2.fizik.kvs', 'S1+, S2+ ritmik')
            ->set('ek2.lab.biyolojik.tarih', '2026-10-01')
            ->set('ek2.lab.biyolojik.sonuc', 'Hemogram normal')
            ->set('ek2.kanaat.yuksekte', true)
            ->set('ek2.hekim.diploma', '12.06.2010 / 98765')
            ->callAction('pdf');

        $m = MuayeneFormu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('Kocaeli', $m->ek2['dogum_yeri']);
        $this->assertSame('Apandisit', $m->ek2['oz'][3]['aciklama']);
        $this->assertSame('IH-4567', $m->ek2['hekim']['belge_no']);

        $html = view('pdf.muayene-formu', ['form' => $m, 'firma' => $firma, 'imzali' => true])->render();

        // Referans şablonun bölüm başlıkları ve yasal metinleri
        foreach (['EK-2', 'İŞE GİRİŞ / PERİYODİK MUAYENE FORMU', '1. İŞYERİNİN BİLGİLERİ', '2. ÇALIŞANIN BİLGİLERİ', '3. TIBBİ ANAMNEZ', '4. FİZİK MUAYENE SONUÇLARI',
            '5. LABORATUVAR BULGULARI VE DİĞER TETKİKLER', '6. KANAAT VE SONUÇ (*)', 'Daha Önce Çalıştığı Yerler',
            'ELVERİŞLİDİR', 'İşyeri Hekimliği Belge No', 'Diploma Tarih / No',
            '"İşe giriş / periyodik muayene olmayı kabul ettiğimi, muayene sırasında verdiğim bilgilerin doğru ve eksiksiz olduğunu ve tahlil sonuçlarım hakkında bilgilendirildiğimi beyan ederim."',
            '(*) Yapılan muayene sonucunda çalışanın gece veya vardiyalı çalışma koşullarında'] as $metin) {
            $this->assertStringContainsString($metin, $html, $metin);
        }

        // Girilen değerler
        foreach (['Örnek Metal A.Ş.', '1234567', 'Ahmet Yılmaz', 'Kocaeli', 'Eski Tekstil Ltd.', 'Apandisit', 'Günde 10 adet', '24.7 — Normal', '120/80', 'S1+, S2+ ritmik',
            '01.10.2026', 'Hemogram normal', 'Gürültülü ortamda kulak koruyucu kullanır', 'Dr. Ayşe Kaya', 'IH-4567', '12.06.2010 / 98765', 'isg-profesyonel-kase/k.png'] as $deger) {
            $this->assertStringContainsString(e($deger), $html, $deger);
        }

        // İşaretler: işe giriş türü, şartlı uygunluk, Evet / Hayır
        $this->assertStringContainsString('☒ İşe Giriş Muayenesi', $html);
        $this->assertStringContainsString('☐ Periyodik Muayene', $html);
        $this->assertStringContainsString('☒ <strong>2.</strong>', $html);
        $this->assertStringContainsString('☐ <strong>1.</strong>', $html);
        $this->assertStringContainsString('☒ Evet', $html);
        $this->assertStringContainsString('☒ Yüksekte / tehlikeli işlerde çalışabilir.', $html);
        $this->assertStringContainsString('Tehlikeli', $html);

        // İmzasız nüshada kaşe görseli basılmaz
        $imzasiz = view('pdf.muayene-formu', ['form' => $m, 'firma' => $firma, 'imzali' => false])->render();
        $this->assertStringNotContainsString('isg-profesyonel-kase/k.png', $imzasiz);

        // Gerçek PDF üretimi
        ob_start();
        MuayeneFormuUretici::pdf($m)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_ek2_olmayan_eski_kayit_pdfde_eski_alanlari_korur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $m = MuayeneFormu::create([
            'firma_id' => $firma->id, 'calisan_ad_soyad' => 'Eski Kayıt', 'muayene_turu' => 'araya_giren', 'sonuc_kanaati' => 'is_degisikligi',
            'meslek_oykusu' => 'Eski meslek öyküsü', 'ozgecmis' => 'Eski özgeçmiş notu',
            'sistemik_muayene' => [['baslik' => 'Genel Durum', 'sonuc' => 'anormal', 'not' => 'Zayıf']],
            'tetkikler' => [['anahtar' => 'odyometri', 'yapildi' => true, 'sonuc' => 'anormal', 'not' => 'Sağ kulak kaybı'], ['anahtar' => 'ekg', 'yapildi' => false, 'sonuc' => null, 'not' => null]],
        ]);
        $this->assertNull($m->fresh()->ek2);

        $html = view('pdf.muayene-formu', ['form' => $m->fresh(), 'firma' => $firma, 'imzali' => true])->render();

        $this->assertStringContainsString('Eski meslek öyküsü', $html);
        $this->assertStringContainsString('Eski özgeçmiş notu', $html);
        $this->assertStringContainsString('Genel Durum', $html);
        $this->assertStringContainsString('Zayıf', $html);
        $this->assertStringContainsString('Sağ kulak kaybı', $html);
        $this->assertStringContainsString('İş Değişikliği Önerilir', $html);
        $this->assertStringContainsString('☒ İşe Dönüş / Ek Muayene', $html);   // araya_giren → ek muayene
        $this->assertStringNotContainsString('Elektrokardiyografi', $html);       // yapılmayan tetkik basılmaz
    }

    public function test_ek2_birlestir_ve_bki_yardimcilari(): void
    {
        $b = MuayeneFormu::ek2Birlestir(['kan_grubu' => 'B Rh(+)', 'vital' => ['boy' => '170'], 'bilinmeyen' => 'x', 'onceki' => [['isyeri' => 'A', 'is' => null, 'baslama' => null, 'ayrilma' => null]]]);
        $this->assertSame('B Rh(+)', $b['kan_grubu']);
        $this->assertSame('170', $b['vital']['boy']);
        $this->assertNull($b['vital']['kilo']);                 // eksik alt anahtar şablondan
        $this->assertArrayNotHasKey('bilinmeyen', $b);
        $this->assertCount(1, $b['onceki']);                    // liste olduğu gibi gelir
        $this->assertCount(10, MuayeneFormu::ek2Birlestir(null)['oz']);
        $this->assertSame('Hayır', MuayeneFormu::ek2Birlestir(null)['sigara']['durum']);

        $this->assertNull(MuayeneFormu::bki(null, 80));
        $this->assertSame(['deger' => 24.7, 'sinif' => 'Normal'], MuayeneFormu::bki('180', '80'));
        $this->assertSame('Zayıf', MuayeneFormu::bki(180, 55)['sinif']);
        $this->assertSame('Fazla Kilolu', MuayeneFormu::bki('175', '85,5')['sinif']);
        $this->assertSame('Obez (BKİ ≥ 30)', MuayeneFormu::bki(170, 100)['sinif']);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $m = MuayeneFormu::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Test Kişi',
            'sonuc_kanaati' => 'uygun',
        ]);

        $yanit = MuayeneFormuUretici::pdf($m);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $m = MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'X', 'sonuc_kanaati' => 'uygun']);

        Livewire::test(MuayeneSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $m->id);

        $this->assertDatabaseMissing('muayene_formlari', ['id' => $m->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(MuayeneSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
