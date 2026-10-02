<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Taşeron belgesi — dosya `public` diskte taseron/{firma_id}/ altında. */
class TaseronBelgesi extends Model
{
    protected $table = 'taseron_belgeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'gecerlilik_sonu' => 'date',
        'boyut' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TaseronBelgesi $b): void {
            if ($b->dosya_yolu && Storage::disk('public')->exists($b->dosya_yolu)) {
                Storage::disk('public')->delete($b->dosya_yolu);
            }
        });
    }

    public function taseron(): BelongsTo
    {
        return $this->belongsTo(Taseron::class);
    }

    public function turEtiketi(): string
    {
        return config('isg.taseron.belge_turleri.'.$this->tur.'.ad', $this->tur);
    }

    public function etiket(): string
    {
        return $this->baslik ?: $this->turEtiketi();
    }

    /** suresiz | gecerli | yaklasan | dolmus */
    public function durum(): string
    {
        if (! $this->gecerlilik_sonu) {
            return 'suresiz';
        }

        $kalan = (int) now()->startOfDay()->diffInDays($this->gecerlilik_sonu, false);

        return match (true) {
            $kalan < 0 => 'dolmus',
            $kalan <= config('isg.taseron.yaklasan_gun', 30) => 'yaklasan',
            default => 'gecerli',
        };
    }
}
