<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İSG-KATİP'in "İSG Hizmet Sözleşmeleri" ekranındaki native "Tümünü Dışa
 * aktar" Excel'inden içe aktarılan tek bir sözleşme süreç satırı
 * (bkz. App\Support\IsgKatipSozlesmeIceAktarici). Amaç: birden fazla uzman/
 * hekim çalıştıran kullanıcının her görevlendirilen kişinin aktif atama
 * sayısını, çalışma süresi dağılımını ve sözleşme bitiş/onay durumunu tek
 * yerden görüp planlama yapabilmesi (bkz. App\Filament\Pages\IsgKatipSozlesmeleri).
 */
class IsgKatipSozlesmesi extends Model
{
    protected $table = 'isg_katip_sozlesmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'oto_guncelleme_izni' => 'boolean',
        'mevzuata_uygun_mu' => 'boolean',
        'hizmet_alan_calisan_sayisi' => 'integer',
        'baslangic_tarihi' => 'date',
        'bitis_tarihi' => 'date',
        'onay_tarihi' => 'date',
        'ham_veri' => 'array',
        'son_iceaktarim_at' => 'datetime',
    ];

    public const AKTIF_STATULER = ['Sözleşme Devam Ediyor'];

    public const ONAY_BEKLEYEN_DURUMLAR = ['Sözleşme Onayı Bekliyor', 'İSG Profesyoneli Onayında', 'Hizmet Alan İşyeri Onayında'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function aktifMi(): bool
    {
        return in_array($this->statu, self::AKTIF_STATULER, true);
    }

    public function onayBekliyorMu(): bool
    {
        return in_array($this->onay_durumu, self::ONAY_BEKLEYEN_DURUMLAR, true);
    }

    /** Bitişine N gün veya daha az kalmış, hâlâ aktif sözleşme. */
    public function suresiYaklasiyorMu(int $gun = 30): bool
    {
        return $this->aktifMi() && $this->bitis_tarihi !== null
            && now()->diffInDays($this->bitis_tarihi, false) <= $gun
            && now()->diffInDays($this->bitis_tarihi, false) >= 0;
    }

    public function suresiGecmisMi(): bool
    {
        return $this->aktifMi() && $this->bitis_tarihi !== null && $this->bitis_tarihi->isPast();
    }

    public function tehlikeSinifiAnahtari(): ?string
    {
        return collect(config('isg.tehlike_siniflari'))->search($this->hizmet_alan_tehlike_sinifi) ?: null;
    }

    public function scopeAktif(Builder $q): Builder
    {
        return $q->whereIn('statu', self::AKTIF_STATULER);
    }
}
