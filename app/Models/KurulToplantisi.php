<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İSG Kurulu toplantısı — isgpratik yardım/kurul-toplantisi rehberi.
 */
class KurulToplantisi extends Model
{
    use HasFactory;

    protected $table = 'kurul_toplantilari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'katilimcilar' => 'array',
        'gundem' => 'array',
        'kararlar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function katilanSayisi(): int
    {
        return collect($this->katilimcilar ?? [])->where('katildi', true)->count();
    }
}
