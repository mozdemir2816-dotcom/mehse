<?php

namespace App\Filament\Auth;

use App\Models\Basvuru;
use App\Models\User;
use App\Support\YasalMetinler;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * İş Güvenliği Uzmanı bireysel kaydı (isgsuite.tr "Bireysel Başvuru" referansı):
 * ad, e-posta, telefon, sertifika sınıfı, şifre + etkin yasal onaylar. Hesap
 * anında açılır ama hiçbir sayfa/firma yetkisi OLMADAN — sahip hesap
 * KullaniciYonetimi ekranından erişimi tek tek tanımlar. Onay izi `basvurular`
 * tablosuna tip=uzman olarak yazılır. OSGB başvurusu: App\Filament\Auth\OsgbBasvuru.
 */
class KayitOl extends Register
{
    /** İki sütunlu form — varsayılan dar kart yerine. */
    protected Width|string|null $maxContentWidth = Width::TwoExtraLarge;

    public function getHeading(): string|Htmlable
    {
        return 'İş Güvenliği Uzmanı Kaydı';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    $this->getNameFormComponent()->label('Ad soyad'),
                    $this->getEmailFormComponent(),
                    $this->getTelefonFormComponent(),
                    $this->getSertifikaFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent()->label('Şifre tekrar'),
                ]),
                ...YasalMetinler::onayAlanlari(),
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

    protected function getSertifikaFormComponent(): Component
    {
        return Select::make('unvan')
            ->label('Sertifika sınıfı')
            ->options(config('isg.kayit.sertifika_siniflari'))
            ->required();
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        // $data['password'] form alanının dehydrateStateUsing'i tarafından zaten
        // hash'lenmiş halde gelir (bkz. Filament\Auth\Pages\Register::getPasswordFormComponent).
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefon' => $data['telefon'],
            'unvan' => $data['unvan'],
            'password' => $data['password'],
            'rol' => 'uzman',
            'aktif' => true,
        ]);

        Basvuru::create([
            'tip' => 'uzman',
            'durum' => 'onaylandi',
            'ad_soyad' => $data['name'],
            'eposta' => $data['email'],
            'telefon' => $data['telefon'],
            'onaylar' => YasalMetinler::onayKaydi(),
            'ip' => request()->ip(),
            'user_id' => $user->id,
            'incelendi_at' => now(),
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
