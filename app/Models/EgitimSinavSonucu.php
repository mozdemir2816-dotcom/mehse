<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgitimSinavSonucu extends Model
{
    use HasFactory;

    protected $table = 'egitim_sinav_sonuclari';

    protected $guarded = ['id'];

    protected $casts = [
        'deneme_no' => 'integer',
        'puan' => 'integer',
        'gecti' => 'boolean',
        'cevaplar' => 'array',
        'tamamlandi_at' => 'datetime',
    ];

    public function atama(): BelongsTo
    {
        return $this->belongsTo(EgitimAtamasi::class, 'egitim_atamasi_id');
    }

    public function dogruSayisi(): int
    {
        return collect($this->cevaplar)
            ->filter(fn ($c) => isset($c['verilen_index'], $c['dogru_index']) && (int) $c['verilen_index'] === (int) $c['dogru_index'])
            ->count();
    }

    public function toplamSoru(): int
    {
        return count($this->cevaplar ?? []);
    }
}
