<?php

namespace Tests\Feature;

use App\Filament\Auth\KayitOl;
use App\Filament\Auth\OsgbBasvuru;
use App\Filament\Pages\Basvurular;
use App\Models\Basvuru;
use App\Models\User;
use App\Support\YasalMetinler;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kayıt / başvuru (isgsuite.tr "Başvuru seçenekleri" referansı): bireysel İGU
 * kaydı, OSGB başvurusu, sahip onayı/reddi, yasal onay altyapısı.
 */
class BasvuruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** @return array<string, string> */
    private function osgbVerisi(array $ek = []): array
    {
        return [
            'osgb_adi' => 'Örnek OSGB Ltd.',
            'yetki_no' => 'OSGB-1234',
            'vergi_no' => '1234567890',
            'iletisim_eposta' => 'iletisim@ornekosgb.com',
            'ad_soyad' => 'Ayşe Başvuran',
            'eposta' => 'ayse@ornekosgb.com',
            'yasal_onay_sozlesme' => true,
            'yasal_onay_kvkk' => true,
            ...$ek,
        ];
    }

    public function test_giris_ekrani_iki_basvuru_secenegini_gosterir(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSee('Başvuru seçenekleri')
            ->assertSee('İş Güvenliği Uzmanı')
            ->assertSee('OSGB Başvurusu')
            ->assertSee(OsgbBasvuru::getUrl(), escape: false);
    }

    public function test_osgb_basvuru_sayfasi_herkese_acik(): void
    {
        $this->get(OsgbBasvuru::getUrl())->assertOk()->assertSee('OSGB Başvurusu')->assertSee('Yetki no');
    }

    public function test_bireysel_kayit_sertifika_sinifi_ile_hesap_ve_onay_izi_olusturur(): void
    {
        Livewire::test(KayitOl::class)
            ->fillForm([
                'name' => 'Can Uzman',
                'email' => 'can@ornek.com',
                'telefon' => '05551112233',
                'unvan' => 'b_sinifi',
                'password' => 'GucluSifre123',
                'passwordConfirmation' => 'GucluSifre123',
                'yasal_onay_sozlesme' => true, 'yasal_onay_kvkk' => true,
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'can@ornek.com')->firstOrFail();
        $this->assertSame('b_sinifi', $user->unvan);
        $this->assertSame('uzman', $user->rol);
        $this->assertDatabaseHas('basvurular', ['tip' => 'uzman', 'durum' => 'onaylandi', 'user_id' => $user->id]);
    }

    public function test_bireysel_kayitta_sertifika_sinifi_zorunlu(): void
    {
        Livewire::test(KayitOl::class)
            ->fillForm(['name' => 'X', 'email' => 'x@ornek.com', 'telefon' => '1', 'password' => 'GucluSifre123', 'passwordConfirmation' => 'GucluSifre123'])
            ->call('register')
            ->assertHasFormErrors(['unvan' => 'required']);
    }

    public function test_osgb_basvurusu_hesap_acmadan_beklemede_kaydedilir(): void
    {
        Livewire::test(OsgbBasvuru::class)
            ->fillForm($this->osgbVerisi())
            ->call('gonder')
            ->assertHasNoFormErrors()
            ->assertRedirect(filament()->getLoginUrl());

        $this->assertDatabaseHas('basvurular', ['tip' => 'osgb', 'durum' => 'beklemede', 'eposta' => 'ayse@ornekosgb.com', 'vergi_no' => '1234567890']);
        $this->assertDatabaseMissing('users', ['email' => 'ayse@ornekosgb.com']);
    }

    public function test_osgb_basvurusunda_vergi_no_ve_mevcut_eposta_dogrulanir(): void
    {
        User::factory()->create(['email' => 'var@ornek.com']);

        Livewire::test(OsgbBasvuru::class)
            ->fillForm($this->osgbVerisi(['vergi_no' => '12AB', 'eposta' => 'var@ornek.com']))
            ->call('gonder')
            ->assertHasFormErrors(['vergi_no', 'eposta']);

        $this->assertSame(0, Basvuru::query()->count());
    }

    public function test_basvurular_sayfasi_yalniz_sahibe_acik(): void
    {
        $this->actingAs(User::factory()->kisitli()->create());
        $this->assertFalse(Basvurular::canAccess());

        $this->actingAs(User::factory()->create());
        $this->assertTrue(Basvurular::canAccess());
    }

    public function test_sahip_osgb_basvurusunu_onaylayinca_deneme_hesabi_acilir(): void
    {
        $sahip = User::factory()->create();
        $this->actingAs($sahip);
        $basvuru = Basvuru::create([...Arr::except($this->osgbVerisi(), ['yasal_onay_sozlesme', 'yasal_onay_kvkk']), 'tip' => 'osgb', 'durum' => 'beklemede']);

        Livewire::test(Basvurular::class)
            ->callTableAction('onayla', $basvuru)
            ->assertHasNoTableActionErrors();

        $user = User::query()->where('email', 'ayse@ornekosgb.com')->firstOrFail();
        $this->assertSame('deneme', $user->abonelik_plani);
        $this->assertSame(today()->addDays(90)->toDateString(), $user->abonelik_bitis->toDateString());
        $this->assertSame('uzman', $user->rol);

        $basvuru->refresh();
        $this->assertSame('onaylandi', $basvuru->durum);
        $this->assertSame($user->id, $basvuru->user_id);
        $this->assertSame($sahip->id, $basvuru->inceleyen_id);
    }

    public function test_sahip_osgb_basvurusunu_nedenle_reddeder(): void
    {
        $this->actingAs(User::factory()->create());
        $basvuru = Basvuru::create([...Arr::except($this->osgbVerisi(), ['yasal_onay_sozlesme', 'yasal_onay_kvkk']), 'tip' => 'osgb', 'durum' => 'beklemede']);

        Livewire::test(Basvurular::class)
            ->callTableAction('reddet', $basvuru, ['neden' => 'Yetki belgesi doğrulanamadı'])
            ->assertHasNoTableActionErrors();

        $basvuru->refresh();
        $this->assertSame('reddedildi', $basvuru->durum);
        $this->assertSame('Yetki belgesi doğrulanamadı', $basvuru->red_nedeni);
        $this->assertDatabaseMissing('users', ['email' => 'ayse@ornekosgb.com']);
    }

    public function test_mehse_sozlesme_ve_kvkk_metinleri_etkin_ve_formda_gorunur(): void
    {
        $this->assertSame(['sozlesme', 'kvkk'], array_keys(YasalMetinler::aktifler()));

        $this->get(OsgbBasvuru::getUrl())->assertOk()
            ->assertSee('Mehse Hizmet ve Kullanım Sözleşmesini')
            ->assertSee('kişisel verilerimin işlenmesi hakkında bilgilendirildim', escape: false)
            ->assertSee('Sürüm 1.1 · 30.09.2026')
            ->assertSee('Sağlayıcı: Mehse adıyla faaliyet gösteren');
    }

    public function test_onay_kutulari_isaretlenmeden_basvuru_yapilamaz(): void
    {
        Livewire::test(OsgbBasvuru::class)
            ->fillForm($this->osgbVerisi(['yasal_onay_sozlesme' => false, 'yasal_onay_kvkk' => false]))
            ->call('gonder')
            ->assertHasFormErrors(['yasal_onay_sozlesme' => 'accepted', 'yasal_onay_kvkk' => 'accepted']);

        $this->assertSame(0, Basvuru::query()->count());
    }

    public function test_yasal_metin_eklenince_onay_zorunlu_olur_ve_revizyonla_kaydedilir(): void
    {
        View::addNamespace('testyasal', base_path('tests/Fixtures/yasal'));
        config([
            'isg.kayit.yasal_metinler.sozlesme.gorunum' => 'testyasal::sozlesme',
            'isg.kayit.yasal_metinler.sozlesme.revizyon' => '01.10.2026',
        ]);

        $this->get(OsgbBasvuru::getUrl())->assertOk()
            ->assertSee('Test maddesi')
            ->assertSee('yasal-sozlesme', escape: false);

        Livewire::test(OsgbBasvuru::class)
            ->fillForm($this->osgbVerisi(['yasal_onay_sozlesme' => false]))
            ->call('gonder')
            ->assertHasFormErrors(['yasal_onay_sozlesme' => 'accepted']);

        Livewire::test(OsgbBasvuru::class)
            ->fillForm($this->osgbVerisi())
            ->call('gonder')
            ->assertHasNoFormErrors();

        $onay = Basvuru::query()->firstOrFail()->onaylar[0];
        $this->assertSame('sozlesme', $onay['anahtar']);
        $this->assertSame('01.10.2026', $onay['revizyon']);
    }
}
