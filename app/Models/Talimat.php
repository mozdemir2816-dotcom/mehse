<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışma Talimatı — isgpratik 82-83.jpg.
 */
class Talimat extends Model
{
    use HasFactory;

    protected $table = 'talimatlar';

    protected $guarded = ['id'];

    protected $casts = [
        'kkdler' => 'array',
        'maddeler' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.talimat.kategoriler.'.$this->kategori, (string) $this->kategori);
    }
}
