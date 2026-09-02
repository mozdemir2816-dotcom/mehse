<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Saha Analizi → İSG Saha Gözetim Raporu — isgpratik AI SAHA ANALİZİ/1-6.jpg.
 */
class SahaAnalizi extends Model
{
    protected $table = 'saha_analizleri';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'bulgular' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (SahaAnalizi $s): void {
            $s->belge_no ??= 'SAHA-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function riskDerecesiEtiketi(?int $derece): string
    {
        return config('isg.saha_analiz.risk_dereceleri.'.$derece, (string) $derece);
    }
}
