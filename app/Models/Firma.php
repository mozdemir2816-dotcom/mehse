<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Firma extends Model
{
    use HasFactory;

    protected $table = 'firmalar';

    protected $guarded = ['id'];

    protected $casts = [
        'sozlesme_baslangic' => 'date',
        'sozlesme_bitis' => 'date',
        'aktif' => 'boolean',
        'calisan_sayisi' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Firma $f): void {
            if (blank($f->user_id) && auth()->check()) {
                $f->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calisanlar(): HasMany
    {
        return $this->hasMany(Calisan::class);
    }

    public function riskDegerlendirmeleri(): HasMany
    {
        return $this->hasMany(RiskDegerlendirmesi::class);
    }

    public function tehlikeSinifiEtiketi(): string
    {
        return config('isg.tehlike_siniflari.'.$this->tehlike_sinifi, $this->tehlike_sinifi);
    }

    /** Risk değerlendirmesi geçerlilik süresi (yıl) — tehlike sınıfına göre. */
    public function riskGecerlilikYili(): int
    {
        return config('isg.risk_gecerlilik_yili.'.$this->tehlike_sinifi, 4);
    }

    public function sozlesmeAktifMi(): bool
    {
        return $this->aktif
            && (! $this->sozlesme_bitis || $this->sozlesme_bitis->isFuture());
    }
}
