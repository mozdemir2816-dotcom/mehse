<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş İzin Şablonu — uzmanın kendi izin kütüphanesi kaydı. config'teki hazır
 * izin kataloğuyla ("isg.is_izin.kutuphane") aynı şekilde İş İzin Formu
 * ekranında "Kütüphaneden Uygula" ile seçilir.
 */
class IsIzinSablonu extends Model
{
    protected $table = 'is_izin_sablonlari';

    protected $guarded = ['id'];

    protected $casts = [
        'turler' => 'array',
        'ek_onlemler' => 'array',
        'kkdler' => 'array',
        'uyarilar' => 'array',
        'gecerlilik_saat' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<int, string> */
    public function turEtiketleri(): array
    {
        return collect($this->turler ?? [])
            ->map(fn ($t) => config('isg.is_izin.turler.'.$t, $t))
            ->all();
    }
}
