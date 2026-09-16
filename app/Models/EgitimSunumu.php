<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Görev/iş başına yüklenen eğitim sunumu (PowerPoint/PDF) — kullanıcının
 * kendi arşivi. İşbaşı Eğitim Tutanağı sayfasında çalışanın görevine göre
 * eşleşen sunum önerilir.
 */
class EgitimSunumu extends Model
{
    use HasFactory;

    protected $table = 'egitim_sunumlari';

    protected $guarded = ['id'];

    protected $casts = [
        'boyut' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boyutEtiketi(): string
    {
        if (! $this->boyut) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
