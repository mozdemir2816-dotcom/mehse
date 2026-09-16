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
        'boyut' => 'integer',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.talimat.kategoriler.'.$this->kategori, (string) $this->kategori);
    }

    public function dosyaVarMi(): bool
    {
        return (bool) $this->dosya_yolu;
    }

    public function boyutEtiketi(): string
    {
        if (! $this->boyut) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
