<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgitimPaketiSorusu extends Model
{
    use HasFactory;

    protected $table = 'egitim_paketi_sorulari';

    protected $guarded = ['id'];

    /** Sorunun kullanıldığı sınav: ön test (seviye tespiti), final sınavı ya da ikisi. */
    public const KULLANIMLAR = [
        'ortak' => 'Ön test + final',
        'on_test' => 'Yalnız ön test',
        'final' => 'Yalnız final sınavı',
    ];

    protected $casts = [
        'secenekler' => 'array',
        'dogru_index' => 'integer',
    ];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(EgitimPaketi::class, 'egitim_paketi_id');
    }

    public function dogruSecenek(): ?string
    {
        return $this->secenekler[$this->dogru_index] ?? null;
    }
}
