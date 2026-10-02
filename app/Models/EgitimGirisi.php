<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzaktan eğitim giriş günlüğü — portal girişi (atama boş) ya da bir
 * eğitimin açılması (atama dolu).
 */
class EgitimGirisi extends Model
{
    protected $table = 'egitim_girisleri';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'giris_at' => 'datetime',
    ];

    /** Aynı eğitim kısa süre içinde yeniden açılırsa (sayfa yenileme) yeni satır yazılmaz. */
    public const TEKRAR_DK = 30;

    public static function kaydet(int $calisanId, ?int $atamaId = null): ?self
    {
        $yakin = static::query()
            ->where('calisan_id', $calisanId)
            ->where('egitim_atamasi_id', $atamaId)
            ->where('giris_at', '>=', now()->subMinutes(static::TEKRAR_DK))
            ->exists();

        if ($yakin) {
            return null;
        }

        return static::create([
            'calisan_id' => $calisanId,
            'egitim_atamasi_id' => $atamaId,
            'ip' => request()->ip(),
            'giris_at' => now(),
        ]);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function atama(): BelongsTo
    {
        return $this->belongsTo(EgitimAtamasi::class, 'egitim_atamasi_id');
    }
}
