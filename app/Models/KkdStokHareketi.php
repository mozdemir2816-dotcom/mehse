<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** KKD stok hareketi — miktar işaretli: giriş/iade (+), zimmet/fire (−). */
class KkdStokHareketi extends Model
{
    protected $table = 'kkd_stok_hareketleri';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'miktar' => 'integer',
    ];

    public function stokKarti(): BelongsTo
    {
        return $this->belongsTo(KkdStokKarti::class, 'kkd_stok_karti_id');
    }

    public function zimmet(): BelongsTo
    {
        return $this->belongsTo(KkdZimmet::class, 'kkd_zimmet_id');
    }

    public function tipEtiketi(): string
    {
        return config('isg.kkd_takip.hareket_tipleri.'.$this->tip, $this->tip);
    }
}
