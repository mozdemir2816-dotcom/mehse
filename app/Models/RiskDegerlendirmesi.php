<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Firma risk değerlendirmesi (rapor). Firma künyesi kayıt anında snapshot'lanır;
 * geçerlilik tarihi tehlike sınıfına göre otomatik hesaplanır.
 */
class RiskDegerlendirmesi extends Model
{
    use HasFactory;

    protected $table = 'risk_degerlendirmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'gecerlilik_tarihi' => 'date',
        'ekip' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (RiskDegerlendirmesi $rd): void {
            $firma = $rd->firma;

            if ($firma) {
                $rd->firma_unvan ??= $firma->unvan;
                $rd->firma_sgk_sicil_no ??= $firma->sgk_sicil_no;
                $rd->firma_nace ??= trim($firma->nace_kodu.' '.$firma->nace_aciklama);
                $rd->tehlike_sinifi ??= $firma->tehlike_sinifi;
                $rd->firma_adres ??= $firma->adres;

                if ($rd->rapor_tarihi && ! $rd->gecerlilik_tarihi) {
                    $yil = config('isg.risk_gecerlilik_yili.'.$firma->tehlike_sinifi, 4);
                    $rd->gecerlilik_tarihi = Carbon::parse($rd->rapor_tarihi)->addYears($yil);
                }
            }

            $rd->belge_no ??= static::belgeNoUret();
        });
    }

    public static function belgeNoUret(): string
    {
        $yil = now()->year;
        $sira = static::whereYear('created_at', $yil)->count() + 1;

        return sprintf('RD-%d-%03d', $yil, $sira);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function maddeler(): HasMany
    {
        return $this->hasMany(RiskMaddesi::class)->orderBy('sira');
    }

    public function yontemEtiketi(): string
    {
        return config('isg.risk_yontemleri.'.$this->yontem, $this->yontem);
    }

    public function fineKinneyMi(): bool
    {
        return $this->yontem === 'fine_kinney';
    }

    public function gecerlilikGecti(): bool
    {
        return $this->gecerlilik_tarihi && $this->gecerlilik_tarihi->isPast();
    }
}
