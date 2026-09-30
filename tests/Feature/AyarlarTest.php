<?php

namespace Tests\Feature;

use App\Filament\Pages\Ayarlar;
use App\Filament\Widgets\HosGeldinWidget;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\User;
use App\Support\KullaniciAyarlari;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AyarlarTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_ayar_kaydetmemis_kullanici_icin_varsayilanlar_eski_sabit_degerlerle_ayni(): void
    {
        $this->assertSame('klasik', KullaniciAyarlari::tema());
        $this->assertSame(60, KullaniciAyarlari::esik('egitim'));
        $this->assertSame((int) config('isg.periyodik_kontrol.vize_yaklasan_gun'), KullaniciAyarlari::esik('ekipman'));
        $this->assertSame((int) config('isg.onayli_defter.yaklasan_gun'), KullaniciAyarlari::esik('onayli_defter'));
        $this->assertSame(60, KullaniciAyarlari::esik('kimyasal'));
        $this->assertSame(30, KullaniciAyarlari::esik('kontrol_vade'));
        $this->assertSame(
            count(config('isg.kontrol_merkezi.kriterler')),
            count(PortfoyKarne::firmaTakipKriterleri($this->uzman->id)),
        );
    }

    public function test_ayarlar_sayfasi_kaydeder(): void
    {
        $tum = array_column(config('isg.kontrol_merkezi.kriterler'), 'anahtar');
        $takip = array_values(array_diff($tum, ['isg_kurulu', 'tespit_oneri']));

        Livewire::test(Ayarlar::class)
            ->assertOk()
            ->fillForm([
                'tema' => 'windows11',
                'yogunluk' => 'kompakt',
                'yazi_boyutu' => 'buyuk',
                'esikler.egitim' => 45,
                'esikler.ekipman' => 10,
                'kontrol_takip' => $takip,
            ])
            ->call('kaydet')
            ->assertHasNoFormErrors();

        $ayar = $this->uzman->fresh()->ayarlar;
        $this->assertSame('windows11', $ayar['tema']);
        $this->assertSame('kompakt', $ayar['yogunluk']);
        $this->assertSame('buyuk', $ayar['yazi_boyutu']);
        $this->assertSame(45, $ayar['esikler']['egitim']);
        $this->assertEqualsCanonicalizing(['isg_kurulu', 'tespit_oneri'], $ayar['kontrol_haric']);
    }

    public function test_esik_disinda_kalan_deger_reddedilir(): void
    {
        Livewire::test(Ayarlar::class)
            ->fillForm(['esikler.egitim' => 0, 'esikler.kimyasal' => 400])
            ->call('kaydet')
            ->assertHasFormErrors(['esikler.egitim', 'esikler.kimyasal']);
    }

    public function test_ekipman_esigi_vize_durumunu_degistirir(): void
    {
        $ekipman = new IsEkipmani(['sonraki_vize_tarihi' => now()->addDays(20)]);

        // Varsayılan 30 gün → 20 gün kala "yaklaşan".
        $this->assertSame('yaklasan', $ekipman->vizeDurumu());

        KullaniciAyarlari::kaydet($this->uzman, ['esikler' => ['ekipman' => 10]]);

        $this->assertSame('gecerli', $ekipman->vizeDurumu());
    }

    public function test_takip_disi_kriter_kontrol_merkezi_hesabindan_cikar(): void
    {
        Firma::factory()->for($this->uzman)->create(['aktif' => true]);

        $once = array_column(PortfoyKarne::kriterler($this->uzman->id), 'anahtar');
        $this->assertContains('tespit_oneri', $once);

        KullaniciAyarlari::kaydet($this->uzman, ['kontrol_haric' => ['tespit_oneri']]);

        $sonra = array_column(PortfoyKarne::kriterler($this->uzman->id), 'anahtar');
        $this->assertNotContains('tespit_oneri', $sonra);
        $this->assertCount(count($once) - 1, $sonra);
    }

    public function test_topbar_tema_secimi_hesaba_kaydedilir(): void
    {
        $this->postJson(route('mehse.ayar.tema'), ['tema' => 'saha'])->assertNoContent();
        $this->assertSame('saha', $this->uzman->fresh()->ayarlar['tema']);

        $this->postJson(route('mehse.ayar.tema'), ['tema' => 'yok-boyle'])->assertUnprocessable();
    }

    public function test_misafir_tema_kaydedemez(): void
    {
        auth()->logout();

        $this->postJson(route('mehse.ayar.tema'), ['tema' => 'saha'])->assertUnauthorized();
    }

    public function test_kayitli_gorunum_ayarlari_sayfa_basinda_basilir(): void
    {
        KullaniciAyarlari::kaydet($this->uzman, ['tema' => 'material', 'yogunluk' => 'kompakt', 'yazi_boyutu' => 'kucuk']);

        $html = $this->get(Ayarlar::getUrl())->assertOk()->getContent();

        $this->assertStringContainsString('"tema":"material"', $html);
        $this->assertStringContainsString('"yogunluk":"kompakt"', $html);
        $this->assertStringContainsString('"yazi":"kucuk"', $html);
        // Sidebar alt kartı: Ayarlar kısayolu.
        $this->assertStringContainsString('mehse-sidebar-alt-ayar', $html);
    }

    public function test_hos_geldin_widget_render_olur(): void
    {
        Firma::factory()->for($this->uzman)->create(['aktif' => true]);

        Livewire::test(HosGeldinWidget::class)
            ->assertOk()
            ->assertSee('Firma Ekle')
            ->assertSee('Evrak uyumu');
    }
}
