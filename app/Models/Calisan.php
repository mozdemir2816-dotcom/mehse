<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calisan extends Model
{
    use HasFactory;

    protected $table = 'calisanlar';

    protected $guarded = ['id'];

    protected $casts = [
        'ise_giris' => 'date',
        'isten_cikis' => 'date',
        'dogum_tarihi' => 'date',
        'agir_tehlikeli_iste' => 'boolean',
        'aktif' => 'boolean',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function egitimKayitlari(): HasMany
    {
        return $this->hasMany(EgitimKaydi::class);
    }
}
