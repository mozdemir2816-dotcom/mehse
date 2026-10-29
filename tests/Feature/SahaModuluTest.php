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
use App\Support\KomutaFirma;
use App\Support\SayfaAileleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_yedi_sayfa_uc_ana_sekmede_toplanir(): void
    {
        $g = SayfaAileleri::sekmeGruplari([ZiyaretModu::class]);

        $this->assertSame(
            ['Aktif Saha Denetimi & Ziyaret Modu', 'Bulgu & DÖF Takip Havuzu', 'Yasal Rapor & Defter Çıktıları'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame([true, false, false], array_column($g['ust'], 'aktif'));
        $this->assertSame(['Bugün (Ziyaret Modu)', 'Bulgular', 'Saha Denetimi'], array_column($g['alt'], 'etiket'));

        // Tek sayfalı grup alt şerit göstermez; defter grubunda üç sayfa.
        $this->assertSame([], SayfaAileleri::sekmeGruplari([DofOlustur::class])['alt']);
        $defter = SayfaAileleri::sekmeGruplari([AiSahaAnalizi::class]);
        $this->assertSame([false, false, true], array_column($defter['ust'], 'aktif'));
        $this->assertCount(3, $defter['alt']);

        foreach ([HizliSahaBulgusu::class, SahaDenetimi::class, TespitOneriDefteri::class, OnBilgilendirmeler::class] as $sinif) {
            $this->assertNotNull(SayfaAileleri::sekmeGruplari([$sinif]), $sinif);
        }

        // Saha ailesinde olmayan sayfa düz sekme şeridini kullanır.
        $this->assertNull(SayfaAileleri::sekmeGruplari([\App\Filament\Pages\KontrolMerkezi::class]));
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

    public function test_baska_uzmanin_firmasi_baglamda_gosterilmez(): void
    {
        $yabanci = Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı Firma']);
        KomutaFirma::sec($yabanci->id);

        // Bilgi çubuğu yalnız kullanıcının kendi firmasını gösterir (sahip rolündeki kullanıcının sayfa listesi geniştir).
        $this->get(DofOlustur::getUrl())->assertOk()->assertSee('Firma seçilmedi')->assertDontSee('mehse-saha-baglam-liste', false);
    }
}
