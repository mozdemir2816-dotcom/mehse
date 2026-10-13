<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Telefon bildirimi aboneliği (bir kullanıcının bir cihazı) — bkz. App\Support\WebPush. */
class PushAbonelik extends Model
{
    protected $table = 'push_abonelikleri';

    protected $guarded = ['id'];

    protected $casts = [
        'son_basari_at' => 'datetime',
        'hata_sayisi' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Tarayıcının PushSubscription.toJSON() çıktısından kaydeder (aynı cihaz tekrar gelirse günceller). */
    public static function kaydet(int $userId, array $abonelik, ?string $cihaz = null): self
    {
        return static::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $abonelik['endpoint'])],
            [
                'user_id' => $userId,
                'endpoint' => $abonelik['endpoint'],
                'p256dh' => $abonelik['keys']['p256dh'],
                'auth' => $abonelik['keys']['auth'],
                'cihaz' => $cihaz ? mb_substr($cihaz, 0, 120) : null,
                'hata_sayisi' => 0,
            ],
        );
    }
}
