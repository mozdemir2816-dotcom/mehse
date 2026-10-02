<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Acil durum ekibi (söndürme, kurtarma, koruma, ilk yardım, tahliye,
 * haberleşme…). Asgari üye sayısı girilmemişse tehlike sınıfı ve çalışan
 * sayısından yasal orana göre hesaplanır (config isg.acil_durum.ekip_turleri).
 */
class AcilEkip extends Model
{
    use SoftDeletes;

    protected $table = 'acil_ekipleri';

    protected $guarded = ['id'];

    protected $casts = ['min_uye' => 'integer'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function uyeler(): HasMany
    {
        return $this->hasMany(AcilEkipUyesi::class, 'acil_ekip_id');
    }

    public function turBilgisi(): array
    {
        return config('isg.acil_durum.ekip_turleri.'.$this->tur) ?? config('isg.acil_durum.ekip_turleri.diger');
    }

    public function turEtiketi(): string
    {
        return $this->turBilgisi()['ad'];
    }

    /** Yasal asgari: çalışan sayısı / orana göre yukarı yuvarlanmış, en az 1. */
    public static function yasalMinimum(string $tur, ?string $tehlikeSinifi, int $calisan): int
    {
        $oran = config('isg.acil_durum.ekip_turleri.'.$tur.'.oran');
        $bolen = $oran[$tehlikeSinifi] ?? null;

        return $bolen && $calisan > 0 ? max(1, (int) ceil($calisan / $bolen)) : 1;
    }

    public function minimum(int $calisan): int
    {
        return $this->min_uye ?: static::yasalMinimum($this->tur, $this->firma?->tehlike_sinifi, $calisan);
    }

    public function yasalOranMetni(): ?string
    {
        $oran = $this->turBilgisi()['oran'] ?? null;
        $bolen = $oran[$this->firma?->tehlike_sinifi] ?? null;

        return $bolen ? "her {$bolen} çalışana 1" : null;
    }
}
