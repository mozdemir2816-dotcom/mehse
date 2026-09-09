<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * Çalışan (personel) — bir firmaya bağlı. Uzaktan Eğitim portalına (panel: portal)
 * e-posta + şifre ile girer, yalnız kendisine atanmış eğitimleri görür. Şifre İSG
 * uzmanı tarafından atama sırasında üretilir (`sifre` sütunu, `password` değil).
 */
class Calisan extends Model implements AuthenticatableContract, FilamentUser
{
    use Authenticatable;
    use Authorizable;
    use HasFactory;

    protected $table = 'calisanlar';

    protected $guarded = ['id'];

    protected $hidden = ['sifre', 'remember_token'];

    protected $casts = [
        'ise_giris' => 'date',
        'isten_cikis' => 'date',
        'dogum_tarihi' => 'date',
        'agir_tehlikeli_iste' => 'boolean',
        'aktif' => 'boolean',
        'sifre_belirlendi_at' => 'datetime',
        'sifre' => 'hashed',
    ];

    // --- Portal auth --------------------------------------------------------

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
        return $panel->getId() === 'portal'
            && (bool) $this->aktif
            && filled($this->sifre)
            && $this->egitimAtamalari()->exists();
    }

    public function getFilamentName(): string
    {
        return $this->ad_soyad;
    }

    // --- İlişkiler ---------------------------------------------------------

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function egitimKayitlari(): HasMany
    {
        return $this->hasMany(EgitimKaydi::class);
    }

    public function egitimAtamalari(): HasMany
    {
        return $this->hasMany(EgitimAtamasi::class);
    }
}
