<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanliImza extends Model
{
    protected $table = 'canli_imzalar';

    protected $guarded = ['id'];

    protected $casts = [
        'imzalandi_at' => 'datetime',
    ];

    public const BELGE_TURLERI = [
        'egitim_katilim' => 'Eğitim Katılım Formu',
        'kkd' => 'KKD Zimmet ve Teslim Tutanağı',
        'ceza_teblig' => 'Ceza / İhtar ve Tebliğ Tutanağı',
        'muayene_ek2' => 'EK-2 Sağlık Muayene Formu',
        'kurul' => 'İSG Kurulu Toplantı Tutanağı',
        'isbasi_egitim' => 'İşbaşı Eğitim Tutanağı',
        'tatbikat' => 'Acil Durum Tatbikat Tutanağı',
        'talimat' => 'Güvenli Çalışma Talimatı Tebellüğü',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function belgeTuruEtiketi(): string
    {
        return self::BELGE_TURLERI[$this->belge_turu] ?? $this->belge_turu;
    }

    public function imzalandiMi(): bool
    {
        return $this->imzalandi_at !== null && filled($this->imza_gorseli);
    }

    public function maskeliTc(): string
    {
        $tc = preg_replace('/\D/', '', (string) $this->calisan_tc);
        if (strlen($tc) !== 11) {
            return '—';
        }

        return substr($tc, 0, 3).'*****'.substr($tc, -3);
    }
}
