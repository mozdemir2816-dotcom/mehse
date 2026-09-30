<?php

namespace App\Filament\Auth;

use App\Models\Basvuru;
use App\Models\User;
use App\Support\YasalMetinler;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * OSGB başvurusu (isgsuite.tr "OSGB Başvuru" referansı) — herkese açık form.
 * Hesap açmaz: `basvurular` tablosuna tip=osgb, durum=beklemede kayıt düşer;
 * sahip App\Filament\Pages\Basvurular'dan onaylayınca hesap + deneme açılır.
 * Route: AdminPanelProvider ->routes() → filament.admin.osgb-basvuru.
 */
class OsgbBasvuru extends SimplePage
{
    use WithRateLimiting;

    protected Width|string|null $maxContentWidth = Width::TwoExtraLarge;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getUrl(): string
    {
        return route('filament.admin.osgb-basvuru');
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return 'OSGB Başvurusu';
    }

    public function getHeading(): string|Htmlable
    {
        return 'OSGB Başvurusu';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $gun = (int) config('isg.kayit.osgb_deneme_gun', 90);

        return new HtmlString(
            "Başvurunuz incelendikten sonra hesabınız açılır ve {$gun} günlük deneme başlar. "
            .'Hesabınız var mı? <a href="'.e(filament()->getLoginUrl()).'" class="fi-link">Giriş yapın</a>'
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    TextInput::make('osgb_adi')->label('OSGB adı')->required()->maxLength(255),
                    TextInput::make('yetki_no')->label('Yetki no')->required()->maxLength(50),
                    TextInput::make('vergi_no')->label('Vergi no')->required()
                        ->regex('/^\d{8,11}$/')
                        ->validationMessages(['regex' => 'Vergi no 8–11 haneli rakamlardan oluşmalıdır.']),
                    TextInput::make('sorumlu_mudur')->label('Sorumlu müdür')->maxLength(255),
                    TextInput::make('iletisim_eposta')->label('İletişim e-posta')->email()->required()->maxLength(255)
                        ->placeholder('ornek@firma.com'),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(20),
                    Textarea::make('adres')->label('Adres')->rows(2)->maxLength(1000)->columnSpanFull(),
                    TextInput::make('ad_soyad')->label('Başvuran adı soyadı')->required()->maxLength(255),
                    TextInput::make('eposta')->label('Başvuran e-posta')->email()->required()->maxLength(255)
                        ->placeholder('ornek@firma.com')
                        ->helperText('Onaylanınca hesabınız bu adresle açılır.')
                        ->rules([
                            fn (): \Closure => function (string $attribute, $value, \Closure $fail): void {
                                if (User::query()->where('email', $value)->exists()) {
                                    $fail('Bu e-posta adresiyle zaten bir hesap var — giriş yapmayı deneyin.');
                                } elseif (Basvuru::query()->where('eposta', $value)->where('durum', 'beklemede')->exists()) {
                                    $fail('Bu e-posta ile incelemede bekleyen bir başvuru zaten var.');
                                }
                            },
                        ]),
                    Textarea::make('not')->label('Not')->rows(2)->maxLength(2000)->columnSpanFull(),
                ]),
                ...YasalMetinler::onayAlanlari(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('gonder')
                ->footer([
                    Actions::make([
                        Action::make('geri')
                            ->label('Geri')
                            ->color('gray')
                            ->url(filament()->getLoginUrl()),
                        Action::make('gonder')
                            ->label('Başvuruyu Gönder')
                            ->submit('gonder'),
                    ])->alignment('end')->key('form-actions'),
                ]),
        ]);
    }

    public function gonder(): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title('Çok fazla deneme')
                ->body("Lütfen {$e->secondsUntilAvailable} saniye sonra tekrar deneyin.")
                ->danger()->send();

            return;
        }

        $veri = $this->form->getState();

        Basvuru::create([
            'tip' => 'osgb',
            'durum' => 'beklemede',
            'ad_soyad' => $veri['ad_soyad'],
            'eposta' => $veri['eposta'],
            'telefon' => $veri['telefon'] ?? null,
            'osgb_adi' => $veri['osgb_adi'],
            'yetki_no' => $veri['yetki_no'],
            'vergi_no' => $veri['vergi_no'],
            'sorumlu_mudur' => $veri['sorumlu_mudur'] ?? null,
            'iletisim_eposta' => $veri['iletisim_eposta'],
            'adres' => $veri['adres'] ?? null,
            'not' => $veri['not'] ?? null,
            'onaylar' => YasalMetinler::onayKaydi(),
            'ip' => request()->ip(),
        ]);

        Notification::make()
            ->title('Başvurunuz alındı')
            ->body('İncelendikten sonra hesabınız açılacak ve giriş bilgileriniz size iletilecek.')
            ->success()
            ->persistent()
            ->send();

        $this->redirect(filament()->getLoginUrl());
    }
}
