<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsgAfis extends Model
{
    protected $table = 'isg_afisleri';

    protected $guarded = ['id'];

    protected $casts = [
        'boyut' => 'integer',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.afis.kategoriler.'.$this->kategori, $this->kategori);
    }

    public function boyutEtiketi(): string
    {
        if ($this->boyut <= 0) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }

    public function gorselMi(): bool
    {
        return in_array(strtolower(pathinfo($this->dosya_adi, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
