<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * İş ekipmanının tek bir periyodik kontrol kaydı (geçmiş). IsEkipmani her
 * kaydedildiğinde son kontrol bilgisi buraya da yazılır (aynı tarih → güncellenir).
 */
class IsEkipmaniKontrolu extends Model
{
    protected $table = 'is_ekipmani_kontrolleri';

    protected $guarded = ['id'];

    protected $casts = [
        'kontrol_tarihi' => 'date',
        'sonraki_tarih' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (IsEkipmaniKontrolu $k): void {
            if ($k->dosya_yolu && Storage::disk('public')->exists($k->dosya_yolu)) {
                Storage::disk('public')->delete($k->dosya_yolu);
            }
        });
    }

    public function ekipman(): BelongsTo
    {
        return $this->belongsTo(IsEkipmani::class, 'is_ekipmani_id');
    }

    public function sonucEtiketi(): string
    {
        return config('isg.periyodik_kontrol.sonuclar.'.$this->sonuc, $this->sonuc ?? '—');
    }
}
