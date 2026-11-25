<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\Araclar;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\TespitOneriDefteri;
use App\Filament\Pages\ZiyaretProgrami;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\IsgDenemeSinaviKutuphanesi;
use App\Support\IsverenFirmaKarnesiUretici;
use App\Support\IsverenWhatsApp;
use App\Support\SayfaRehberi;
use App\Support\ZiyaretOtomatikPlanlayici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PaketAltiOzelliklerTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek Metal A.Ş.',
            'tehlike_sinifi' => 'tehlikeli',
            'calisan_sayisi' => 30,
            'aktif' => true,
        ]);
    }

    public function test_ziyaret_otomatik_planlayici_mesgul_gunleri_atlar_ve_gunluk_kapasiteyi_uygular(): void
    {
        // 30 çalışan × 20 dk = 600 dk = 10 saat → günde max 5 saat ile 2 güne bölünmeli.
        $mesgul = ['2026-11-02', '2026-11-03', '2026-11-04'];
        ZiyaretOtomatikPlanlayici::calistir(2026, Carbon::create(2026, 11, 1), false, $mesgul, 5.0);

        $kasim = ZiyaretProgramiModel::where('firma_id', $this->firma->id)->where('yil', 2026)->first()->ziyaretler[10];

        $this->assertCount(2, $kasim);
        foreach ($kasim as $girdi) {
            $this->assertNotContains($girdi['tarih'], $mesgul);
            $this->assertLessThanOrEqual(5.0, (float) $girdi['sure_saat']);
        }
    }

    public function test_sesle_yaz_bileseni_saha_ve_dof_sayfalarinda_gorunur(): void
    {
        Livewire::test(HizliSahaBulgusu::class)->assertSee('Sesle Yaz');
        Livewire::test(DofOlustur::class)->set('firmaId', $this->firma->id)->assertSee('Sesle Yaz');
        Livewire::test(TespitOneriDefteri::class)->set('firmaId', $this->firma->id)->assertSee('Sesle Yaz');
        Livewire::test(AiSahaAnalizi::class)->set('firmaId', $this->firma->id)->assertSee('Sesle Yaz');
    }

    public function test_isg_uzmanlik_deneme_sinavi_ve_hap_notlar_pdf_calisir(): void
    {
        $sorular = IsgDenemeSinaviKutuphanesi::sorular();
        $this->assertGreaterThanOrEqual(10, count($sorular));

        Livewire::test(Araclar::class)
            ->call('denemeSikSec', 0, $sorular[0]['dogru'])
            ->call('denemeKontrolEt')
            ->assertSet('denemeTamamlandi', true)
            ->call('denemeSifirla')
            ->assertSet('denemeTamamlandi', false)
            ->assertSet('denemeCevaplari', []);

        $yanit = IsgDenemeSinaviKutuphanesi::hapNotlarPdf();
        $this->assertSame(200, $yanit->getStatusCode());
    }

    public function test_isveren_salt_okunur_firma_karnesi_pdf_ve_imzali_paylasim_calisir(): void
    {
        $pdf = IsverenFirmaKarnesiUretici::pdf($this->firma);
        $this->assertSame(200, $pdf->getStatusCode());

        $mesaj = IsverenWhatsApp::firmaKarnesiMesaji($this->firma);
        $this->assertStringContainsString('İşveren İSG Uyum Karneniz', $mesaj);
        $this->assertStringContainsString('/paylasim/firma-karnesi/'.$this->firma->id, $mesaj);

        // İmzasız erişim reddedilir (403)
        auth()->logout();
        $this->get('/paylasim/firma-karnesi/'.$this->firma->id)->assertStatus(403);

        // 30 günlük imzalı bağlantı oturum açmadan PDF döner
        $imzaliUrl = IsverenWhatsApp::pdfBaglantisi('firma-karnesi', $this->firma->id);
        $this->get($imzaliUrl)->assertOk();
    }

    public function test_kontrol_merkezi_hizli_evrak_matrisinde_isveren_karnesi_butonlari_gorunur(): void
    {
        Livewire::test(KontrolMerkezi::class)
            ->set('firmaId', $this->firma->id)
            ->set('sekme', 'hizli_evrak')
            ->assertSee('İşveren Karnesi PDF')
            ->assertSee('İşveren Linki Kopyala');
    }

    public function test_nasil_kullanilir_rehber_kutusu_ana_modullerde_gorunur(): void
    {
        $rehber = SayfaRehberi::bul([KontrolMerkezi::class]);
        $this->assertNotNull($rehber);
        $this->assertCount(3, $rehber['adimlar']);

        $this->get(KontrolMerkezi::getUrl())
            ->assertOk()
            ->assertSee('Nasıl Kullanılır?')
            ->assertSee('Hızlı Kullanım Rehberi');

        $this->get(ZiyaretProgrami::getUrl())
            ->assertOk()
            ->assertSee('Nasıl Kullanılır?');
    }

    public function test_egitim_ve_sinav_sayfalarinda_detayli_rehber_ve_yonlendirmeler_calisir(): void
    {
        $sayfalar = [
            \App\Filament\Pages\EgitimKatilim::class,
            \App\Filament\Pages\IsbasiEgitim::class,
            \App\Filament\Pages\EgitimYenilemeTakibi::class,
            \App\Filament\Pages\SertifikaOlustur::class,
            \App\Filament\Resources\EgitimPaketis\EgitimPaketiResource::class,
            \App\Filament\Pages\UzaktanEgitimFirmaAyarlari::class,
            \App\Filament\Pages\UzaktanEgitimAtama::class,
            \App\Filament\Pages\UzaktanEgitimTakip::class,
            \App\Filament\Pages\UzaktanEgitimYilBasi::class,
            \App\Filament\Pages\UzaktanEgitimSorulari::class,
            \App\Filament\Pages\SoruBankasi::class,
            \App\Filament\Pages\EgitimSorulari::class,
            \App\Filament\Pages\ToolboxKonusmalari::class,
            \App\Filament\Pages\IseOzguEgitimKonulari::class,
            \App\Filament\Pages\YillikPlan\YillikEgitimPlani::class,
        ];

        foreach ($sayfalar as $sinif) {
            $rehber = SayfaRehberi::bul([$sinif]);
            $this->assertNotNull($rehber, $sinif.' için Nasıl Kullanılır rehberi tanımlı olmalı');
            $this->assertNotEmpty(SayfaRehberi::yonlendirmeler($rehber, [$sinif]), $sinif.' için yönlendirme bağlantıları olmalı');
        }

        // Eğitim Katılım sayfasında detaylı bölümler, yasal süre tablosu ve firma parametreli yönlendirme linkleri basılır
        $this->get(\App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $this->firma->id]))
            ->assertOk()
            ->assertSee('Nasıl Kullanılır?')
            ->assertSee('Ekranlara Yönlendirme')
            ->assertSee('3 Ayrı Eğitim Katılım Formu Nasıl Kullanılır?')
            ->assertSee('Kalıp Ekibi ile Sıva Ekibine Ayrı Eğitim Verme')
            ->assertSee('Yasal Süre & Karma Dağılım Tablosu', false)
            ->assertSee('firma='.$this->firma->id, false);

        // İşe Özgü Eğitim Konuları ve Yıllık Eğitim Planı sayfalarında da görünür
        $this->get(\App\Filament\Pages\IseOzguEgitimKonulari::getUrl(['firma' => $this->firma->id]))
            ->assertOk()
            ->assertSee('Nasıl Kullanılır?')
            ->assertSee('Kütüphane Diğer Ekranlarla Nasıl Senkron Çalışır?');

        $this->get(\App\Filament\Pages\YillikPlan\YillikEgitimPlani::getUrl(['firma' => $this->firma->id]))
            ->assertOk()
            ->assertSee('Nasıl Kullanılır?')
            ->assertSee('RG 02.04.2026 Mevzuat Kilidi');
    }
}
