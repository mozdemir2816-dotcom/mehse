<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş Kazası Raporu — 6331 s.K. ve standart kaza inceleme raporu formatı.
 */
class IsKazasiRaporu extends Model
{
    protected $table = 'is_kazasi_raporlari';

    protected $guarded = ['id'];

    protected $casts = [
        'kaza_tarihi' => 'date',
        'sgk_bildirim_tarihi' => 'date',
        'kayip_gun_sayisi' => 'integer',
        'kok_neden_kategorileri' => 'array',
        'taniklar' => 'array',
        'fotograflar' => 'array',
        'sgk_bildirimi_yapildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (IsKazasiRaporu $r): void {
            $r->belge_no ??= 'IKR-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
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

    public function kazaTuruEtiketi(): string
    {
        return config('isg.is_kazasi.kaza_turleri.'.$this->kaza_turu, $this->kaza_turu ?? '—');
    }

    public function agirlikDerecesiEtiketi(): string
    {
        return config('isg.is_kazasi.agirlik_dereceleri.'.$this->agirlik_derecesi, $this->agirlik_derecesi ?? '—');
    }

    /** @return array<int, string> */
    public function kokNedenEtiketleri(): array
    {
        return collect($this->kok_neden_kategorileri ?? [])
            ->map(fn ($k) => config('isg.is_kazasi.kok_neden_kategorileri.'.$k, $k))
            ->all();
    }
}
