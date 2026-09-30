<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Firmaya bağlı kalıcı İSG kurulu üyesi. `rol` anahtarları ve zorunluluk:
 * config isg.kurul_toplantisi.roller (Yönetmelik Md.6). Toplantı
 * oluşturulurken aktif üyeler katılımcı olarak toplantıya kopyalanır
 * (tarihsel snapshot — üye sonradan değişse de eski tutanak bozulmaz).
 */
class KurulUyesi extends Model
{
    protected $table = 'kurul_uyeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function rolEtiketi(): string
    {
        return config("isg.kurul_toplantisi.roller.{$this->rol}.ad", $this->rol);
    }
}
