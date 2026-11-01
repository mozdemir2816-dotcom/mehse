<?php

namespace Tests\Feature;

use App\Filament\Pages\KullaniciYonetimi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DenemeSuresiTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(?int $kalanGun, string $plan = 'deneme', string $rol = 'uzman'): User
    {
        return User::factory()->create([
            'rol' => $rol,
            'abonelik_plani' => $plan,
            'abonelik_bitis' => $kalanGun === null ? null : today()->addDays($kalanGun),
        ]);
    }

    public function test_deneme_durumu_ve_kalan_gun(): void
    {
        $this->assertTrue($this->kullanici(null)->hasActiveTrial());
        $this->assertTrue($this->kullanici(0)->hasActiveTrial());   // bitiş günü dahil
        $this->assertFalse($this->kullanici(-1)->hasActiveTrial());
        $this->assertTrue($this->kullanici(-30, rol: 'sahip')->hasActiveTrial()); // sahip muaf

        $this->assertSame(5, $this->kullanici(5)->trialDaysRemaining());
        $this->assertNull($this->kullanici(null)->trialDaysRemaining());

        $this->assertTrue($this->kullanici(7)->shouldShowTrialWarning());
        $this->assertTrue($this->kullanici(0)->shouldShowTrialWarning());
        $this->assertFalse($this->kullanici(8)->shouldShowTrialWarning());
        $this->assertFalse($this->kullanici(-1)->shouldShowTrialWarning());
        $this->assertFalse($this->kullanici(null)->shouldShowTrialWarning());
        $this->assertFalse($this->kullanici(3, rol: 'sahip')->shouldShowTrialWarning());
    }

    public function test_suresi_dolan_panel_yerine_sure_doldu_ekranina_gider(): void
    {
        $user = $this->kullanici(-1);

        $this->actingAs($user)->get('/admin')->assertRedirect(route('filament.admin.deneme-bitti'));
        $this->actingAs($user)->get('/admin/firmalar')->assertRedirect(route('filament.admin.deneme-bitti'));

        $this->actingAs($user)->get(route('filament.admin.deneme-bitti'))->assertOk()
            ->assertSee("Sayın {$user->name}, deneme süreniz doldu", false)
            ->assertSee('Verileriniz güvende.')
            ->assertSee('https://wa.me/905064754855?text=', false)
            ->assertSee('tel:+905064754855', false)
            ->assertSee(route('filament.admin.paketler'), false)
            ->assertSee(route('filament.admin.auth.logout'), false);

        $this->actingAs($user)->get(route('filament.admin.paketler'))->assertOk();
        $this->actingAs($user)->post(route('filament.admin.auth.logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_suresi_devam_eden_ve_sahip_panele_girer(): void
    {
        $this->actingAs($this->kullanici(30))->get('/admin')->assertOk();
        $this->actingAs($this->kullanici(null))->get('/admin')->assertOk();
        $this->actingAs($this->kullanici(-10, rol: 'sahip'))->get('/admin')->assertOk();
    }

    public function test_son_7_gunde_sari_uyari_bandi_paketlere_yonlendirir(): void
    {
        $this->actingAs($this->kullanici(3))->get('/admin')->assertOk()
            ->assertSee('son 3 gün kaldı')
            ->assertSee('#fef3c7', false)
            ->assertSee(route('filament.admin.paketler'), false);

        $this->actingAs($this->kullanici(0))->get('/admin')->assertOk()
            ->assertSee('Deneme süreniz bugün sona eriyor.');

        $this->actingAs($this->kullanici(20))->get('/admin')->assertOk()
            ->assertDontSee('mehse-deneme-uyari', false);
    }

    public function test_paketler_sayfasi_uc_paketi_ve_paket_adli_whatsapp_baglantisini_gosterir(): void
    {
        $user = $this->kullanici(10);

        $yanit = $this->actingAs($user)->get(route('filament.admin.paketler'))->assertOk()
            ->assertSeeInOrder(['Bireysel Uzman', 'En çok tercih edilen', 'Profesyonel OSGB', 'Kurumsal &amp; Sanayi'], false);

        foreach (['Bireysel Uzman', 'Profesyonel OSGB', 'Kurumsal & Sanayi'] as $paket) {
            $yanit->assertSee(rawurlencode("\"{$paket}\" paketiyle"), false);
        }
    }

    public function test_sahip_aboneligi_uzatinca_erisim_geri_gelir(): void
    {
        $user = $this->kullanici(-5);
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        Livewire::test(KullaniciYonetimi::class)
            ->call('ac', $user->id)
            ->assertSet('abonelikBitis', today()->subDays(5)->toDateString())
            ->call('abonelikUzat', 30)
            ->assertSet('abonelikBitis', today()->addDays(30)->toDateString())
            ->set('abonelikPlani', 'osgb')
            ->call('kaydet')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('osgb', $user->abonelik_plani);
        $this->assertTrue($user->hasActiveTrial());
        $this->actingAs($user)->get('/admin')->assertOk();
    }
}
