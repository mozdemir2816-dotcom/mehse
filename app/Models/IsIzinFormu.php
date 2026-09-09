<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg. İzin türüne göre ilgili
 * güvenlik önlemleri dinamik gösterilir; izin kütüphanesinden (config veya
 * IsIzinSablonu) ön doldurulabilir. Onay/kapanış yaşam döngüsü: taslak ->
 * onay bekliyor -> onaylandı (çalışma yetkisi) -> iş tamamlandı -> kapatıldı.
 */
class IsIzinFormu extends Model
{
    use HasFactory;

    protected $table = 'is_izin_formlari';

    protected $guarded = ['id'];

    protected $casts = [
        'baslangic' => 'datetime',
        'bitis' => 'datetime',
        'onay1_tarih' => 'datetime',
        'onay2_tarih' => 'datetime',
        'is_bitis_tarihi' => 'datetime',
        'saha_teslim_alindi' => 'boolean',
        'gecerlilik_saat' => 'integer',
        'izin_turleri' => 'array',
        'guvenlik_onlemleri' => 'array',
        'gerekli_kkdler' => 'array',
        'uyarilar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (IsIzinFormu $f): void {
            $f->izin_no ??= 'PTW-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
            $f->durum ??= 'taslak';

            // Geçerlilik saati verilmiş ve bitiş boşsa başlangıçtan türet.
            if ($f->gecerlilik_saat && $f->baslangic && ! $f->bitis) {
                $f->bitis = $f->baslangic->copy()->addHours($f->gecerlilik_saat);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** @return array<int, string> seçili izin türlerinin etiketleri */
    public function izinTurEtiketleri(): array
    {
        return collect($this->izin_turleri ?? [])
            ->map(fn ($t) => config('isg.is_izin.turler.'.$t, $t))
            ->all();
    }

    public function durumEtiketi(): string
    {
        return config('isg.is_izin.durumlar.'.$this->durum, $this->durum ?? '—');
    }

    public function durumRengi(): string
    {
        return match ($this->durum) {
            'onaylandi' => 'success',
            'onay_bekliyor' => 'warning',
            'reddedildi', 'iptal' => 'danger',
            'is_tamamlandi' => 'info',
            'kapatildi' => 'gray',
            default => 'gray',
        };
    }

    public function onay1Etiketi(): string
    {
        return config('isg.is_izin.onay_durumlari.'.$this->onay1_durum, $this->onay1_durum ?? '—');
    }

    public function onay2Etiketi(): string
    {
        return config('isg.is_izin.onay_durumlari.'.$this->onay2_durum, $this->onay2_durum ?? '—');
    }

    /** İki onaycı da onayladı mı? */
    public function tamOnayliMi(): bool
    {
        return $this->onay1_durum === 'onayladi' && $this->onay2_durum === 'onayladi';
    }

    /** Onaylı ve geçerlilik penceresi içinde mi (çalışma fiilen yapılabilir mi)? */
    public function aktifMi(): bool
    {
        return $this->durum === 'onaylandi'
            && (! $this->baslangic || $this->baslangic->isPast())
            && (! $this->bitis || $this->bitis->isFuture());
    }

    /** Onaylı/onay bekleyen ama geçerlilik süresi dolmuş — kapatılmalı. */
    public function suresiGectiMi(): bool
    {
        return in_array($this->durum, ['onaylandi', 'onay_bekliyor'], true)
            && $this->bitis
            && $this->bitis->isPast();
    }
}
