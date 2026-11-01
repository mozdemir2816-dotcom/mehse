<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
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
            'ayarlar' => 'array',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'yasal_onaylar' => 'array',
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

    /** Kullanıcı Yetkileri ekranındaki abonelik planı seçenekleri (users.abonelik_plani). */
    public const ABONELIK_PLANLARI = [
        'deneme' => 'Deneme',
        'uzman' => 'Bireysel Uzman',
        'osgb' => 'Profesyonel OSGB',
        'kurumsal' => 'Kurumsal & Sanayi',
    ];

    /** Fatura & Profil sayfası (users.fatura_tipi). */
    public const FATURA_TIPLERI = [
        'bireysel' => 'Bireysel (şahıs)',
        'kurumsal' => 'Kurumsal (şirket)',
    ];

    /** Fatura bilgileri eksiksiz girilmiş mi (ad/ünvan, vergi/TC no, adres, il, ilçe). */
    public function faturaBilgisiTamMi(): bool
    {
        return filled($this->fatura_tipi) && filled($this->fatura_unvan) && filled($this->vergi_no)
            && filled($this->fatura_adresi) && filled($this->fatura_il) && filled($this->fatura_ilce)
            && ($this->fatura_tipi !== 'kurumsal' || filled($this->vergi_dairesi));
    }

    /**
     * Panel erişimi açık mı (App\Http\Middleware\CheckTrialPeriod). Sahip hesap ve
     * bitiş tarihi olmayan hesaplar muaf; bitiş günü dahil erişim sürer.
     */
    public function hasActiveTrial(): bool
    {
        if ($this->sahipMi() || ! $this->abonelik_bitis) {
            return true;
        }

        return $this->abonelikKalanGun() >= 0;
    }

    public function trialDaysRemaining(): ?int
    {
        return $this->abonelikKalanGun();
    }

    /** Panel üstündeki sarı uyarı bandı: bitişe 7 gün veya daha az kaldıysa. */
    public function shouldShowTrialWarning(): bool
    {
        $kalan = $this->abonelikKalanGun();

        return ! $this->sahipMi() && $kalan !== null && $kalan >= 0 && $kalan <= 7;
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
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    // --- İki adımlı doğrulama (Filament uygulama tabanlı TOTP) -----------------

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[\SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /** @return array<string>|null */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /** @param  array<string>|null  $codes */
    public function saveAppAuthenticationRecoveryCodes(#[\SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    public function mfaAcikMi(): bool
    {
        return filled($this->app_authentication_secret);
    }
}
