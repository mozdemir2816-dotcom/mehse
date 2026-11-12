<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Operatörün günlük (kullanım öncesi) kontrolü — QR operatör ekranından ya da panelden (12.11.2026).
 * cevaplar: [{soru, cevap: U|UD|Y, not}] — doldurulduğu andaki sorularla saklanır.
 */
class IsEkipmaniGunlukKontrolu extends Model
{
    protected $table = 'is_ekipmani_gunluk_kontrolleri';

    protected $guarded = ['id'];

    protected $casts = [
        'kontrol_zamani' => 'datetime',
        'cevaplar' => 'array',
        'uygunsuz_sayisi' => 'integer',
        'kullanima_uygun' => 'boolean',
    ];

    public const CEVAPLAR = ['U' => 'Uygun', 'UD' => 'Uygun Değil', 'Y' => 'Uygulanamaz'];

    public function ekipman(): BelongsTo
    {
        return $this->belongsTo(IsEkipmani::class, 'is_ekipmani_id');
    }

    public function bakim(): HasOne
    {
        return $this->hasOne(IsEkipmaniBakimi::class, 'gunluk_kontrol_id');
    }
}
