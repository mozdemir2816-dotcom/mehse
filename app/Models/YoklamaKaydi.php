<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Günlük Saha / Şantiye Yoklama & Puantaj kaydı (N2).
 * Hem aylık puantaj raporu hem de acil durum anında "Bugün sahada kimler var?" listesi üretir.
 */
class YoklamaKaydi extends Model
{
    protected $table = 'yoklama_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
    ];

    public const DURUMLAR = [
        'mevcut' => 'Mevcut (Sahada)',
        'izinli' => 'İzinli',
        'raporlu' => 'Raporlu',
        'gorevli' => 'Görevli (Dış Saha)',
        'gelmedi' => 'Gelmedi',
    ];

    public const DURUM_RENK = [
        'mevcut' => '#15803d',
        'izinli' => '#2563eb',
        'raporlu' => '#d97706',
        'gorevli' => '#7c3aed',
        'gelmedi' => '#dc2626',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }
}
