<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş ekipmanı bakım / arıza kaydı (12.11.2026). Günlük kontrolde "Uygun Değil" işaretlenince
 * kendiliğinden açılır (App\Support\EkipmanGunlukKontrol); panelden elle de açılıp kapatılır.
 */
class IsEkipmaniBakimi extends Model
{
    protected $table = 'is_ekipmani_bakimlari';

    protected $guarded = ['id'];

    protected $casts = [
        'uygunsuz_maddeler' => 'array',
        'acilis_zamani' => 'datetime',
        'kapanis_zamani' => 'datetime',
    ];

    public const TURLER = ['ariza' => 'Arıza', 'bakim' => 'Bakım'];

    public const DURUMLAR = ['acik' => 'Açık', 'devam' => 'Onarımda', 'kapali' => 'Kapandı'];

    public function ekipman(): BelongsTo
    {
        return $this->belongsTo(IsEkipmani::class, 'is_ekipmani_id');
    }

    public function gunlukKontrol(): BelongsTo
    {
        return $this->belongsTo(IsEkipmaniGunlukKontrolu::class, 'gunluk_kontrol_id');
    }

    public function scopeAcik(Builder $q): Builder
    {
        return $q->where('durum', '!=', 'kapali');
    }
}
