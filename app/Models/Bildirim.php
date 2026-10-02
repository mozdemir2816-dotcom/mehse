<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

/** Bildirim Merkezi kaydı — bkz. App\Support\BildirimTarayici. */
class Bildirim extends Model
{
    protected $table = 'bildirimler';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'okundu_at' => 'datetime',
        'cozuldu_at' => 'datetime',
    ];

    public const SEVIYELER = [
        'kritik' => 'Kritik',
        'uyari' => 'Uyarı',
        'bilgi' => 'Bilgi',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function scopeAcik(Builder $q): Builder
    {
        return $q->whereNull('cozuldu_at');
    }

    /**
     * Üst menü zili: kullanıcının açık ve okunmamış bildirim sayısı. Her panel
     * sayfasında çalıştığı için tablo henüz yoksa (canlıya SQL eksik) sayfayı
     * düşürmez, 0 döner.
     */
    public static function okunmamisSayisi(int $userId): int
    {
        try {
            return static::query()->where('user_id', $userId)->acik()->whereNull('okundu_at')->count();
        } catch (QueryException) {
            return 0;
        }
    }

    public function seviyeEtiketi(): string
    {
        return static::SEVIYELER[$this->seviye] ?? $this->seviye;
    }
}
