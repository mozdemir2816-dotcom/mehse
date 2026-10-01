<?php

namespace App\Filament\Isyeri\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * İşyeri girişi — IsyeriHesabi e-postayı `eposta` sütununda tutar (Filament
 * varsayılanı `email`); PortalLogin ile aynı eşleme. Başarılı girişte
 * son_giris_at güncellenir (uzman, işverenin girip girmediğini görür).
 */
class IsyeriLogin extends Login
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Kullanıcı adı (e-posta)')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'eposta' => trim($data['email']),
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        $yanit = parent::authenticate();

        Filament::auth()->user()?->forceFill(['son_giris_at' => now()])->save();

        return $yanit;
    }
}
