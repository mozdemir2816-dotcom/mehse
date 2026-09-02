<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DÖF Oluştur (Çoklu DÖF) raporu — isgpratik 158.jpg.
 */
class DofRaporu extends Model
{
    protected $table = 'dof_raporlari';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'maddeler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (DofRaporu $d): void {
            $d->belge_no ??= 'DOF-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function oncelikEtiketi(?string $anahtar): string
    {
        return config('isg.dof.oncelikler.'.$anahtar, $anahtar ?? '—');
    }

    public static function durumEtiketi(?string $anahtar): string
    {
        return config('isg.dof.durumlar.'.$anahtar, $anahtar ?? '—');
    }
}
