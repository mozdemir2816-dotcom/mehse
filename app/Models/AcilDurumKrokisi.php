<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acil Durum / Tahliye Krokisi — firma başına bir kayıt. Duvar çizgileri +
 * yerleştirilen semboller JSON tutulur; `AcilDurumKrokisiUretici` hem editördeki
 * SVG'yi hem PDF çıktısını AYNI koordinatlardan üretir.
 */
class AcilDurumKrokisi extends Model
{
    protected $table = 'acil_durum_krokileri';

    protected $guarded = ['id'];

    protected $casts = [
        'duvarlar' => 'array',
        'semboller' => 'array',
        'hazirlanma_tarihi' => 'date',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kroki = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kroki->exists) {
            $kroki->hazirlanma_tarihi = now();
            $kroki->duvarlar = [];
            $kroki->semboller = [];
            $kroki->save();
        }

        return $kroki;
    }

    /** Sembol tiplerinin adet dağılımı — lejant için. */
    public function lejant(): array
    {
        $etiketler = config('isg.kroki.semboller');

        return collect($this->semboller ?? [])
            ->groupBy('tip')
            ->map(fn ($grup, $tip) => [
                'tip' => $tip,
                'ad' => $etiketler[$tip]['ad'] ?? $tip,
                'renk' => $etiketler[$tip]['renk'] ?? '#666',
                'adet' => $grup->count(),
            ])
            ->values()
            ->all();
    }
}
