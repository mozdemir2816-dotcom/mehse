<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yıl başı toplu online eğitim — firma × yıl bildirim kaydı (23.11.2026). Firma yetkilisine
 * giriş bilgileri e-postası gönderildi mi, kaç kişiye yenileme (4 saat) / temel (8 saat) atandı.
 */
class EgitimTopluBildirimi extends Model
{
    protected $table = 'egitim_toplu_bildirimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'yenileme_sayisi' => 'integer',
        'ilk_defa_sayisi' => 'integer',
        'eposta_gonderildi_at' => 'datetime',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
