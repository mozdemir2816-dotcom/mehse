<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Support\SayfaKatalogu;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Sahip hesabın diğer kullanıcılara (yeni kayıt olanlar dahil) hangi sayfalara ve
 * hangi firmalara erişebileceğini tek tek belirlediği ekran. Sadece sahip görür —
 * SinirliErisim trait'i KASITLI olarak kullanılmıyor, canAccess() doğrudan sahipMi()
 * kontrol eder (genel yetki sistemine bağlı olmasın, ayrıcalık yükseltme riski olmasın).
 */
class KullaniciYonetimi extends Page
{
    protected string $view = 'filament.pages.kullanici-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — Sistem & Ayarlar Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'kullanici-yonetimi';

    protected static ?string $title = 'Kullanıcı Yetkileri';

    public ?int $acikKullaniciId = null;

    /** @var array<string,bool> */
    public array $sayfaSecim = [];

    /** @var array<int,bool> */
    public array $firmaSecim = [];

    /** Abonelik / deneme süresi — boş bitiş = süresiz (App\Http\Middleware\CheckTrialPeriod). */
    public ?string $abonelikPlani = null;

    public ?string $abonelikBitis = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    #[Computed]
    public function kullanicilar()
    {
        return User::where('rol', '!=', 'sahip')->orderBy('name')->get();
    }

    #[Computed]
    public function sayfaKatalogu(): array
    {
        return SayfaKatalogu::tumSayfalar();
    }

    #[Computed]
    public function firmalar()
    {
        return Firma::withoutGlobalScopes()->orderBy('unvan')->get();
    }

    public function ac(int $userId): void
    {
        if ($this->acikKullaniciId === $userId) {
            $this->acikKullaniciId = null;

            return;
        }

        $user = User::findOrFail($userId);

        $this->acikKullaniciId = $userId;
        $this->sayfaSecim = array_fill_keys(array_keys($this->sayfaKatalogu()), false);
        foreach ($user->sayfaYetkileri()->pluck('sayfa_anahtari') as $anahtar) {
            $this->sayfaSecim[$anahtar] = true;
        }

        $this->abonelikPlani = $user->abonelik_plani;
        $this->abonelikBitis = $user->abonelik_bitis?->toDateString();

        $this->firmaSecim = [];
        $sahipOlunan = DB::table('firmalar')->where('user_id', $userId)->pluck('id');
        $paylasilan = DB::table('kullanici_firmalar')->where('user_id', $userId)->pluck('firma_id');
        foreach ($sahipOlunan->merge($paylasilan)->unique() as $firmaId) {
            $this->firmaSecim[$firmaId] = true;
        }
    }

    public function tumSayfalariSec(): void
    {
        $this->sayfaSecim = array_fill_keys(array_keys($this->sayfaKatalogu()), true);
    }

    public function tumFirmalariSec(): void
    {
        $this->firmaSecim = array_fill_keys($this->firmalar()->pluck('id')->all(), true);
    }

    public function kaydet(): void
    {
        if (! $this->acikKullaniciId) {
            return;
        }

        $user = User::findOrFail($this->acikKullaniciId);

        $this->validate([
            'abonelikPlani' => ['required', 'in:'.implode(',', array_keys(User::ABONELIK_PLANLARI))],
            'abonelikBitis' => ['nullable', 'date'],
        ], attributes: ['abonelikPlani' => 'abonelik planı', 'abonelikBitis' => 'bitiş tarihi']);

        $user->update([
            'abonelik_plani' => $this->abonelikPlani,
            'abonelik_bitis' => $this->abonelikBitis ?: null,
        ]);

        $secilenSayfalar = array_keys(array_filter($this->sayfaSecim));
        $user->sayfaYetkileri()->delete();
        $user->sayfaYetkileri()->createMany(
            collect($secilenSayfalar)->map(fn (string $anahtar) => ['sayfa_anahtari' => $anahtar])->all()
        );

        // Sahip olduğu (firmalar.user_id) firmalara ek paylaşım satırı açmaya gerek yok,
        // sadece kendi SAHİBİ OLMADIĞI ama seçilen firmalar için paylaşım kaydı tutulur.
        $kendiFirmaIdleri = DB::table('firmalar')->where('user_id', $user->id)->pluck('id')->all();
        $secilenFirmaIdleri = array_keys(array_filter($this->firmaSecim));
        $paylasimIdleri = array_values(array_diff($secilenFirmaIdleri, $kendiFirmaIdleri));

        DB::table('kullanici_firmalar')->where('user_id', $user->id)->delete();
        if ($paylasimIdleri !== []) {
            DB::table('kullanici_firmalar')->insert(array_map(fn ($firmaId) => [
                'user_id' => $user->id,
                'firma_id' => $firmaId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $paylasimIdleri));
        }

        unset($this->kullanicilar);

        Notification::make()
            ->title($user->name.' için yetkiler kaydedildi')
            ->success()
            ->send();
    }

    /** Bitişi mevcut bitişten (gelecekteyse) ya da bugünden uzatır; "Kaydet" ile yazılır. */
    public function abonelikUzat(int $gun): void
    {
        $bitis = $this->abonelikBitis ? Carbon::parse($this->abonelikBitis) : null;
        $taban = $bitis && $bitis->isFuture() ? $bitis : today();

        $this->abonelikBitis = $taban->addDays($gun)->toDateString();
    }

    public function aktifDegistir(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['aktif' => ! $user->aktif]);
        unset($this->kullanicilar);
    }
}
