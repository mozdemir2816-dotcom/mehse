<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İşverene İdari Para Cezası (İPC) Tebliği — Ceza ve Tebliğ Tutanağı'nın
 * 2. sekmesi (isgpratik 79-80.jpg).
 */
class IpcTebligi extends Model
{
    protected $table = 'ipc_tebligleri';

    protected $guarded = ['id'];

    protected $casts = [
        'teblig_tarihi' => 'date',
        'denetim_tarihi' => 'date',
        'ihlaller' => 'array',
        'ceza_tutari' => 'decimal:2',
        'pesin_odeme_tutari' => 'decimal:2',
        'odeme_yapildi' => 'boolean',
        'itiraz_edildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (IpcTebligi $t): void {
            $t->belge_no ??= 'IPC-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);

            if ($t->ceza_tutari !== null) {
                $t->pesin_odeme_tutari = round((float) $t->ceza_tutari * 0.75, 2);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
