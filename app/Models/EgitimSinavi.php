<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eğitim Soruları (sınav) — isgpratik 69-70.jpg.
 */
class EgitimSinavi extends Model
{
    use HasFactory;

    protected $table = 'egitim_sinavlari';

    protected $guarded = ['id'];

    protected $casts = [
        'cevap_anahtari_dahil' => 'boolean',
        'sorular' => 'array',
        'katilimcilar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function sektorEtiketi(): string
    {
        return $this->sektor_anahtari
            ? (config('isg.risk_ai.sektorler.'.$this->sektor_anahtari.'.ad') ?? $this->sektor_anahtari)
            : 'Genel';
    }
}
