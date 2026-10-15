<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Ön Bilgilendirme / Tebliğ yazısı — İGU'nun işe başlamadan önce firmaya bildirdiği,
 * kendi hazırladığı belge (dosya değiştirilmeden saklanır, `local` diskte). Tebliğ;
 * elden teslim kaydı ve/veya işveren panelindeki "okudum, tebellüğ ettim" onayıyla
 * belgelenir (kullanıcı 15.10.2026).
 */
class OnBilgilendirme extends Model
{
    protected $table = 'on_bilgilendirmeler';

    protected $guarded = ['id'];

    protected $casts = [
        'bildirim_tarihi' => 'date',
        'elden_teslim_tarihi' => 'date',
        'isyerine_gonderildi_at' => 'datetime',
        'isyeri_okundu_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleted(fn (OnBilgilendirme $b) => Storage::disk('local')->delete($b->dosya_yolu));
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** teblig_edildi (elden ya da panelde onaylı) | panelde_bekliyor | teblig_bekliyor */
    public function durum(): string
    {
        return match (true) {
            $this->isyeri_okundu_at !== null || $this->elden_teslim_tarihi !== null => 'teblig_edildi',
            $this->isyerine_gonderildi_at !== null => 'panelde_bekliyor',
            default => 'teblig_bekliyor',
        };
    }

    public function durumEtiketi(): string
    {
        return [
            'teblig_edildi' => 'Tebliğ edildi',
            'panelde_bekliyor' => 'İşveren panelinde, onay bekliyor',
            'teblig_bekliyor' => 'Tebliğ bekliyor',
        ][$this->durum()];
    }
}
