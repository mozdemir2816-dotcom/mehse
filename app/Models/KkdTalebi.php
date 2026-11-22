<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışan Portalından gelen dijital KKD talebi (K5).
 */
class KkdTalebi extends Model
{
    protected $table = 'kkd_talepleri';

    protected $guarded = ['id'];

    public const NEDENLER = [
        'yipranma' => 'Yıpranma / Hasar',
        'yeni_gorev' => 'Yeni Görev / Alan',
        'kayip' => 'Kayıp',
        'diger' => 'Diğer',
    ];

    public const DURUMLAR = [
        'bekliyor' => 'Onay Bekliyor',
        'onaylandi' => 'Onaylandı (Hazırlanıyor)',
        'teslim_edildi' => 'Teslim Edildi',
        'reddedildi' => 'Reddedildi',
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
