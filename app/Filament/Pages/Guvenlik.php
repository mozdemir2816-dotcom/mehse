<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\YasalMetinler;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Güvenlik — isgsuite "Güvenlik ve Denetim" karşılaştırması: şifre değiştirme
 * (mevcut şifre şart, en az 10 karakter), tüm cihazlardan çıkış, aktif
 * oturumlar, iki adımlı doğrulama durumu (kurulum Filament profil sayfasında),
 * sürüm bazlı yasal metin onayları. Herkes yalnız kendi hesabını yönetir —
 * yetki kısıtı (SinirliErisim) bilinçli olarak yok.
 */
class Guvenlik extends Page
{
    protected string $view = 'filament.pages.guvenlik';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'guvenlik';

    protected static ?string $title = 'Güvenlik';

    /** @var array<string, mixed>|null */
    public ?array $sifre = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('sifre')
            ->components([
                TextInput::make('mevcut')->label('Mevcut şifre')
                    ->password()->revealable()->required()->currentPassword()
                    ->autocomplete('current-password'),
                TextInput::make('yeni')->label('Yeni şifre')
                    ->password()->revealable()->required()
                    ->rule(Password::default())
                    ->different('mevcut')
                    ->helperText('En az 10 karakter.')
                    ->autocomplete('new-password'),
                TextInput::make('yeni_tekrar')->label('Yeni şifre (tekrar)')
                    ->password()->revealable()->required()->same('yeni')
                    ->validationMessages(['same' => 'Şifreler aynı değil.'])
                    ->autocomplete('new-password'),
            ]);
    }

    public function sifreDegistir(): void
    {
        $veri = $this->form->getState();
        $kullanici = $this->kullanici();

        $kullanici->update(['password' => $veri['yeni']]);
        $this->oturumSifresiniTazele($kullanici);

        // Diğer cihazlardaki oturumlar AuthenticateSession ile (şifre özeti değişti)
        // bir sonraki istekte düşer; veritabanı oturumlarını da hemen temizle.
        $this->digerOturumlariSil($kullanici);

        $this->form->fill();
        Notification::make()->title('Şifreniz değiştirildi')->body('Diğer cihazlardaki oturumlar kapatıldı.')->success()->send();
    }

    public function tumCihazlardanCikisAction(): Action
    {
        return Action::make('tumCihazlardanCikis')
            ->label('Tüm cihazlardan çıkış')
            ->icon('heroicon-o-arrow-right-start-on-rectangle')
            ->color('gray')
            ->modalHeading('Diğer tüm cihazlardan çıkış')
            ->modalDescription('Bu cihaz dışındaki tüm oturumlarınız kapatılır. Onaylamak için şifrenizi girin.')
            ->schema([
                TextInput::make('sifre')->label('Şifreniz')->password()->revealable()->required()->currentPassword(),
            ])
            ->modalSubmitActionLabel('Diğer oturumları kapat')
            ->action(function (array $data): void {
                $kullanici = $this->kullanici();

                Filament::auth()->logoutOtherDevices($data['sifre']);
                $this->oturumSifresiniTazele($kullanici->fresh());
                $silinen = $this->digerOturumlariSil($kullanici);

                unset($this->oturumlar);
                Notification::make()->title('Diğer cihazlardan çıkış yapıldı')
                    ->body($silinen ? $silinen.' oturum kapatıldı.' : 'Başka açık oturum yoktu.')
                    ->success()->send();
            });
    }

    public function yasalOnayla(string $anahtar): void
    {
        if (YasalMetinler::onayla($this->kullanici(), $anahtar)) {
            Notification::make()->title('Onayınız kaydedildi')->success()->send();
        }
    }

    /**
     * Veritabanı oturumları (SESSION_DRIVER=database) — başka sürücüde boş.
     *
     * @return array<int, array{bu_cihaz: bool, ip: ?string, cihaz: string, son: Carbon}>
     */
    #[Computed]
    public function oturumlar(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->kullanici()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($o) => [
                'bu_cihaz' => $o->id === static::buOturumId(),
                'ip' => $o->ip_address,
                'cihaz' => static::cihazAdi((string) $o->user_agent),
                'son' => Carbon::createFromTimestamp($o->last_activity),
            ])
            ->all();
    }

    /** @return array<string, array{baslik: string, revizyon: ?string, onay: ?array, guncel: bool}> */
    #[Computed]
    public function yasalMetinler(): array
    {
        $kullanici = $this->kullanici();

        return collect(YasalMetinler::aktifler())
            ->map(fn (array $m, string $anahtar) => [
                'baslik' => $m['baslik'],
                'revizyon' => $m['revizyon'] ?? null,
                'onay' => YasalMetinler::sonOnay($kullanici, $anahtar),
                'guncel' => YasalMetinler::guncelOnayliMi($kullanici, $anahtar),
            ])
            ->all();
    }

    /** User-Agent'tan kaba "Tarayıcı · İşletim sistemi" etiketi. */
    public static function cihazAdi(string $ua): string
    {
        $tarayici = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Tarayıcı',
        };
        $sistem = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'bilinmeyen sistem',
        };

        return $tarayici.' · '.$sistem;
    }

    public function kullanici(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }

    /** Şifre özeti değişince bu oturum düşmesin (AuthenticateSession karşılaştırması). */
    private function oturumSifresiniTazele(User $kullanici): void
    {
        if (request()->hasSession()) {
            request()->session()->put('password_hash_'.Filament::getAuthGuard(), $kullanici->getAuthPassword());
        }
    }

    private function digerOturumlariSil(User $kullanici): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $kullanici->getAuthIdentifier())
            ->when(static::buOturumId(), fn ($q, $id) => $q->where('id', '!=', $id))
            ->delete();
    }

    private static function buOturumId(): ?string
    {
        return request()->hasSession() ? request()->session()->getId() : null;
    }
}
