<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanliEgitimOturumu extends Model
{
    protected $table = 'canli_egitim_oturumlari';

    protected $guarded = ['id'];

    protected $casts = [
        'oturum_tarihi' => 'datetime',
        'sorular' => 'array',
        'sonuclar' => 'array',
        'aktif' => 'boolean',
    ];

    public const KATEGORILER = [
        'temel_isg' => ['ad' => 'Temel İSG Eğitimi (Canlı Oturum)', 'saat' => 8],
        'yuksekte_8s' => ['ad' => 'Yüksekte Çalışma Eğitimi (8 Saat)', 'saat' => 8],
        'isyeri_hijyeni_4s' => ['ad' => 'İşyeri Hijyeni Eğitimi (4 Saat)', 'saat' => 4],
        'gida_hijyeni_4s' => ['ad' => 'Gıda ve Su Hijyeni Eğitimi (4 Saat)', 'saat' => 4],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function kategoriEtiketi(): string
    {
        return self::KATEGORILER[$this->kategori]['ad'] ?? $this->kategori;
    }
}
