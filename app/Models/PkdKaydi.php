<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Patlamadan Korunma Dokümanı (PKD) künyesi — bölüm / proses bazında zone,
 * tutuşturucu kaynak ve önlem kaydı; dosya `public` diskte pkd/{firma_id}/.
 */
class PkdKaydi extends Model
{
    protected $table = 'pkd_kayitlari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'taslak', 'ortam_turu' => 'gaz_buhar_sis'];

    protected $casts = [
        'dokuman_tarihi' => 'date',
        'sonraki_gozden_gecirme' => 'date',
        'zonelar' => 'array',
        'tutusturucular' => 'array',
        'onlemler' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PkdKaydi $p): void {
            if ($p->dosya_yolu && Storage::disk('public')->exists($p->dosya_yolu)) {
                Storage::disk('public')->delete($p->dosya_yolu);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function durumEtiketi(): string
    {
        return config('isg.pkd.durumlar.'.$this->durum, $this->durum);
    }

    public function ortamEtiketi(): string
    {
        return config('isg.pkd.ortam_turleri.'.$this->ortam_turu, $this->ortam_turu ?? '—');
    }

    /** @return array<int, string> */
    public function etiketler(string $alan): array
    {
        return collect($this->{$alan} ?? [])
            ->map(fn ($k) => config("isg.pkd.{$alan}.{$k}", $k))
            ->values()
            ->all();
    }

    public function dosyaVarMi(): bool
    {
        return filled($this->dosya_yolu);
    }

    /** gecikmis | yaklasan | guncel | tarihsiz (arşiv / taslak hariç tutulmaz) */
    public function incelemeDurumu(): string
    {
        if (! $this->sonraki_gozden_gecirme) {
            return 'tarihsiz';
        }

        $kalan = (int) now()->startOfDay()->diffInDays($this->sonraki_gozden_gecirme, false);

        return match (true) {
            $kalan < 0 => 'gecikmis',
            $kalan <= (int) config('isg.pkd.yaklasan_gun', 30) => 'yaklasan',
            default => 'guncel',
        };
    }

    /** Takip gerektiren: revizyon durumu veya inceleme tarihi geçmiş aktif doküman. */
    public function takipGerekiyorMu(): bool
    {
        return $this->durum === 'revizyon'
            || ($this->durum !== 'arsiv' && $this->incelemeDurumu() === 'gecikmis');
    }
}
