<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function firmalar(): HasMany
    {
        return $this->hasMany(Firma::class);
    }

    public function unvanEtiketi(): string
    {
        return config('isg.uzman_unvanlari.'.$this->unvan, $this->unvan ?: '—');
    }

    /** Aboneliğin bitişine kalan gün (negatifse süresi geçmiş). */
    public function abonelikKalanGun(): ?int
    {
        return $this->abonelik_bitis
            ? (int) now()->startOfDay()->diffInDays($this->abonelik_bitis, false)
            : null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sertifika_gecerlilik' => 'date',
            'abonelik_baslangic' => 'date',
            'abonelik_bitis' => 'date',
        ];
    }
}
