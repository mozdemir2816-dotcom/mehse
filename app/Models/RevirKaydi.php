<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revir / Poliklinik Başvuru & Protokol Defteri kaydı (K1).
 */
class RevirKaydi extends Model
{
    protected $table = 'revir_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'datetime',
        'istirahat_gun' => 'integer',
    ];

    public const SONUCLAR = [
        'ayakta' => 'Ayakta Müdahale / İşe Döndü',
        'istirahat' => 'İstirahat Verildi',
        'sevk' => 'Sağlık Kuruluşuna Sevk',
        'hastane' => 'Acil / Hastane Nakli',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r): void {
            $r->protokol_no ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'protokol_no', 'PR-'.now()->format('Y').'-', 4);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function sonucEtiketi(): string
    {
        return self::SONUCLAR[$this->sonuc] ?? (string) $this->sonuc;
    }
}
