<?php

namespace Tests\Feature;

use App\Filament\Pages\Ayarlar;
use App\Filament\Pages\CariHesap;
use App\Filament\Pages\CezaTeblig;
use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\EReetem;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\MuayeneFormu;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\Profilim;
use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Pages\SahaDenetimi;
use App\Filament\Pages\SertifikaOlustur;
use App\Filament\Pages\ZiyaretProgrami;
use App\Models\CanliImza;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\SahaBulgusu;
use App\Models\TeftisPaylasimi;
use App\Models\User;
use App\Support\CanliImzaYonetici;
use App\Support\EkipmanPasaportUretici;
use App\Support\FirmaRiskBildirimQrUretici;
use App\Support\HaritaRotaPlanlayici;
use App\Support\SmsGonderici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MehseIyilestirmelerTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip', 'name' => 'Mehmet Özdemir']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'FOM MAKİNA__OTOMOTİV A.Ş. A.Ş.',
            'aktif' => true,
            'adres' => 'NOSAB Nilüfer Bursa',
            'calisan_sayisi' => 30,
        ]);
    }

    public function test_a2_hizli_saha_bulgusu_sayfalama_sinirlar_ve_artirir(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            SahaBulgusu::create([
                'firma_id' => $this->firma->id,
                'uygunsuzluk' => "Test bulgu #{$i}",
                'durum' => 'acik',
                'olasilik' => 3,
                'siddet' => 3,
            ]);
        }

        $sayfa = Livewire::test(HizliSahaBulgusu::class);
        $this->assertSame(30, $sayfa->instance()->bulguToplamSayisi);
        $this->assertCount(25, $sayfa->instance()->bulgular);

        $sayfa->call('dahaFazlaGoster');
        $this->assertCount(30, $sayfa->instance()->bulgular);
    }

    public function test_a3_arsiv_yalniz_aktif_firmalari_sayar(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Pasif Test Firması', 'aktif' => false]);

        $sayfa = Livewire::test(DokumanYonetimi::class);
        $this->assertCount(1, $sayfa->instance()->kapsamFirmalari);
    }

    public function test_a5_erecete_gercek_hekim_atamasi_kontrol_eder(): void
    {
        Livewire::test(EReetem::class)
            ->call('isyeriHekimiHatirlat')
            ->assertNotified('İşyeri Hekimi Atama Durumu: 0/1 Firma');
    }

    public function test_a8_firma_unvan_temizligi_ve_artisan_komutu(): void
    {
        $this->assertSame('FOM MAKİNA — OTOMOTİV A.Ş.', $this->firma->secimEtiketi());

        $this->artisan('mehse:veri-temizle')->assertSuccessful();
        $this->artisan('mehse:veri-temizle', ['--apply' => true])->assertSuccessful();
        $this->assertSame('FOM MAKİNA — OTOMOTİV A.Ş.', $this->firma->fresh()->unvan);
    }

    public function test_c15_canli_imza_olusturulur_imzalanir_ve_dogrulanir(): void
    {
        Storage::fake('public');

        $imza = CanliImzaYonetici::oturumOlustur($this->uzman, $this->firma, 'egitim_katilim', null, 'Temel İSG Eğitimi');
        $this->get(route('canli-imza.form', ['token' => $imza->imza_token]))
            ->assertOk()
            ->assertSee('Temel İSG Eğitimi');

        // 1x1 şeffaf PNG değil, doğrulamadan geçecek boyutta sahte PNG gövdesi
        $png = 'data:image/png;base64,'.base64_encode(str_repeat('A', 300));
        $this->post(route('canli-imza.kaydet', ['token' => $imza->imza_token]), [
            'calisan_adi' => 'Ali Yılmaz',
            'calisan_tc' => '12345678901',
            'imza_veri' => $png,
            'onay' => '1',
        ])->assertRedirect();

        $imza->refresh();
        $this->assertTrue($imza->imzalandiMi());
        $this->assertNotNull($imza->dogrulama_kodu);

        $this->get(route('canli-imza.dogrula', ['kod' => $imza->dogrulama_kodu]))
            ->assertOk()
            ->assertSee('Ali Yılmaz')
            ->assertSee('123*****901');
    }

    public function test_c16_public_qr_risk_bildirimi_giris_olmadan_bulgu_olusturur(): void
    {
        auth()->logout();
        $url = FirmaRiskBildirimQrUretici::bildirimAdresi($this->firma);

        $this->get($url)->assertOk()->assertSee('Tehlike / Ramak Kala Bildir');

        $this->post($url, [
            'tur' => 'ramak_kala',
            'bolum' => 'Pres Hattı',
            'uygunsuzluk' => 'Koruyucu sensör gevşemiş, acil müdahale gerekiyor.',
            'aciliyet' => 'kritik',
            'isimsiz' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('saha_bulgulari', [
            'firma_id' => $this->firma->id,
            'bolum' => 'Pres Hattı',
            'kaynak' => 'qr',
        ]);
    }

    public function test_c18_harita_rota_planlayici_ve_gorunum(): void
    {
        $k = HaritaRotaPlanlayici::firmaKoordinati($this->firma);
        $this->assertSame('NOSAB', $k['bolge']);

        $sayfa = Livewire::test(ZiyaretProgrami::class)->call('gorunumSec', 'harita');
        $this->assertSame('harita', $sayfa->get('gorunum'));
        $this->assertNotEmpty($sayfa->instance()->haritaRotasi['duraklar']);
    }

    public function test_c20_saha_denetimi_uygunsuzluklari_dof_ve_riske_aktarir(): void
    {
        $sayfa = Livewire::test(SahaDenetimi::class, ['firmaId' => $this->firma->id])
            ->set('cevaplar', [
                'm1' => ['sonuc' => 'uygun_degil', 'ifade' => 'Yangın söndürücü önü kapalı', 'kritik' => true, 'aciklama' => 'Palet konulmuş'],
            ]);

        $sayfa->call('uygunsuzluklariDofeAktar');
        $this->assertDatabaseCount('dof_raporlari', 1);

        $sayfa->call('uygunsuzluklariRiskeAktar');
        $this->assertDatabaseCount('risk_degerlendirmeleri', 1);
        $this->assertDatabaseCount('risk_maddeleri', 1);
    }

    public function test_c21_mufettis_salt_okunur_teftis_linki_ve_erisim_logu(): void
    {
        $paylasim = TeftisPaylasimi::create([
            'user_id' => $this->uzman->id,
            'firma_id' => $this->firma->id,
            'token' => str_repeat('a', 32),
            'baslik' => 'ÇSGB Teftiş Dosyası',
            'bitis_tarihi' => now()->addDays(5),
            'aktif' => true,
        ]);

        auth()->logout();
        $this->get(route('mufettis.teftis', ['token' => $paylasim->token]))
            ->assertOk()
            ->assertSee('SALT-OKUNUR TEFTİŞ ERİŞİMİ');

        $this->assertSame(1, $paylasim->fresh()->erisim_sayisi);
    }

    public function test_c22_c23_mikro_kisayollar_ve_ekipnet_dogrulama(): void
    {
        $this->assertTrue(EkipmanPasaportUretici::ekipnetNoGecerliMi('K12345678'));
        $this->assertFalse(EkipmanPasaportUretici::ekipnetNoGecerliMi('abc'));

        Livewire::test(MuayeneFormu::class, ['firmaId' => $this->firma->id])
            ->callAction('tumuNormalDoldur')
            ->assertSet('sonucKanaati', 'uygun');

        Livewire::test(CezaTeblig::class, ['firmaId' => $this->firma->id])
            ->callAction('imzadanImtina')
            ->assertSet('imzaDurumu', 'imtina_etti');

        $ekipman = IsEkipmani::create([
            'firma_id' => $this->firma->id,
            'ekipman_adi' => 'Kompresör 1',
            'kategori' => 'basinc_kap',
        ]);
        $this->assertNotNull(EkipmanPasaportUretici::pdf($ekipman, 'K12345678'));
    }

    public function test_d25_d26_d27_d28_fiyatlandirma_ucretsiz_araclar_sms_ve_osgb(): void
    {
        auth()->logout();
        $this->get(route('fiyatlandirma'))->assertOk()->assertSee('7 Gün Ücretsiz Başla');
        $this->get(route('araclar.tehlike'))->assertOk()->assertSee('Tehlike Sınıfı Sorgulama');
        $this->get(route('araclar.sure'))->assertOk()->assertSee('6331 İGU');
        $this->get(route('araclar.kso'))->assertOk()->assertSee('Kaza Sıklık Oranı');
        $this->get('/sitemap.xml')->assertOk()->assertSee('/fiyatlandirma');

        $sms = SmsGonderici::gonder($this->uzman, '0506 475 48 55', 'Deneme');
        $this->assertTrue($sms['basarili']);

        $this->actingAs($this->uzman);
        Livewire::test(CariHesap::class)->callAction('osgbSubeler', ['aktif_sube' => 'Bursa Nilüfer Şube', 'yeni_sube_il' => 'Bursa']);
        $this->assertSame('Bursa Nilüfer Şube', ($this->uzman->fresh()->ayarlar ?? [])['aktif_sube']);
    }
}
