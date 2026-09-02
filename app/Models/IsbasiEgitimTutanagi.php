<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İşbaşı / Oryantasyon Eğitim Tutanağı — isgpratik 60.jpg.
 */
class IsbasiEgitimTutanagi extends Model
{
    use HasFactory;

    protected $table = 'isbasi_egitim_tutanaklari';

    protected $guarded = ['id'];

    protected $casts = [
        'egitim_tarihi' => 'date',
        'belge_tarihi' => 'date',
        'tc_gizli' => 'boolean',
        'igu_imzasi' => 'boolean',
        'isyeri_hekimi_imzasi' => 'boolean',
        'konular' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function tcGorunur(): string
    {
        if (blank($this->calisan_tc)) {
            return '—';
        }

        return $this->tc_gizli ? substr($this->calisan_tc, 0, 3).str_repeat('*', max(strlen($this->calisan_tc) - 3, 0)) : $this->calisan_tc;
    }
}
