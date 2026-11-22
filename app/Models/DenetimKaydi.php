<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DenetimKaydi extends Model
{
    public $timestamps = false;

    protected $table = 'denetim_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const ISLEMLER = [
        'olusturdu' => 'Oluşturdu',
        'guncelledi' => 'Güncelledi',
        'sildi' => 'Sildi',
        'kilitledi' => 'Kilitledi',
        'onayladi' => 'Onayladı',
        'indirdi' => 'İndirdi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
