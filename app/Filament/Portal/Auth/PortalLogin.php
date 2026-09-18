<?php

namespace App\Filament\Portal\Auth;

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Çalışan portalı `Calisan` modeli e-posta sütununu `eposta` olarak tutuyor,
 * Filament'ın varsayılan Login sayfası ise `email` alanını bekliyor —
 * bu eşleşmezlik yüzünden girişte sessizce "Unknown column 'email'" hatası
 * alınıyordu (hiç fark edilmemiş, egitim_atamalari tablosu hep boştu).
 *
 * E-postası olmayan çalışanlar için Uzaktan Eğitim Atama, aynı `eposta`
 * sütununa gerçek e-posta yerine geçici bir kullanıcı kodu (ör. "ayilmaz482")
 * yazar — bu yüzden alan artık ->email() format doğrulaması İSTEMİYOR, düz
 * metin girişi kabul ediyor.
 */
class PortalLogin extends Login
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-posta / Kullanıcı Kodu')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'eposta' => $data['email'],
            'password' => $data['password'],
        ];
    }
}
