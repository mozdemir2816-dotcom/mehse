<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\OnBilgilendirmeler;
use App\Filament\Pages\SahaDenetimi;
use App\Filament\Pages\TespitOneriDefteri;
use App\Filament\Pages\ZiyaretModu;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\KomutaFirma;
use App\Support\SayfaAileleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** Saha Yönetim & Denetim (29.10.2026): 7 sayfa 3 ana sekmede, ortak firma bağlamı ve otomatik firma bilgi çubuğu. */
class SahaModuluTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['name' => 'Mehmet Özdemir', 'sertifika_no' => '405044']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Beta Yapı', 'nace_kodu' => '41.20.01', 'tehlike_sinifi' => 'cok_tehlikeli', 'isveren_vekili' => 'Yasin Şener',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_yedi_sayfa_uc_ana_sekmede_toplanir(): void
    {
        $g = SayfaAileleri::sekmeGruplari([ZiyaretModu::class]);

        $this->assertSame(
            ['Aktif Saha Denetimi & Ziyaret Modu', 'Bulgu & DÖF Takip Havuzu', 'Yasal Rapor & Defter Çıktıları'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame([true, false, false], array_column($g['ust'], 'aktif'));
        $this->assertSame(['Bugün (Ziyaret Modu)', 'Bulgular', 'Saha Denetimi', 'Kontrol Kartı'], array_column($g['alt'], 'etiket'));

        // Tek sayfalı grup alt şerit göstermez; defter grubunda üç sayfa.
        $this->assertSame([], SayfaAileleri::sekmeGruplari([DofOlustur::class])['alt']);
        $defter = SayfaAileleri::sekmeGruplari([AiSahaAnalizi::class]);
        $this->assertSame([false, false, true], array_column($defter['ust'], 'aktif'));
        $this->assertCount(3, $defter['alt']);

        foreach ([HizliSahaBulgusu::class, SahaDenetimi::class, TespitOneriDefteri::class, OnBilgilendirmeler::class] as $sinif) {
            $this->assertNotNull(SayfaAileleri::sekmeGruplari([$sinif]), $sinif);
        }

        // Saha ailesinde olmayan sayfa düz sekme şeridini kullanır.
        $this->assertNull(SayfaAileleri::sekmeGruplari([\App\Filament\Pages\ZiyaretProgrami::class]));
    }

    public function test_firma_secimi_saha_sayfalari_arasinda_paylasilir(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Alfa']);

        Livewire::test(HizliSahaBulgusu::class)->set('firmaId', $diger->id);
        $this->assertSame($diger->id, KomutaFirma::oturumdaki());

        Livewire::test(DofOlustur::class)->assertSet('firmaId', $diger->id);
        Livewire::test(SahaDenetimi::class)->assertSet('firmaId', $diger->id);
        Livewire::test(AiSahaAnalizi::class)->assertSet('firmaId', $diger->id);
        Livewire::test(TespitOneriDefteri::class)->assertSet('firmaId', $diger->id);
        Livewire::test(OnBilgilendirmeler::class)->assertSet('firmaId', $diger->id);

        // Başka sayfada firma değişince ortak bağlam da değişir.
        Livewire::test(DofOlustur::class)->set('firmaId', $this->firma->id);
        Livewire::test(TespitOneriDefteri::class)->assertSet('firmaId', $this->firma->id);
    }

    public function test_adresteki_firma_oturumdaki_secimi_ezer(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create();
        KomutaFirma::sec($diger->id);

        $this->get(DofOlustur::getUrl(['firma' => $this->firma->id]))->assertOk();
        $this->assertSame($this->firma->id, KomutaFirma::oturumdaki());
    }

    public function test_firma_bilgi_cubugu_uzman_sertifika_nace_ve_sorumluyu_otomatik_getirir(): void
    {
        KomutaFirma::sec($this->firma->id);

        $this->get(DofOlustur::getUrl())->assertOk()
            ->assertSee('Beta Yapı')
            ->assertSee('41.20.01')
            ->assertSee('Mehmet Özdemir')
            ->assertSee('405044')
            ->assertSee('Yasin Şener')
            ->assertSee('Bulgu & DÖF Takip Havuzu');

        KomutaFirma::sec(null);
        $this->get(DofOlustur::getUrl())->assertOk()->assertSee('Firma seçilmedi');
    }

    public function test_adresten_gelen_firma_tum_saha_sayfalarinda_ortak_baglama_yazilir(): void
    {
        foreach ([HizliSahaBulgusu::class, OnBilgilendirmeler::class, DofOlustur::class, TespitOneriDefteri::class, SahaDenetimi::class, AiSahaAnalizi::class] as $sinif) {
            KomutaFirma::sec(null);

            $this->get($sinif::getUrl(['firma' => $this->firma->id]))->assertOk();

            $this->assertSame($this->firma->id, KomutaFirma::oturumdaki(), $sinif);
        }
    }

    public function test_baska_uzmanin_firmasi_baglamda_gosterilmez(): void
    {
        $yabanci = Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı Firma']);
        KomutaFirma::sec($yabanci->id);

        // Bilgi çubuğu yalnız kullanıcının kendi firmasını gösterir (sahip rolündeki kullanıcının sayfa listesi geniştir).
        $this->get(DofOlustur::getUrl())->assertOk()->assertSee('Firma seçilmedi')->assertDontSee('mehse-saha-baglam-liste', false);
    }
    private function planKur(): void
    {
        Carbon::setTestNow('2026-10-15 10:00');

        YillikPlan::create(['firma_id' => $this->firma->id, 'yil' => 2026, 'egitimler' => [], 'faaliyetler' => [
            ['faaliyet' => 'Risk değerlendirmesi gözden geçirilmesi', 'ana_konu' => 'RİSK YÖNETİMİ', 'aylar' => [9 => 'planlandi']],
            ['faaliyet' => 'İskele kontrolü', 'ana_konu' => 'İŞ EKİPMANLARI', 'aylar' => [9 => 'planlandi']],
            ['faaliyet' => 'Yangın tüpü kontrolü', 'ana_konu' => 'ACİL DURUM', 'aylar' => [9 => 'planlandi']],
            ['faaliyet' => 'KKD zimmet kontrolü', 'ana_konu' => 'KKD', 'aylar' => [9 => 'planlandi']],
            ['faaliyet' => 'Ortam aydınlatma', 'ana_konu' => 'İŞYERİ DÜZENİ', 'aylar' => [9 => 'planlandi']],
        ]]);
    }

    public function test_ziyaret_kontrol_listesi_dort_kategoride_toplanir_ve_toplu_isaretlenir(): void
    {
        $this->planKur();

        $sayfa = Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id]);
        $gruplar = $sayfa->instance()->planGruplari;

        $this->assertSame(['risk', 'yangin', 'egitim', 'ortam'], array_keys($gruplar));
        $this->assertSame([2, 1, 1, 1], array_column($gruplar, 'toplam'));
        $sayfa->assertSee('Risk & Ekipman / İskele')->assertSee('Yangın & Acil Durum')->assertSee('Eğitim & KKD')->assertSee('Ortam & Hijyen')
            ->assertSee('Tümünü Uygun İşaretle');

        $sayfa->call('kategoriTumunuUygunIsaretle', 'risk');

        $plan = YillikPlan::sole();
        $this->assertSame('tamamlandi', $plan->faaliyetler[0]['aylar'][9]);
        $this->assertSame('tamamlandi', $plan->faaliyetler[1]['aylar'][9]);
        $this->assertSame('planlandi', $plan->faaliyetler[2]['aylar'][9], 'Diğer kategori etkilenmemeli.');
        $this->assertSame(2, $sayfa->instance()->planGruplari['risk']['yapilan']);

        // İkinci çağrı işaretlenenleri geri almaz.
        $sayfa->call('kategoriTumunuUygunIsaretle', 'risk');
        $this->assertSame('tamamlandi', YillikPlan::sole()->faaliyetler[0]['aylar'][9]);
    }

    public function test_plan_maddesi_uygunsuz_isareti_hizli_bulgu_taslagi_acar(): void
    {
        $this->planKur();

        Livewire::test(ZiyaretModu::class, ['firmaId' => $this->firma->id])
            ->call('planMaddesiUygunsuz', 'faaliyetler', 2)
            ->assertRedirect(HizliSahaBulgusu::getUrl());

        Livewire::test(HizliSahaBulgusu::class)
            ->assertSet('firmaId', $this->firma->id)
            ->assertSet('uygunsuzluk', 'Plan maddesi uygulanmıyor / uygun değil: Yangın tüpü kontrolü');

        Carbon::setTestNow();
    }
}
