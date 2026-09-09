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
