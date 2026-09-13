<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\User;
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

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'kullanici-yonetimi';

    protected static ?string $title = 'Kullanıcı Yetkileri';

    public ?int $acikKullaniciId = null;

    /** @var array<string,bool> */
    public array $sayfaSecim = [];

    /** @var array<int,bool> */
    public array $firmaSecim = [];

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

    public function aktifDegistir(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['aktif' => ! $user->aktif]);
        unset($this->kullanicilar);
    }
}
