<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KKD Zimmet Formu — isgpratik 71-75.jpg.
 */
class KkdZimmetFormu extends Model
{
    use HasFactory;

    protected $table = 'kkd_zimmet_formlari';

    protected $guarded = ['id'];

    protected $casts = [
        'teslim_tarihi' => 'date',
        'periyodik_kontrol_tarihi' => 'date',
        'calisanlar' => 'array',
        'kkdler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (KkdZimmetFormu $f): void {
            $f->form_no ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'form_no', 'KKD-'.now()->format('Y').'-', 3);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
