<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aday Firma — Profilim > Pazarlama (isgpratik 143.jpg). Görüşme aşamasındaki
 * potansiyel müşteri; resmi sözleşmeye kadar takip edilir.
 */
class AdayFirma extends Model
{
    protected $table = 'aday_firmalar';

    protected $guarded = ['id'];

    protected $casts = [
        'hatirlatma_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (AdayFirma $a): void {
            if (blank($a->user_id) && auth()->check()) {
                $a->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asamaEtiketi(): string
    {
        return config('isg.pazarlama.asamalar.'.$this->asama, $this->asama);
    }

    public function acikHatirlatmasiMi(): bool
    {
        return $this->hatirlatma_tarihi
            && $this->hatirlatma_tarihi->isPast()
            && ! in_array($this->asama, ['kazanildi', 'kaybedildi'], true);
    }
}
