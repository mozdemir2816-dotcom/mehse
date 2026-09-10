<?php

namespace Tests\Feature;

use App\Filament\Pages\SahaDenetimi as SahaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaDenetimi;
use App\Models\SahaDenetimiOzelMadde;
use App\Models\User;
use App\Support\SahaDenetimiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SahaDenetimiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_kontrol_listesi_41_madde_icerir(): void
    {
        $toplam = collect(config('isg.saha_denetimi.kategoriler'))->sum(fn ($k) => count($k['maddeler']));

        $this->assertSame(41, $toplam);
    }

    public function test_firma_secilince_denetci_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'unvan' => 'Test A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('denetciAdi'));
    }

    public function test_firma_placeholder_ifadede_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'ACME İnşaat A.Ş.']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $component->assertSee('ACME İnşaat A.Ş. etiketiyle tanımlanmış mı?');
    }

    public function test_cevap_verilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun');

        $this->assertSame('uygun', $component->get('cevaplar')['1_1']['sonuc']);
    }

    public function test_uygun_degil_icin_aciklama_zorunlu(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun_degil')
            ->callAction('pdf');

        $this->assertDatabaseCount('saha_denetimleri', 0);
    }

    public function test_ekip_firma_calisanindan_hizli_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('ekipHizliEkle', $c->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('ekipUyeleri')[0]['ad_soyad']);
    }

    public function test_pdf_aksiyonu_kayit_olusturur_uygunluk_hesaplar_ve_kase_snapshotlanir(): void
    {
        Storage::fake('public');
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->call('cevapVer', '1.2', 'uygun')
            ->call('cevapVer', '1.3', 'uygun_degil')
            ->set('cevaplar.1_3.aciklama', 'Eğitim eksik')
            ->set('fotoYuklemeleri.1_3', UploadedFile::fake()->image('kanit.jpg'))
            ->call('cevapVer', '1.4', 'uygulanamaz')
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame(1, $d->revizyon);
        $this->assertSame('isg-profesyonel-kase/x.png', $d->denetci_kase);
        $this->assertTrue($d->kritik_uygunsuzluk_var);
        $this->assertEqualsWithDelta(66.67, $d->uygunluk_yuzdesi, 0.01);

        $madde13 = collect($d->cevaplar)->firstWhere('kod', '1.3');
        $this->assertSame('Eğitim eksik', $madde13['aciklama']);
        $this->assertNotNull($madde13['foto_yolu']);
        Storage::disk('public')->assertExists($madde13['foto_yolu']);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Test?', 'kritik' => true, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $yanit = SahaDenetimiUretici::pdf($d);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_raporunda_uygulanamaz_maddeler_listelenmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Görünmesi gereken madde', 'kritik' => true, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
                ['kategori_ad' => 'Yangın', 'kod' => '1.4', 'ifade' => 'Gizlenmesi gereken uygulanamaz madde', 'kritik' => false, 'sonuc' => 'uygulanamaz', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();

        $this->assertStringContainsString('Görünmesi gereken madde', $html);
        $this->assertStringNotContainsString('Gizlenmesi gereken uygulanamaz madde', $html);
    }

    public function test_ozel_madde_eklenir_ve_secili_sektorde_kontrol_listesine_dahil_olur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniOzelSektorAnahtari', 'insaat')
            ->set('yeniOzelKategoriAdi', 'Kazı Kontrolü')
            ->set('yeniOzelIfade', 'Kazı şevi/iksa sistemi güvenli mi?')
            ->set('yeniOzelKritik', true)
            ->call('ozelMaddeEkle');

        $this->assertDatabaseHas('saha_denetimi_ozel_maddeleri', [
            'user_id' => $this->uzman->id,
            'sektor_anahtari' => 'insaat',
            'kategori_ad' => 'Kazı Kontrolü',
        ]);

        // Sektör henüz seçilmediği için kontrol listesinde (kategoriler) görünmemeli
        // (özel madde yönetim tablosunda hâlâ görünür — o listede sektör filtresi yok).
        $iceriyorMu = fn ($kategoriler) => collect($kategoriler)->contains(fn ($k) => $k['ad'] === 'Kazı Kontrolü');
        $this->assertFalse($iceriyorMu($component->get('kategoriler')));

        // Sektör "İnşaat" seçilince kontrol listesine dahil olmalı.
        $component->set('sektorAnahtari', 'insaat');
        $this->assertTrue($iceriyorMu($component->get('kategoriler')));
    }

    public function test_sektorsuz_ozel_madde_her_sektorde_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        SahaDenetimiOzelMadde::create([
            'user_id' => $this->uzman->id,
            'sektor_anahtari' => null,
            'kategori_ad' => 'Genel Ek Kontrol',
            'ifade' => 'Herkes için görünen madde',
        ]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'maden')
            ->assertSee('Herkes için görünen madde');
    }

    public function test_ozel_madde_mevcut_kategoriye_eklenince_birlesir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniOzelKategoriAdi', 'Yüksekte Çalışma')
            ->set('yeniOzelIfade', 'Kat kenarları korkuluklu mu?')
            ->call('ozelMaddeEkle');

        $kategoriler = $component->get('kategoriler');
        $bulunduMu = collect($kategoriler)->contains(fn ($k) => $k['ad'] === 'Yüksekte Çalışma'
            && collect($k['maddeler'])->contains(fn ($m) => $m['ifade'] === 'Kat kenarları korkuluklu mu?'));

        $this->assertTrue($bulunduMu);
        // Aynı kategori tekrar oluşturulmamalı (tek "Yüksekte Çalışma" girdisi kalmalı).
        $this->assertCount(1, collect($kategoriler)->where('ad', 'Yüksekte Çalışma'));
    }

    public function test_ozel_madde_silinir(): void
    {
        $madde = SahaDenetimiOzelMadde::create([
            'user_id' => $this->uzman->id,
            'kategori_ad' => 'Test Başlık',
            'ifade' => 'Test madde',
        ]);

        Livewire::test(SahaSayfasi::class)->call('ozelMaddeSil', $madde->id);

        $this->assertDatabaseMissing('saha_denetimi_ozel_maddeleri', ['id' => $madde->id]);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create(['firma_id' => $firma->id, 'revizyon' => 1]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $d->id);

        $this->assertDatabaseMissing('saha_denetimleri', ['id' => $d->id]);
    }

    public function test_taslak_kaydedilir_ve_gecmise_karismaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('santiyeAdi', 'Yarım Kalan Şantiye')
            ->call('cevapVer', '1.1', 'uygun')
            ->call('taslakKaydet');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $taslak = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('taslak', $taslak->durum);
        $this->assertSame('Yarım Kalan Şantiye', $taslak->santiye_adi);

        // Taslak, "geçmiş kayıtlar" (tamamlanmış) listesine dahil edilmemeli.
        $sayfa = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);
        $this->assertCount(0, $sayfa->instance()->gecmisKayitlar());
    }

    public function test_ikinci_taslak_kaydi_oncekini_gunceller_yeni_satir_acmaz(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('santiyeAdi', 'İlk Hal')
            ->call('taslakKaydet');

        $component->set('santiyeAdi', 'Güncellenmiş Hal')->call('taslakKaydet');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $this->assertSame('Güncellenmiş Hal', SahaDenetimi::where('firma_id', $firma->id)->firstOrFail()->santiye_adi);
    }

    public function test_firma_secilince_taslak_otomatik_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        SahaDenetimi::create([
            'firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0,
            'santiye_adi' => 'Kaydedilmiş Şantiye', 'ekip_uyeleri' => [], 'cevaplar' => [
                ['kategori_ad' => 'x', 'kod' => '1.1', 'ifade' => 'y', 'kritik' => false, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => null],
            ],
        ]);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertTrue($component->get('taslakYuklendi'));
        $this->assertSame('Kaydedilmiş Şantiye', $component->get('santiyeAdi'));
        $this->assertSame('uygun', $component->get('cevaplar')['1_1']['sonuc']);
    }

    public function test_taslak_temizle_kaydi_siler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $taslak = SahaDenetimi::create(['firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('taslakTemizle');

        $this->assertDatabaseMissing('saha_denetimleri', ['id' => $taslak->id]);
    }

    public function test_denetimi_tamamlayinca_bekleyen_taslak_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        SahaDenetimi::create(['firma_id' => $firma->id, 'durum' => 'taslak', 'revizyon' => 0]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->callAction('pdf');

        $this->assertDatabaseCount('saha_denetimleri', 1);
        $this->assertSame('tamamlandi', SahaDenetimi::where('firma_id', $firma->id)->firstOrFail()->durum);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SahaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_uygun_maddeye_de_fotograf_eklenebilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('cevapVer', '1.1', 'uygun')
            ->set('fotoYuklemeleri.1_1', UploadedFile::fake()->image('uygun-kanit.jpg'))
            ->callAction('pdf');

        $d = SahaDenetimi::where('firma_id', $firma->id)->firstOrFail();
        $madde11 = collect($d->cevaplar)->firstWhere('kod', '1.1');

        $this->assertSame('uygun', $madde11['sonuc']);
        $this->assertNotNull($madde11['foto_yolu']);
        Storage::disk('public')->assertExists($madde11['foto_yolu']);
    }

    public function test_foto_kaldir_yuklemeyi_ve_taslak_yolunu_temizler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('cevaplar.1_1.foto_yolu', 'saha-denetimi-foto/eski.jpg')
            ->set('fotoYuklemeleri.1_1', UploadedFile::fake()->image('yeni.jpg'))
            ->call('fotoKaldir', '1.1');

        $this->assertArrayNotHasKey('1_1', $component->get('fotoYuklemeleri'));
        $this->assertNull($component->get('cevaplar')['1_1']['foto_yolu']);
    }

    public function test_pdf_madde_tablosunda_satir_ici_foto_gosterilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $d = SahaDenetimi::create([
            'firma_id' => $firma->id,
            'revizyon' => 1,
            'cevaplar' => [
                ['kategori_ad' => 'Yangın', 'kod' => '1.1', 'ifade' => 'Test?', 'kritik' => false, 'sonuc' => 'uygun', 'aciklama' => null, 'foto_yolu' => 'saha-denetimi-foto/kanit.jpg'],
            ],
        ]);

        $html = view('pdf.saha-denetimi', ['denetim' => $d, 'firma' => $firma])->render();

        $this->assertStringContainsString('class="satir-foto"', $html);
        $this->assertStringContainsString('saha-denetimi-foto/kanit.jpg', $html);
    }
}
