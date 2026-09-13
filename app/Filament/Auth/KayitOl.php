<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * Kendi kendine kayıt: e-posta + telefon + şifre. Yeni hesap varsayılan olarak
 * hiçbir sayfa/firma yetkisi OLMADAN oluşturulur — sahip hesap KullaniciYonetimi
 * ekranından erişimi tek tek tanımlayana kadar panel neredeyse boş görünür.
 */
class KayitOl extends Register
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getTelefonFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getTelefonFormComponent(): Component
    {
        return TextInput::make('telefon')
            ->label('Telefon')
            ->tel()
            ->required()
            ->maxLength(20);
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        // $data['password'] form alanının dehydrateStateUsing'i tarafından zaten
        // hash'lenmiş halde gelir (bkz. Filament\Auth\Pages\Register::getPasswordFormComponent).
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefon' => $data['telefon'],
            'password' => $data['password'],
            'rol' => 'uzman',
            'aktif' => true,
        ]);

        Notification::make()
            ->title('Kaydınız alındı')
            ->body('Yönetici size erişim tanımladıktan sonra ilgili sayfaları görebileceksiniz.')
            ->success()
            ->persistent()
            ->send();

        return $user;
    }
}
