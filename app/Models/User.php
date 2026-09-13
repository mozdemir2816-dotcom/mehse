<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sertifika_gecerlilik' => 'date',
            'abonelik_baslangic' => 'date',
            'abonelik_bitis' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->aktif;
    }

    public function firmalar(): HasMany
    {
        return $this->hasMany(Firma::class);
    }

    /** Sahip olmadığı ama ek/paylaşımlı erişim verilmiş firmalar. */
    public function paylasilanFirmalar(): BelongsToMany
    {
        return $this->belongsToMany(Firma::class, 'kullanici_firmalar');
    }

    public function sayfaYetkileri(): HasMany
    {
        return $this->hasMany(SayfaYetkisi::class);
    }

    public function sahipMi(): bool
    {
        return $this->rol === 'sahip';
    }

    /**
     * Bu kullanıcının görebileceği tüm firma id'leri (kendi + paylaşılan).
     * Firma modelinin görünürlük global scope'una bu metod BESLEME kaynağı olduğu için
     * kasıtlı olarak ham DB sorguları kullanır — Firma::query() üzerinden gidip sonsuz
     * döngüye (scope -> bu metod -> scope -> ...) girmemek için.
     */
    public function erisilebilirFirmaIdleri(): \Illuminate\Support\Collection
    {
        if ($this->sahipMi()) {
            return \Illuminate\Support\Facades\DB::table('firmalar')->pluck('id');
        }

        $sahipOlunan = \Illuminate\Support\Facades\DB::table('firmalar')->where('user_id', $this->id)->pluck('id');
        $paylasilan = \Illuminate\Support\Facades\DB::table('kullanici_firmalar')->where('user_id', $this->id)->pluck('firma_id');

        return $sahipOlunan->merge($paylasilan)->unique()->values();
    }

    public function sayfaErisimiVarMi(string $anahtar): bool
    {
        if ($this->sahipMi()) {
            return true;
        }

        return $this->sayfaYetkileri()->where('sayfa_anahtari', $anahtar)->exists();
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
}
