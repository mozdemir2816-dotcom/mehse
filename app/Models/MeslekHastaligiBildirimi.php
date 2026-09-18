<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Meslek Hastalığı Bildirimi — 6331 s.K. m.14 / SGK Yönetmeliği: hastalığın
 * meslek hastalığı olduğunun sağlık hizmeti sunucusu, işyeri hekimi veya
 * sigortalı tarafından ÖĞRENİLDİĞİ tarihten itibaren işveren 3 iş günü
 * içinde SGK'ya (e-SGK/e-Devlet) bildirmekle yükümlüdür.
 */
class MeslekHastaligiBildirimi extends Model
{
    protected $table = 'meslek_hastaligi_bildirimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'ogrenme_tarihi' => 'date',
        'bildirim_son_tarihi' => 'date',
        'tani_tarihi' => 'date',
        'sgk_bildirim_tarihi' => 'date',
        'sgk_bildirimi_yapildi' => 'boolean',
        'meslekte_kazanma_gucu_kaybi_yuzde' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (MeslekHastaligiBildirimi $m): void {
            $m->belge_no ??= 'MH-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);

            if ($m->ogrenme_tarihi) {
                $m->bildirim_son_tarihi = Carbon::parse($m->ogrenme_tarihi)->addWeekdays(3);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function ogrenmeKaynagiEtiketi(): string
    {
        return config('isg.meslek_hastaligi.ogrenme_kaynaklari.'.$this->ogrenme_kaynagi, $this->ogrenme_kaynagi ?? '—');
    }

    public function sgkKurulOnayDurumuEtiketi(): string
    {
        return config('isg.meslek_hastaligi.kurul_onay_durumlari.'.$this->sgk_kurul_onay_durumu, $this->sgk_kurul_onay_durumu ?? '—');
    }

    /** Süresi geçmiş mi — SGK'ya hâlâ bildirilmemiş ve son tarih geride kaldıysa. */
    public function suresiGectiMi(): bool
    {
        return ! $this->sgk_bildirimi_yapildi && $this->bildirim_son_tarihi && now()->gt($this->bildirim_son_tarihi);
    }

    /** Bildirime kaç gün kaldığı (negatifse süre geçmiş demektir). */
    public function kalanGun(): ?int
    {
        return $this->bildirim_son_tarihi ? now()->startOfDay()->diffInDays($this->bildirim_son_tarihi, false) : null;
    }
}
