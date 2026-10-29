<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Panel giriş sayfası: stok Filament girişi + e-posta alanında örnek metin ve
 * "Beni hatırla" ile "Şifremi unuttum?" bağlantısının tek satırda hizalanması.
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        $satir = [$this->getRememberFormComponent()];

        if (filament()->hasPasswordReset()) {
            $satir[] = Html::make(new HtmlString(
                '<a href="'.e(filament()->getRequestPasswordResetUrl()).'" class="fi-link fi-size-sm mehse-giris-unuttum" tabindex="-1">Şifremi unuttum?</a>'
            ));
        }

        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            Grid::make(2)->schema($satir)->extraAttributes(['class' => 'mehse-giris-satir']),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/login.form.email.label'))
            ->email()
            ->required()
            ->placeholder('ornek@mehse.com')
            ->autocomplete('username')
            ->autofocus();
    }

    /** Sıfırlama bağlantısı alt satırda; parola alanının üstündeki ipucu (hint) kaldırıldı. */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }
}
