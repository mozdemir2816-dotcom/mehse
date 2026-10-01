<?php

namespace Tests\Feature;

use App\Filament\Isyeri\Auth\IsyeriLogin;
use App\Filament\Isyeri\Pages\IsyeriEvraklari;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\IsyeriHesabi;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * İşyeri (işveren) girişi — isgsuite "İşyeri Kiosk Giriş Bilgileri" karşılığı:
 * kalıcı e-posta + şifre, salt-okunur evrak görüntüleme, firma sınırı.
 */
class IsyeriGirisTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Deneme Yapı A.Ş.']);
    }

    public function test_hesap_kalici_olusur_sifre_okunabilir_ve_hashli(): void
    {
        $hesap = IsyeriHesabi::firmaIcin($this->firma);

        $this->assertSame('isyeri.'.$this->firma->id.'@giris.mehse.com', $hesap->eposta);
        $this->assertSame(12, strlen($hesap->sifre_acik));
        $this->assertTrue(Hash::check($hesap->sifre_acik, $hesap->sifre));
        $this->assertSame($hesap->sifre_acik, IsyeriHesabi::firmaIcin($this->firma)->sifre_acik, 'ikinci açılışta aynı şifre');
        $this->assertSame(1, IsyeriHesabi::count());
    }

    public function test_sifre_sifirlaninca_eskisi_gecersiz_olur(): void
    {
        $hesap = IsyeriHesabi::firmaIcin($this->firma);
        $eski = $hesap->sifre_acik;

        $yeni = $hesap->sifreyiSifirla();

        $this->assertNotSame($eski, $yeni);
        $this->assertFalse(Hash::check($eski, $hesap->fresh()->sifre));
        $this->assertTrue(Hash::check($yeni, $hesap->fresh()->sifre));
    }

    public function test_uzman_isyeri_girisi_penceresinde_bilgileri_gorur(): void
    {
        $this->actingAs($this->uzman);

        Livewire::test(EditFirma::class, ['record' => $this->firma->getRouteKey()])
            ->mountAction('isyeriGirisi')
            ->assertMountedActionModalSee('isyeri.'.$this->firma->id.'@giris.mehse.com')
            ->assertMountedActionModalSee(IsyeriHesabi::firstOrFail()->sifre_acik);
    }

    public function test_isveren_giris_yapar_ve_son_giris_kaydedilir(): void
    {
        $hesap = IsyeriHesabi::firmaIcin($this->firma);
        Filament::setCurrentPanel(Filament::getPanel('isyeri'));

        Livewire::test(IsyeriLogin::class)
            ->fillForm(['email' => $hesap->eposta, 'password' => $hesap->sifre_acik])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertTrue(auth('isyeri')->check());
        $this->assertFalse(auth('web')->check(), 'işyeri girişi uzman paneline giriş sayılmaz');
        $this->assertNotNull($hesap->fresh()->son_giris_at);
    }

    public function test_yanlis_sifreyle_giris_yapilamaz(): void
    {
        $hesap = IsyeriHesabi::firmaIcin($this->firma);
        Filament::setCurrentPanel(Filament::getPanel('isyeri'));

        Livewire::test(IsyeriLogin::class)
            ->fillForm(['email' => $hesap->eposta, 'password' => 'yanlis-sifre'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertFalse(auth('isyeri')->check());
    }

    public function test_isveren_yalniz_kendi_firmasinin_evraklarini_gorur(): void
    {
        $baska = Firma::factory()->for(User::factory())->create(['unvan' => 'Rakip Ltd.']);
        $kendi = IsIzinFormu::create(['firma_id' => $this->firma->id, 'calisma_alani' => 'Çatı']);
        $yabanci = IsIzinFormu::create(['firma_id' => $baska->id]);

        $this->actingAs(IsyeriHesabi::firmaIcin($this->firma), 'isyeri');

        $this->get('/isyeri')->assertOk()->assertSee('Deneme Yapı A.Ş.')->assertDontSee('Rakip Ltd.');

        Filament::setCurrentPanel(Filament::getPanel('isyeri'));
        $sayfa = Livewire::test(IsyeriEvraklari::class);

        $this->assertSame([IsIzinFormu::class.':'.$kendi->id], array_column($sayfa->get('evraklar'), 'anahtar'));

        $sayfa->call('evrakIndir', IsIzinFormu::class.':'.$kendi->id)->assertFileDownloaded();
        $sayfa->call('evrakIndir', IsIzinFormu::class.':'.$yabanci->id)->assertNoFileDownloaded();
    }

    public function test_isveren_uzman_paneline_giremez(): void
    {
        $this->actingAs(IsyeriHesabi::firmaIcin($this->firma), 'isyeri');

        $this->get('/admin')->assertRedirect();
        $this->assertFalse(auth('web')->check());
    }

    public function test_pasif_firma_veya_kapali_giris_panele_giremez(): void
    {
        $hesap = IsyeriHesabi::firmaIcin($this->firma);

        $hesap->update(['aktif' => false]);
        $this->actingAs($hesap, 'isyeri')->get('/isyeri')->assertForbidden();

        $hesap->update(['aktif' => true]);
        $this->firma->update(['aktif' => false]);
        $this->actingAs($hesap->fresh(), 'isyeri')->get('/isyeri')->assertForbidden();
    }

    public function test_firma_pasife_alinir_ve_aktiflestirilir(): void
    {
        $this->actingAs($this->uzman);

        Livewire::test(ListFirmas::class)->callTableAction('pasifeAl', $this->firma);
        $this->assertFalse($this->firma->fresh()->aktif);

        Livewire::test(ListFirmas::class)->filterTable('aktif', false)->callTableAction('aktifEt', $this->firma);
        $this->assertTrue($this->firma->fresh()->aktif);
    }

    public function test_firma_silinince_isyeri_hesabi_da_silinir(): void
    {
        IsyeriHesabi::firmaIcin($this->firma);

        $this->firma->delete();

        $this->assertSame(0, IsyeriHesabi::count());
    }
}
