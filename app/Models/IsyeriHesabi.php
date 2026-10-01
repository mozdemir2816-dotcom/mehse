<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * İşyeri (işveren) girişi — firma başına tek kalıcı hesap. /isyeri panelinde
 * yalnız kendi firmasının evraklarını salt-okunur görür. Şifreyi İSG uzmanı
 * üretir ve işverene iletir; uzman "Şifreyi Sıfırla" demedikçe değişmez.
 */
class IsyeriHesabi extends Model implements AuthenticatableContract, FilamentUser, HasName
{
    use Authenticatable;

    protected $table = 'isyeri_hesaplari';

    protected $guarded = ['id'];

    protected $hidden = ['sifre', 'sifre_acik', 'remember_token'];

    protected $casts = [
        'sifre' => 'hashed',
        'sifre_acik' => 'encrypted',
        'aktif' => 'boolean',
        'son_giris_at' => 'datetime',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Firmanın hesabını getirir, yoksa yeni e-posta + şifre ile oluşturur. */
    public static function firmaIcin(Firma $firma): self
    {
        return static::firstOrCreate(
            ['firma_id' => $firma->id],
            static::yeniKimlik($firma),
        );
    }

    public function sifreyiSifirla(): string
    {
        $sifre = static::sifreUret();
        $this->update(['sifre' => $sifre, 'sifre_acik' => $sifre, 'remember_token' => null]);

        return $sifre;
    }

    /** @return array{eposta: string, sifre: string, sifre_acik: string, aktif: bool} */
    private static function yeniKimlik(Firma $firma): array
    {
        $sifre = static::sifreUret();

        return [
            'eposta' => 'isyeri.'.$firma->id.'@giris.mehse.com',
            'sifre' => $sifre,
            'sifre_acik' => $sifre,
            'aktif' => true,
        ];
    }

    /** Okunması kolay 12 karakter — karışan harfler (0/O, 1/l/I) yok. */
    public static function sifreUret(): string
    {
        $harfler = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, 12))
            ->map(fn () => $harfler[random_int(0, strlen($harfler) - 1)])
            ->implode('');
    }

    // --- Auth -----------------------------------------------------------------

    public function getAuthPasswordName(): string
    {
        return 'sifre';
    }

    public function getAuthPassword(): ?string
    {
        return $this->sifre;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'isyeri'
            && $this->aktif
            && (bool) $this->firma?->aktif;
    }

    public function getFilamentName(): string
    {
        return $this->firma?->kisa_ad ?: ($this->firma?->unvan ?? 'İşyeri');
    }
}
