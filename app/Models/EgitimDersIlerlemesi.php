<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgitimDersIlerlemesi extends Model
{
    use HasFactory;

    protected $table = 'egitim_ders_ilerlemeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'izleme_yuzdesi' => 'integer',
        'izlendi' => 'boolean',
        'izlendi_at' => 'datetime',
    ];

    public function atama(): BelongsTo
    {
        return $this->belongsTo(EgitimAtamasi::class, 'egitim_atamasi_id');
    }

    public function ders(): BelongsTo
    {
        return $this->belongsTo(EgitimDersi::class, 'egitim_dersi_id');
    }
}
