<?php

namespace Tests\Feature;

use App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris;
use App\Mail\YeniTeklifTalebi;
use App\Models\TeklifTalebi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Herkese açık ana sayfa (tanıtım) + teklif formu + panelde Teklif Talepleri. */
class TanitimSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $ek = []): array
    {
        return $ek + [
            'firma' => 'Örnek Metal San. Ltd.',
            'kisi' => 'Ali Veli',
            'telefon' => '0532 111 22 33',
            'calisan' => 45,
            'sinif' => 'Tehlikeli',
            'kvkk' => '1',
        ];
    }

    public function test_ana_sayfa_giris_olmadan_acilir_ve_iletisim_bilgileri_dogru(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Evrakla değil, ', false)
            ->assertSee('Üye Girişi')
            ->assertSee('mozdemir2816@gmail.com')
            ->assertSee('0506 475 48 55')
            ->assertSee('tel:+905064754855', false)
            ->assertSee('wa.me/905064754855', false)
            ->assertSee('css/tanitim.css', false);
    }

    public function test_eski_tanitim_adresi_ana_sayfaya_yonlenir(): void
    {
        $this->get('/tanitim')->assertRedirect(url('/'))->assertStatus(301);
    }

    public function test_giris_ekraninda_tanitim_baglantisi_var(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Ana Sayfaya Dön');
    }

    public function test_teklif_formu_talebi_kaydeder(): void
    {
        $this->post(route('tanitim.teklif'), $this->form())
            ->assertRedirect(url('/').'#teklif')
            ->assertSessionHas('teklif_gonderildi');

        $t = TeklifTalebi::sole();
        $this->assertSame('Örnek Metal San. Ltd.', $t->firma_adi);
        $this->assertSame(45, $t->calisan_sayisi);
        $this->assertSame('yeni', $t->durum);

        $this->followingRedirects()->post(route('tanitim.teklif'), $this->form())->assertSee('Talebiniz alındı');
    }

    public function test_teklif_talebi_sahibe_eposta_bildirimi_gonderir(): void
    {
        Mail::fake();
        config(['isg.teklif_bildirim_eposta' => 'sahip@example.test']);

        $this->post(route('tanitim.teklif'), $this->form())->assertSessionHas('teklif_gonderildi');

        Mail::assertSent(YeniTeklifTalebi::class, fn (YeniTeklifTalebi $m) => $m->hasTo('sahip@example.test')
            && $m->talep->firma_adi === 'Örnek Metal San. Ltd.'
            && str_contains($m->render(), '45'));
    }

    public function test_alici_tanimli_degilse_eposta_gitmez_ama_talep_kaydedilir(): void
    {
        Mail::fake();
        config(['isg.teklif_bildirim_eposta' => null]);

        $this->post(route('tanitim.teklif'), $this->form())->assertSessionHas('teklif_gonderildi');

        Mail::assertNothingSent();
        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_eposta_gonderimi_patlasa_da_talep_kaydedilir_ve_ziyaretci_hata_gormez(): void
    {
        config(['isg.teklif_bildirim_eposta' => 'sahip@example.test']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP kapalı'));

        $this->post(route('tanitim.teklif'), $this->form())
            ->assertRedirect(url('/').'#teklif')
            ->assertSessionHas('teklif_gonderildi');

        $this->assertSame(1, TeklifTalebi::count());
    }

    public function test_eksik_veya_gecersiz_form_kaydedilmez(): void
    {
        $this->from('/')->post(route('tanitim.teklif'), $this->form(['telefon' => 'abc', 'sinif' => 'Başka', 'kvkk' => null]))
            ->assertSessionHasErrors(['telefon', 'sinif', 'kvkk']);

        $this->post(route('tanitim.teklif'), $this->form(['calisan' => 0]))->assertSessionHasErrors('calisan');
        $this->assertSame(0, TeklifTalebi::count());
    }

    public function test_bot_tuzagi_dolu_ise_kaydedilmez(): void
    {
        $this->post(route('tanitim.teklif'), $this->form(['web' => 'http://spam.example']))->assertSessionHas('teklif_gonderildi');

        $this->assertSame(0, TeklifTalebi::count());
    }

    public function test_istek_siniri_asilinca_engellenir(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('tanitim.teklif'), $this->form());
        }

        $this->post(route('tanitim.teklif'), $this->form())->assertStatus(429);
        $this->assertSame(5, TeklifTalebi::count());
    }

    public function test_panelde_yalniz_sahip_gorur_ve_durum_guncelleyebilir(): void
    {
        $talep = TeklifTalebi::create(['firma_adi' => 'X', 'kisi' => 'Y', 'telefon' => '0532 000 00 00', 'calisan_sayisi' => 10, 'tehlike_sinifi' => 'Az Tehlikeli']);

        $this->actingAs(User::factory()->create(['rol' => 'uzman']));
        $this->get('/admin/teklif-talepleri')->assertForbidden();

        $this->actingAs(User::factory()->create(['rol' => 'sahip']));
        Livewire::test(ListTeklifTalepleris::class)->assertCanSeeTableRecords([$talep]);

        Livewire::test(ListTeklifTalepleris::class)
            ->callTableAction('edit', $talep, ['durum' => 'arandi', 'not' => 'Pazartesi dönülecek'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('arandi', $talep->fresh()->durum);
    }
}
