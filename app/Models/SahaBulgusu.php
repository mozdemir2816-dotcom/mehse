<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Hızlı saha bulgusu — tek uygunsuzluk kaydı (5×5 risk, aksiyon, foto kanıt).
 */
class SahaBulgusu extends Model
{
    protected $table = 'saha_bulgulari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'acik', 'kaynak' => 'manuel', 'olasilik' => 3, 'siddet' => 3];

    protected $casts = [
        'olasilik' => 'integer',
        'siddet' => 'integer',
        'enlem' => 'float',
        'boylam' => 'float',
        'termin' => 'date',
        'kapanis_tarihi' => 'date',
        'fotograflar' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (SahaBulgusu $b): void {
            $b->bulgu_no ??= 'SB-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
        });

        static::deleting(function (SahaBulgusu $b): void {
            foreach ($b->fotograflar ?? [] as $yol) {
                if (Storage::disk('public')->exists($yol)) {
                    Storage::disk('public')->delete($yol);
                }
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function skor(): int
    {
        return (int) $this->olasilik * (int) $this->siddet;
    }

    public static function seviye(int $skor): string
    {
        return match (true) {
            $skor <= 4 => 'Düşük',
            $skor <= 9 => 'Orta',
            $skor <= 15 => 'Yüksek',
            default => 'Çok Yüksek',
        };
    }

    public function seviyeEtiketi(): string
    {
        return static::seviye($this->skor());
    }

    public function terminGectiMi(): bool
    {
        return $this->durum === 'acik' && $this->termin?->lt(today());
    }

    public function konumLinki(): ?string
    {
        return $this->enlem !== null && $this->boylam !== null
            ? 'https://www.google.com/maps?q='.$this->enlem.','.$this->boylam
            : null;
    }
}
