<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tatbikat Tutanağı — isgpratik 61-65.jpg.
 */
class TatbikatTutanagi extends Model
{
    use HasFactory;

    protected $table = 'tatbikat_tutanaklari';

    protected $guarded = ['id'];

    protected $casts = [
        'tatbikat_tarihi' => 'date',
        'belge_tarihi' => 'date',
        'haberli_tatbikat' => 'boolean',
        'yillik_plan_dahilinde' => 'boolean',
        'isyeri_hekimi_imzasi' => 'boolean',
        'ekipler' => 'array',
        'degerlendirmeler' => 'array',
        'eksiklikler' => 'array',
        'dof_onerileri' => 'array',
        'katilimcilar' => 'array',
        'fotograflar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function senaryoEtiketi(): string
    {
        return config('isg.tatbikat.senaryolar.'.$this->senaryo_anahtari.'.ad', 'Genel Tatbikat');
    }
}
