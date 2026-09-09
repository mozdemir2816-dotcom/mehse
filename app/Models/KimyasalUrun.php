<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İşyeri kimyasal envanterindeki tek ürün — SDS dosyası + GHS sınıfları +
 * gözden geçirme tarihi.
 */
class KimyasalUrun extends Model
{
    use HasFactory;

    protected $table = 'kimyasal_urunler';

    protected $guarded = ['id'];

    protected $casts = [
        'ghs' => 'array',
        'sds_tarihi' => 'date',
        'sonraki_gozden_gecirme' => 'date',
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (KimyasalUrun $u): void {
            // SDS tarihi girilmiş, sonraki gözden geçirme boşsa +1 yıl (yaygın uygulama).
            if ($u->sds_tarihi && ! $u->sonraki_gozden_gecirme) {
                $u->sonraki_gozden_gecirme = Carbon::parse($u->sds_tarihi)->addYear();
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function sdsVarMi(): bool
    {
        return filled($this->sds_dosya_yolu);
    }

    /** @return array<int, string> */
    public function ghsEtiketleri(): array
    {
        $tanim = config('isg.kimyasal.ghs', []);

        return collect($this->ghs ?? [])->map(fn ($k) => $tanim[$k] ?? $k)->all();
    }

    public function gozdenGecirmeDurumu(): string
    {
        if (! $this->sonraki_gozden_gecirme) {
            return 'belirsiz';
        }

        $gun = Carbon::today()->diffInDays($this->sonraki_gozden_gecirme, false);

        return match (true) {
            $gun < 0 => 'gecikmis',
            $gun <= 60 => 'yaklasan',
            default => 'guncel',
        };
    }
}
