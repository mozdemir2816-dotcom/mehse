<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev,
 * Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik EK-2.
 */
class MuayeneFormu extends Model
{
    protected $table = 'muayene_formlari';

    protected $guarded = ['id'];

    protected $casts = [
        'calisan_dogum_tarihi' => 'date',
        'ise_giris_tarihi' => 'date',
        'muayene_tarihi' => 'date',
        'onerilen_kontrol_tarihi' => 'date',
        'sistemik_muayene' => 'array',
        'tetkikler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (MuayeneFormu $m): void {
            $m->belge_no ??= 'MF-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function muayeneTuruEtiketi(): string
    {
        return config('isg.muayene.muayene_turleri.'.$this->muayene_turu, $this->muayene_turu ?? '—');
    }

    public function sonucKanaatiEtiketi(): string
    {
        return config('isg.muayene.sonuc_kanaatleri.'.$this->sonuc_kanaati, $this->sonuc_kanaati ?? '—');
    }
}
