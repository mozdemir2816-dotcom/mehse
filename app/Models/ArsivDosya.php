<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arşiv Dosyası — Profilim > Arşiv (isgpratik 144.jpg). Firma başına düz
 * dosya listesi (klasör yok).
 */
class ArsivDosya extends Model
{
    protected $table = 'arsiv_dosyalari';

    protected $guarded = ['id'];

    protected $casts = [
        'boyut' => 'integer',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function boyutEtiketi(): string
    {
        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
