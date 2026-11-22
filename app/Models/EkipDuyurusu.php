<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OSGB Ekip İçi Duyuru / Talimat — okundu ve onaylandı takibi (F6).
 */
class EkipDuyurusu extends Model
{
    protected $table = 'ekip_duyurulari';

    protected $guarded = ['id'];

    protected $casts = [
        'okuyanlar' => 'array',
    ];

    public const ONCELIKLER = [
        'normal' => 'Normal',
        'onemli' => 'Önemli',
        'acil' => 'Acil',
    ];

    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function okuduMu(int $userId): bool
    {
        return isset(($this->okuyanlar ?? [])[(string) $userId]);
    }

    public function okunduIsaretle(User $user): void
    {
        $liste = (array) ($this->okuyanlar ?? []);
        $anahtar = (string) $user->id;
        if (! isset($liste[$anahtar])) {
            $liste[$anahtar] = [
                'ad' => (string) $user->name,
                'okundu_at' => now()->toIso8601String(),
            ];
            $this->update(['okuyanlar' => $liste]);
        }
    }

    public function okuyanSayisi(): int
    {
        return count((array) ($this->okuyanlar ?? []));
    }
}
