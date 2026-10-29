<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as SifirlamaBildirimi;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** "Şifremi unuttum" (29.10.2026): giriş ekranında bağlantı, e-posta ile sıfırlama isteği. */
class SifreSifirlamaTest extends TestCase
{
    use RefreshDatabase;

    public function test_giris_ekraninda_sifremi_unuttum_baglantisi_var(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSee('Şifremi unuttum?')
            ->assertSee('Beni hatırla')
            ->assertSee('/admin/password-reset/request', escape: false);
    }

    public function test_sifirlama_istegi_kayitli_kullaniciya_bildirim_gonderir(): void
    {
        Notification::fake();
        $kullanici = User::factory()->create(['email' => 'uzman@ornek.test']);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'uzman@ornek.test'])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($kullanici, SifirlamaBildirimi::class);
    }

    public function test_kayitsiz_e_posta_icin_bildirim_gitmez(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'yok@ornek.test'])
            ->call('request');

        Notification::assertNothingSent();
    }
}
