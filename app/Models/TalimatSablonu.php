<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzmanın kendi arşivinden Excel ile toplu yüklediği talimat şablonu —
 * config'teki 30 hazır şablonun yanında Talimat Oluştur kütüphanesinde
 * ayrıca listelenir.
 */
class TalimatSablonu extends Model
{
    use HasFactory;

    protected $table = 'talimat_sablonlari';

    protected $guarded = ['id'];

    protected $casts = [
        'kkdler' => 'array',
        'maddeler' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.talimat.kategoriler.'.$this->kategori, (string) ($this->kategori ?: 'Genel İSG'));
    }
}
