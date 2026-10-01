<?php

namespace Tests\Feature;

use App\Filament\Pages\Guvenlik;
use App\Models\User;
use App\Support\YasalMetinler;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Güvenlik sayfası — isgsuite "Güvenlik ve Denetim" karşılaştırması.
 */
class GuvenlikTest extends TestCase
{
    use RefreshDatabase;

    private User $kullanici;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kullanici = User::factory()->create(['password' => 'eski-sifre-123']);
        $this->actingAs($this->kullanici);
    }

    public function test_sayfa_acilir(): void
    {
        $this->get(Guvenlik::getUrl())->assertOk()
            ->assertSee('Şifre Değiştir')->assertSee('İki Adımlı Doğrulama')->assertSee('Hukuki Onaylar');
    }

    public function test_sifre_mevcut_sifreyle_degisir(): void
    {
        Livewire::test(Guvenlik::class)
            ->fillForm(['mevcut' => 'eski-sifre-123', 'yeni' => 'yeni-guclu-sifre', 'yeni_tekrar' => 'yeni-guclu-sifre'])
            ->call('sifreDegistir')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('yeni-guclu-sifre', $this->kullanici->fresh()->password));
    }

    public function test_yanlis_mevcut_sifre_veya_kisa_sifre_reddedilir(): void
    {
        Livewire::test(Guvenlik::class)
            ->fillForm(['mevcut' => 'yanlis', 'yeni' => 'yeni-guclu-sifre', 'yeni_tekrar' => 'yeni-guclu-sifre'])
            ->call('sifreDegistir')
            ->assertHasFormErrors(['mevcut']);

        Livewire::test(Guvenlik::class)
            ->fillForm(['mevcut' => 'eski-sifre-123', 'yeni' => 'kisa123', 'yeni_tekrar' => 'kisa123'])
            ->call('sifreDegistir')
            ->assertHasFormErrors(['yeni']);

        Livewire::test(Guvenlik::class)
            ->fillForm(['mevcut' => 'eski-sifre-123', 'yeni' => 'yeni-guclu-sifre', 'yeni_tekrar' => 'baska-bir-sifre'])
            ->call('sifreDegistir')
            ->assertHasFormErrors(['yeni_tekrar']);

        $this->assertTrue(Hash::check('eski-sifre-123', $this->kullanici->fresh()->password), 'şifre değişmemeli');
    }

    public function test_tum_cihazlardan_cikis_diger_oturumlari_siler(): void
    {
        Config::set('session.driver', 'database');
        $baskasi = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'diger-cihaz', 'user_id' => $this->kullanici->id, 'ip_address' => '1.2.3.4', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'baskasinin', 'user_id' => $baskasi->id, 'ip_address' => '5.6.7.8', 'user_agent' => '', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $sayfa = Livewire::test(Guvenlik::class);
        $this->assertSame('Chrome · Windows', $sayfa->get('oturumlar')[0]['cihaz']);

        $sayfa->callAction('tumCihazlardanCikis', data: ['sifre' => 'eski-sifre-123'])->assertHasNoActionErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'diger-cihaz']);
        $this->assertDatabaseHas('sessions', ['id' => 'baskasinin']);
    }

    public function test_tum_cihazlardan_cikis_sifresiz_yapilamaz(): void
    {
        Config::set('session.driver', 'database');
        DB::table('sessions')->insert(['id' => 'diger-cihaz', 'user_id' => $this->kullanici->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        Livewire::test(Guvenlik::class)
            ->callAction('tumCihazlardanCikis', data: ['sifre' => 'yanlis'])
            ->assertHasActionErrors(['sifre']);

        $this->assertDatabaseHas('sessions', ['id' => 'diger-cihaz']);
    }

    public function test_yasal_metin_surum_bazli_onaylanir(): void
    {
        $this->assertNotEmpty(YasalMetinler::aktifler(), 'sözleşme/KVKK görünümleri mevcut olmalı');

        Livewire::test(Guvenlik::class)->call('yasalOnayla', 'kvkk');

        $kullanici = $this->kullanici->fresh();
        $this->assertTrue(YasalMetinler::guncelOnayliMi($kullanici, 'kvkk'));
        $this->assertFalse(YasalMetinler::guncelOnayliMi($kullanici, 'sozlesme'));

        // Metin güncellenince eski onay geçersiz sayılır, yeni onay eklenir (eski iz korunur).
        Config::set('isg.kayit.yasal_metinler.kvkk.revizyon', 'Sürüm 9.9');
        $this->assertFalse(YasalMetinler::guncelOnayliMi($kullanici, 'kvkk'));

        Livewire::test(Guvenlik::class)->call('yasalOnayla', 'kvkk');
        $onaylar = $this->kullanici->fresh()->yasal_onaylar;
        $this->assertCount(2, $onaylar);
        $this->assertSame('Sürüm 9.9', $onaylar[1]['revizyon']);
    }

    public function test_mfa_durumu_ve_sirlar_sifreli_saklanir(): void
    {
        $this->assertFalse($this->kullanici->mfaAcikMi());

        $this->kullanici->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->kullanici->saveAppAuthenticationRecoveryCodes(['kod-1', 'kod-2']);

        $kullanici = $this->kullanici->fresh();
        $this->assertTrue($kullanici->mfaAcikMi());
        $this->assertSame(['kod-1', 'kod-2'], $kullanici->getAppAuthenticationRecoveryCodes());

        $ham = DB::table('users')->where('id', $kullanici->id)->value('app_authentication_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $ham, 'sır veritabanında açık metin durmamalı');

        $this->get(Guvenlik::getUrl())->assertSee('Durum: Açık');
    }

    public function test_panelde_uygulama_tabanli_mfa_tanimli(): void
    {
        $saglayicilar = filament()->getPanel('admin')->getMultiFactorAuthenticationProviders();

        $this->assertInstanceOf(AppAuthentication::class, collect($saglayicilar)->first());

        // Kurulum profil sayfasında — sayfa MFA bölümüyle açılmalı.
        $this->get(filament()->getProfileUrl())->assertOk()->assertSee('Doğrulama uygulaması');
    }
}
