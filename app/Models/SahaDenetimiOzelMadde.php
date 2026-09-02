<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saha Denetimi — kullanıcının kendi eklediği sektöre özel kontrol maddesi
 * (isg.saha_denetimi.kategoriler sabit listesinin üzerine eklenir).
 */
class SahaDenetimiOzelMadde extends Model
{
    protected $table = 'saha_denetimi_ozel_maddeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'kritik' => 'boolean',
        'uygulanamaz_izni' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (SahaDenetimiOzelMadde $m): void {
            if (blank($m->user_id) && auth()->check()) {
                $m->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sektorEtiketi(): string
    {
        return $this->sektor_anahtari
            ? config('isg.risk_ai.sektorler.'.$this->sektor_anahtari.'.ad', $this->sektor_anahtari)
            : 'Tüm Sektörler';
    }
}
