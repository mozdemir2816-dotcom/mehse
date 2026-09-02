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
            $f->form_no ??= 'KKD-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
