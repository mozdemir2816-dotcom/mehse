<?php

namespace App\Models;

use App\Support\RiskSkorlama;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Risk değerlendirmesindeki tek satır. `saving` hook'u puan/düzey ve rezidüel
 * puan/düzeyi yönteme göre hesaplar.
 */
class RiskMaddesi extends Model
{
    use HasFactory;

    protected $table = 'risk_maddeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'etkilenen_calisan' => 'boolean',
        'etkilenen_diger' => 'boolean',
        'olasilik' => 'float',
        'frekans' => 'float',
        'siddet' => 'float',
        'puan' => 'float',
        'son_olasilik' => 'float',
        'son_frekans' => 'float',
        'son_siddet' => 'float',
        'son_puan' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (RiskMaddesi $m): void {
            $yontem = $m->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

            $mevcut = RiskSkorlama::hesapla($yontem, $m->olasilik, $m->siddet, $m->frekans);
            $m->puan = $mevcut['puan'] ?: null;
            $m->duzey = $mevcut['puan'] ? $mevcut['duzey'] : null;

            $son = RiskSkorlama::hesapla($yontem, $m->son_olasilik, $m->son_siddet, $m->son_frekans);
            $m->son_puan = $son['puan'] ?: null;
            $m->son_duzey = $son['puan'] ? $son['duzey'] : null;
        });
    }

    public function riskDegerlendirmesi(): BelongsTo
    {
        return $this->belongsTo(RiskDegerlendirmesi::class);
    }

    public function rengi(): string
    {
        $yontem = $this->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

        return RiskSkorlama::bant($yontem, (float) $this->puan)['renk'];
    }
}
