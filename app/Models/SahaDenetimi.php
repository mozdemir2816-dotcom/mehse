<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saha Denetimi ("Şantiye Denetim ve Değerlendirme") — isgpratik SAHA
 * DENETİMİ/1-15.jpg + gerçek örnek PDF.
 */
class SahaDenetimi extends Model
{
    protected $table = 'saha_denetimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'denetim_tarihi' => 'date',
        'cevaplar' => 'array',
        'ekip_uyeleri' => 'array',
        'uygunluk_yuzdesi' => 'float',
        'kritik_uygunsuzluk_var' => 'boolean',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function sonucEtiketi(): string
    {
        if ($this->kritik_uygunsuzluk_var) {
            return 'Kritik Uygunsuzluk Var';
        }

        $uygunsuzVar = collect($this->cevaplar ?? [])->contains('sonuc', 'uygun_degil');

        return $uygunsuzVar ? 'Uygunsuzluk Var' : 'Uygun';
    }

    public function belgeAdi(): string
    {
        return config('isg.saha_denetimi.dokuman_kodu').' · Revizyon '.$this->revizyon;
    }
}
