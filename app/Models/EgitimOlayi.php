<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzaktan eğitim olay günlüğü satırı (zaman çizelgesi). Yazma hatası eğitimi
 * asla durdurmamalı — `yaz()` istisnaları yutar.
 */
class EgitimOlayi extends Model
{
    protected $table = 'egitim_olaylari';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['olay_at' => 'datetime'];

    /** Zaman çizelgesi süzgeçleri: anahtar => [etiket, türler|null (hepsi)]. */
    public const SUZGECLER = [
        'tumu' => ['Tümü', null],
        'giris' => ['Giriş / çıkış', ['giris']],
        'oturum' => ['Oturumlar', ['oturum']],
        'sinav' => ['Sınavlar', ['on_test', 'sinav']],
        'uyari' => ['Uyarılar', ['uyari', 'hatali_giris']],
    ];

    /** Tür => nokta rengi (renge ek olarak başlık metni de olayı anlatır). */
    public const RENKLER = [
        'atama' => '#64748b', 'giris' => '#0284c7', 'hatali_giris' => '#d97706', 'on_test' => '#7c3aed',
        'oturum' => '#4f46e5', 'sinav' => '#7c3aed', 'uyari' => '#d97706', 'tamamlama' => '#15803d',
    ];

    public static function yaz(int $atamaId, string $tur, string $baslik, ?string $aciklama = null, ?int $calisanId = null): void
    {
        try {
            static::create([
                'egitim_atamasi_id' => $atamaId,
                'calisan_id' => $calisanId,
                'tur' => $tur,
                'baslik' => $baslik,
                'aciklama' => $aciklama,
                'ip' => request()?->ip(),
                'olay_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function atama(): BelongsTo
    {
        return $this->belongsTo(EgitimAtamasi::class, 'egitim_atamasi_id');
    }
}
