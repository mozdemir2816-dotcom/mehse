<?php

namespace App\Models;

use App\Support\OzelEkipmanTipleri;
use Illuminate\Database\Eloquent\Model;

/**
 * Toplu Makine Paketi ile eklenen makine tipi (App\Support\OzelEkipmanTipleri).
 *
 * @property array<int, string>|null $kontrol_sorulari
 */
class OzelEkipmanTipi extends Model
{
    protected $table = 'ozel_ekipman_tipleri';

    protected $guarded = ['id'];

    protected $casts = [
        'kontrol_sorulari' => 'array',
        'yz_taslak' => 'boolean',
        'onaylandi_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Config'e eklenen liste önbellekte; tip değişince yenilenir.
        static::saved(fn () => OzelEkipmanTipleri::onbellegiTemizle());
        static::deleted(fn () => OzelEkipmanTipleri::onbellegiTemizle());
    }

    public function onayBekliyorMu(): bool
    {
        return $this->yz_taslak && $this->onaylandi_at === null;
    }
}
