<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ziyaret Programı — firma + yıl başına tek kayıt, 12 aylık satır.
 */
class ZiyaretProgrami extends Model
{
    protected $table = 'ziyaret_programlari';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'ziyaretler' => 'array',
    ];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    public const AYLAR = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $p = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $p->exists) {
            $p->ziyaretler = array_fill(0, 12, ['tarih' => null, 'amac' => null, 'durum' => 'bos', 'sure_saat' => null, 'notlar' => null]);
            $p->save();
        }

        return $p;
    }
}
