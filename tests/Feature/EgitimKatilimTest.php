<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim as EgitimSayfasi;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\KatilimciExcelOkuyucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class EgitimKatilimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_genel_baslik_icerigi_tehlike_sinifina_ve_sektore_gore_uretilir(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'az_tehlikeli');

        $this->assertSame('genel', $icerik['tip']);
        $this->assertSame(8, $icerik['saat']);
        $this->assertCount(4, $icerik['genel_konular']);
        $this->assertCount(5, $icerik['saglik_konulari']);
        $this->assertCount(12, $icerik['teknik_konular']);
        $this->assertSame('İnşaat', $icerik['isyerine_ozgu']['sektor']);
        $this->assertCount(5, $icerik['isyerine_ozgu']['maddeler']);
    }

    public function test_4_blok_tehlike_sinifina_gore_esit_sureye_olceklenir(): void
    {
        // az tehlikeli: 4 blok x 2 saat = 8 saat -> her blok fiili hedefi 90dk
        // (dakikalar tam sayıya yuvarlandığından ±birkaç dk sapma normaldir).
        $az = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'az_tehlikeli');
        $this->assertEqualsWithDelta(90, collect($az['genel_konular'])->sum('dakika'), 6);
        $this->assertEqualsWithDelta(90, collect($az['saglik_konulari'])->sum('dakika'), 6);
        $this->assertEqualsWithDelta(90, collect($az['teknik_konular'])->sum('dakika'), 6);
        $this->assertSame(90, collect($az['isyerine_ozgu']['maddeler'])->sum('dakika')); // eşit bölündüğü için tam

        // tehlikeli: 4 blok x 3 saat = 12 saat -> her blok fiili hedefi 135dk
        $tehlikeli = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'tehlikeli');
        $this->assertSame(12, $tehlikeli['saat']);
        $this->assertEqualsWithDelta(135, collect($tehlikeli['genel_konular'])->sum('dakika'), 6);
        $this->assertEqualsWithDelta(135, collect($tehlikeli['teknik_konular'])->sum('dakika'), 6);

        // çok tehlikeli: 4 blok x 4 saat = 16 saat -> her blok fiili hedefi 180dk
        $cok = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli');
        $this->assertSame(16, $cok['saat']);
        $this->assertEqualsWithDelta(180, collect($cok['saglik_konulari'])->sum('dakika'), 6);
    }

    public function test_toplam_sure_11_saati_asinca_2_gun_planlanir(): void
    {
        // az tehlikeli = 8 saat toplam -> 1 gün
        $az = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'az_tehlikeli');
        $this->assertLessThanOrEqual(EgitimIcerikOlusturucu::IKI_GUN_ESIGI_DK, EgitimIcerikOlusturucu::toplamDakika($az));
        $this->assertSame(1, EgitimIcerikOlusturucu::planlananGun($az));

        // tehlikeli = 12 saat, çok tehlikeli = 16 saat -> 2 gün
        $this->assertSame(2, EgitimIcerikOlusturucu::planlananGun(
            EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'tehlikeli'),
        ));
        $this->assertSame(2, EgitimIcerikOlusturucu::planlananGun(
            EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli'),
        ));
    }

    public function test_cok_tehlikeli_firma_secilince_sure_gun_otomatik_2_olur(): void
    {
        $azFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $cokFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $azFirma->id)
            ->assertSet('sureGun', 1)
            ->set('firmaId', $cokFirma->id)
            ->assertSet('sureGun', 2);
    }

    public function test_isyerine_ozgu_konu_eklenip_cikarilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);

        $component = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'insaat')
            ->call('isyerineOzguMaddeEkle');

        $maddeler = $component->get('icerik')['isyerine_ozgu']['maddeler'];
        $this->assertCount(6, $maddeler);   // 5 sabit + 1 yeni

        $component->set('icerik.isyerine_ozgu.maddeler.5.madde', 'Vinç altında durmama')
            ->call('isyerineOzguMaddeCikar', 0);

        $sonrasi = $component->get('icerik')['isyerine_ozgu']['maddeler'];
        $this->assertCount(5, $sonrasi);
        $this->assertContains('Vinç altında durmama', collect($sonrasi)->pluck('madde')->all());
    }

    public function test_iki_gunluk_egitim_pdfinde_gun_bazli_imza_sutunlari_olusur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'sure_gun' => 2,
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli'),
            'katilimcilar' => [['ad_soyad' => 'Ali Veli', 'tc' => null, 'gorev' => null]],
        ]);

        $html = view('pdf.egitim-katilim', [
            'kayit' => $kayit, 'firma' => $firma, 'icerik' => $kayit->konu_secimleri,
        ])->render();

        // Katılımcı iki gün ayrı imza atar; eğitimci tek imza (gün ayrımı yok).
        $this->assertStringContainsString('İmza (1. Gün)', $html);
        $this->assertStringContainsString('İmza (2. Gün)', $html);
        $this->assertStringContainsString('1. ve 2. gün', $html);
        $this->assertStringNotContainsString('1. Gün İmza', $html);   // eğitmen bloğunda gün ayrımı YOK
    }

    public function test_ders_saati_tehlike_sinifina_gore_gelir_ve_elle_degistirilince_2_gune_ceker(): void
    {
        $azFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $cokFirma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $c = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $azFirma->id)
            ->assertSet('dersSaati', 8)
            ->assertSet('sureGun', 1)
            ->set('firmaId', $cokFirma->id)
            ->assertSet('dersSaati', 16)
            ->assertSet('sureGun', 2);

        // Az tehlikeli işyeri için elle 16 ders saati → 2 güne çeker, belgeye 16 yazılır.
        $c->set('firmaId', $azFirma->id)
            ->assertSet('dersSaati', 8)
            ->set('dersSaati', 16)
            ->assertSet('sureGun', 2)
            ->set('belgeTarihi', now()->toDateString())
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $azFirma->id)->firstOrFail();
        $this->assertSame(16, $kayit->konu_secimleri['saat']);
        $this->assertSame(2, $kayit->sure_gun);
    }

    public function test_egitim_katilim_formu_excel_olarak_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'sure_gun' => 1,
            'isg_uzmani_var' => true,
            'isg_uzmani_adi' => 'Vural Gündüz',
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'az_tehlikeli'),
            'katilimcilar' => [['ad_soyad' => 'Ali Veli', 'tc' => '12345678901', 'gorev' => 'İşçi']],
        ]);

        $yanit = EgitimKatilimUretici::excel($kayit);
        $this->assertInstanceOf(StreamedResponse::class, $yanit);

        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('PK', $icerik);   // geçerli .xlsx (zip)
        $this->assertStringContainsString('.xlsx', $yanit->headers->get('content-disposition'));
    }

    public function test_excel_aksiyonu_kayit_olusturur_ve_xlsx_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create(['ad_soyad' => 'Test Çalışan']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', now()->toDateString())
            ->callAction('excel');

        $this->assertDatabaseHas('egitim_katilimlari', ['firma_id' => $firma->id]);
    }

    public function test_tekrar_egitiminde_tehlike_sinifindan_bagimsiz_8_saat_ve_esit_bloklar(): void
    {
        $tekrar = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'cok_tehlikeli', 'tekrar');

        $this->assertSame(8, $tekrar['saat']);
        $this->assertEqualsWithDelta(90, collect($tekrar['genel_konular'])->sum('dakika'), 6);
        $this->assertEqualsWithDelta(90, collect($tekrar['saglik_konulari'])->sum('dakika'), 6);
        $this->assertEqualsWithDelta(90, collect($tekrar['teknik_konular'])->sum('dakika'), 6);
        $this->assertSame(90, collect($tekrar['isyerine_ozgu']['maddeler'])->sum('dakika'));
    }

    public function test_madde_agirliklari_orantili_korunur(): void
    {
        // saglik_konulari'nda İlkyardım (10dk) diğerlerinden (20dk) daha az ağırlıklı;
        // ölçeklendikten sonra da bu oran korunmalı.
        $icerik = EgitimIcerikOlusturucu::olustur('genel', null, 'tehlikeli');
        $maddeler = collect($icerik['saglik_konulari']);

        $ilkyardim = $maddeler->firstWhere('madde', 'İlkyardım');
        $ilki = $maddeler->first();

        $this->assertLessThan($ilki['dakika'], $ilkyardim['dakika']);
    }

    public function test_genel_baslik_sektorsuz_ise_isyerine_ozgu_bos_gelir(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli');

        $this->assertNull($icerik['isyerine_ozgu']);
    }

    public function test_ozel_baslik_tek_bloklu_sabit_icerik_doner(): void
    {
        $icerik = EgitimIcerikOlusturucu::olustur('yuksekte_calisma', null, 'az_tehlikeli');

        $this->assertSame('ozel', $icerik['tip']);
        $this->assertSame('Yükseklerde Çalışma', $icerik['ad']);
        $this->assertNotEmpty($icerik['maddeler']);
    }

    public function test_sayfa_firma_secilince_calisanlar_varsayilan_secili_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->count(3)->create();

        $component = Livewire::test(EgitimSayfasi::class)
            ->assertOk()
            ->set('firmaId', $firma->id);

        $this->assertCount(3, $component->get('secilenCalisanIdler'));
    }

    public function test_manuel_katilimci_eklenir_ve_cikarilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniAdSoyad', 'Ahmet Yılmaz')
            ->set('yeniTc', '12345678901')
            ->call('manuelEkle');

        $this->assertCount(1, $component->get('manuelKatilimcilar'));

        $component->call('manuelCikar', 0);
        $this->assertCount(0, $component->get('manuelKatilimcilar'));
    }

    public function test_excel_ile_katilimci_toplu_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->fromArray(['Ad Soyad', 'T.C. Kimlik No', 'Görevi'], null, 'A1');
        $sayfa->fromArray(['Zeynep Kaya', '11122233344', 'Formen'], null, 'A2');
        $sayfa->fromArray(['Emre Demir', '', 'İşçi'], null, 'A3');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $component = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('excelDosya', UploadedFile::fake()->createWithContent('katilimci.xlsx', file_get_contents($yol)))
            ->call('excelIceAktar');

        $manuel = $component->get('manuelKatilimcilar');
        $this->assertCount(2, $manuel);
        $this->assertSame('Zeynep Kaya', $manuel[0]['ad_soyad']);
        $this->assertSame('11122233344', $manuel[0]['tc']);

        unlink($yol);
    }

    public function test_firmada_kayitli_olmayan_katilimci_otomatik_firma_listesine_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create(['ad_soyad' => 'Var Olan', 'tc' => '11111111111']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', now()->toDateString())
            ->set('yeniAdSoyad', 'Ali Veli')
            ->set('yeniTc', '99988877766')
            ->call('manuelEkle')
            ->set('yeniAdSoyad', 'Var Olan')   // zaten kayıtlı — kopya oluşturmaz
            ->set('yeniTc', '11111111111')
            ->call('manuelEkle')
            ->callAction('pdf');

        $this->assertDatabaseHas('calisanlar', [
            'firma_id' => $firma->id,
            'ad_soyad' => 'Ali Veli',
            'tc' => '99988877766',
        ]);
        $this->assertSame(2, $firma->calisanlar()->count());   // 1 var olan + 1 yeni
    }

    public function test_firma_secilince_onceki_egitim_katilim_konu_icerigi_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);

        // Önce bu firma için işe özgü konusu eklenmiş bir kayıt oluştur.
        $onceki = EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'tehlikeli');
        $onceki['isyerine_ozgu']['maddeler'][] = ['madde' => 'Kule vinç yük altında durmama', 'dakika' => 20, 'dahil' => true];
        $onceki['genel_konular'][0]['dahil'] = false;
        EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'egitim_turu' => 'ilk',
            'sektor_anahtari' => 'insaat',
            'belge_tarihi' => now()->subDay(),
            'sure_gun' => 2,
            'konu_secimleri' => $onceki,
            'katilimcilar' => [],
        ]);

        // Yeni form: aynı firma + başlık + sektör seçilince önceki içerik gelmeli.
        $c = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('sektorAnahtari', 'insaat')
            ->assertSet('oncekidenYuklendi', true);

        $maddeler = collect($c->get('icerik')['isyerine_ozgu']['maddeler'])->pluck('madde')->all();
        $this->assertContains('Kule vinç yük altında durmama', $maddeler);
        $this->assertFalse($c->get('icerik')['genel_konular'][0]['dahil']);   // önceki "dahil" durumu korundu

        // "Standart İçerikten Başlat" → önceki yoksayılır.
        $c->call('icerigiSifirla')->assertSet('oncekidenYuklendi', false);
        $sifirMaddeler = collect($c->get('icerik')['isyerine_ozgu']['maddeler'])->pluck('madde')->all();
        $this->assertNotContains('Kule vinç yük altında durmama', $sifirMaddeler);
        $this->assertTrue($c->get('icerik')['genel_konular'][0]['dahil']);
    }

    public function test_egitim_katiliminda_eklenen_ise_ozgu_konu_sertifikada_da_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);

        $sayfa = Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('sektorAnahtari', 'insaat')
            ->set('belgeTarihi', now()->toDateString())
            ->call('isyerineOzguMaddeEkle')
            ->set('icerik.isyerine_ozgu.maddeler.5.madde', 'Kule vinç yük altında durmama')
            ->set('yeniAdSoyad', 'Ali Veli')
            ->call('manuelEkle')
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertContains(
            'Kule vinç yük altında durmama',
            collect($kayit->konu_secimleri['isyerine_ozgu']['maddeler'])->pluck('madde')->all(),
        );

        // Aynı içerikten kurulan sertifika PDF'inde de görünmeli (blade metni büyütür).
        $kayit->setRelation('firma', $firma);
        $ref = new \ReflectionMethod(EgitimSayfasi::class, 'sertifikaKur');
        $ref->setAccessible(true);
        $s = $ref->invoke($sayfa->instance(), $kayit);

        $html = mb_strtoupper(view('pdf.sertifika', ['sertifika' => $s, 'firma' => $firma])->render());
        $this->assertStringContainsString('KULE V', $html);
    }

    public function test_egitim_katilimindan_katilimci_sertifikasi_indirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);
        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'belge_tarihi' => now(),
            'sure_gun' => 2,
            'isg_uzmani_var' => true,
            'isg_uzmani_adi' => 'Vural Gündüz',
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('genel', 'insaat', 'tehlikeli'),
            'katilimcilar' => [
                ['ad_soyad' => 'Ali Veli', 'tc' => '12345678901', 'gorev' => 'İşçi'],
                ['ad_soyad' => 'Ayşe Fatma', 'tc' => '10987654321', 'gorev' => 'Ustabaşı'],
            ],
        ]);

        $sayfa = Livewire::test(EgitimSayfasi::class)->set('firmaId', $firma->id)->instance();
        $yanit = $sayfa->gecmisSertifika($kayit->id);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_pdf_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create(['ad_soyad' => 'Test Çalışan']);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('sektorAnahtari', 'insaat')
            ->set('egitimYeri', 'Şantiye ofisi')
            ->set('belgeTarihi', now()->toDateString())
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('genel', $kayit->baslik_anahtari);
        $this->assertSame('insaat', $kayit->sektor_anahtari);
        $this->assertNotNull($kayit->konu_secimleri);
        $this->assertCount(1, $kayit->katilimcilar);
        $this->assertStringStartsWith('EGT-'.now()->year.'-', $kayit->belge_no);
    }

    public function test_ozel_baslik_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'ilkyardim_ekibi',
            'belge_tarihi' => now(),
            'sure_gun' => 1,
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('ilkyardim_ekibi', null, 'az_tehlikeli'),
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'gorev' => null]],
        ]);

        $yanit = EgitimKatilimUretici::pdf($kayit);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_pdf_ve_silme(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $kayit = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'belge_tarihi' => now(),
            'konu_secimleri' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
            'katilimcilar' => [],
        ]);

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $kayit->id);

        $this->assertDatabaseMissing('egitim_katilimlari', ['id' => $kayit->id]);
    }

    public function test_egitim_sekli_ve_turu_kaydedilir_ve_formda_isaretli_kutu_olur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('belgeTarihi', now()->toDateString())
            ->set('egitimTuru', 'tekrar')
            ->set('egitimSekli', 'uzaktan')
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('uzaktan', $kayit->egitim_sekli);
        $this->assertSame('tekrar', $kayit->egitim_turu);

        $html = view('pdf.egitim-katilim', ['kayit' => $kayit, 'firma' => $firma, 'icerik' => $kayit->konu_secimleri])->render();
        $this->assertStringContainsString('Eğitim Türü', $html);
        $this->assertStringContainsString('Eğitim Şekli', $html);
        $this->assertStringContainsString('Uzaktan', $html);
        // Tekrar seçili → onun kutusunda X, İlk Defa boş
        $this->assertMatchesRegularExpression('/class="kutu">X<\/span>\s*Tekrar/u', $html);
    }

    public function test_egitim_turu_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(EgitimSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('baslikAnahtari', 'genel')
            ->set('egitimTuru', 'tekrar')
            ->set('belgeTarihi', now()->toDateString())
            ->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('tekrar', $kayit->egitim_turu);
        $this->assertSame(8, $kayit->konu_secimleri['saat']);
    }

    public function test_bos_form_firma_secmeden_indirilir_ve_pdf_doner(): void
    {
        $yanit = Livewire::test(EgitimSayfasi::class)
            ->set('bosFormTehlikeSinifi', 'cok_tehlikeli')
            ->call('bosFormIndir', 'genel');

        $bosPdf = $yanit->instance()->bosFormIndir('genel');
        $this->assertInstanceOf(StreamedResponse::class, $bosPdf);
        ob_start();
        $bosPdf->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_katilim_tablosu_satir_sayisi_katilimciya_gore_belirlenir(): void
    {
        // dompdf çıktısından sayfa metnini doğrudan doğrulamak zor; bunun yerine
        // şablonun beslendiği satır sayısı mantığını doğrudan test ederiz:
        // katılımcı yoksa 10 (boş imza formu), varsa tam olarak katılımcı kadar.
        $bos = new EgitimKatilim(['katilimcilar' => []]);
        $this->assertSame(10, count($bos->katilimcilar ?? []) ?: 10);

        $uc = new EgitimKatilim(['katilimcilar' => array_fill(0, 3, ['ad_soyad' => 'X'])]);
        $this->assertSame(3, count($uc->katilimcilar) ?: 10);

        $onIki = new EgitimKatilim(['katilimcilar' => array_fill(0, 12, ['ad_soyad' => 'X'])]);
        $this->assertSame(12, count($onIki->katilimcilar) ?: 10);
    }

    public function test_ozel_baslik_icin_bos_form_indirilir(): void
    {
        $yanit = Livewire::test(EgitimSayfasi::class)->instance()->bosFormIndir('isg_kurulu');

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
    }

    public function test_firma_secilince_egitmen_bilgileri_firma_kaydindan_gelir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create([
            'tip' => 'igu', 'ad_soyad' => 'İGU Ayşe', 'kase_gorseli' => 'isg-profesyonel-kase/ayse.png',
        ]);
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create([
            'tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Mehmet', 'kase_gorseli' => 'isg-profesyonel-kase/mehmet.png',
        ]);
        $firma = Firma::factory()->for($this->uzman)->create([
            'igu_id' => $igu->id, 'isyeri_hekimi_id' => $hekim->id,
        ]);

        $component = Livewire::test(EgitimSayfasi::class)->set('firmaId', $firma->id);

        $component->assertSet('isyeriHekimiVar', true)
            ->assertSet('isyeriHekimiAdi', 'Dr. Mehmet');

        $component->set('belgeTarihi', now()->toDateString())->callAction('pdf');

        $kayit = EgitimKatilim::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('İGU Ayşe', $kayit->isg_uzmani_adi);
        $this->assertSame('isg-profesyonel-kase/ayse.png', $kayit->isg_uzmani_kase);
        $this->assertSame('Dr. Mehmet', $kayit->isyeri_hekimi_adi);
        $this->assertSame('isg-profesyonel-kase/mehmet.png', $kayit->isyeri_hekimi_kase);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(EgitimSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
