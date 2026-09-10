<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eğitim Katılım Formu — isgpratik EĞİTİM ekranları. "İş Sağlığı ve Güvenliği"
 * (varsayılan) başlığı Genel/Sağlık/Teknik/İşyerine Özgü 4 bloğu birden
 * kapsar; diğer tüm başlıklar tek bloklu "özel" eğitimlerdir.
 */
class EgitimKatilim extends Model
{
    use HasFactory;

    protected $table = 'egitim_katilimlari';

    protected $guarded = ['id'];

    protected $casts = [
        'belge_tarihi' => 'date',
        'sure_gun' => 'integer',
        'gun_tarihleri' => 'array',
        'isg_uzmani_var' => 'boolean',
        'isyeri_hekimi_var' => 'boolean',
        'konu_secimleri' => 'array',
        'katilimcilar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (EgitimKatilim $e): void {
            $e->belge_no ??= 'EGT-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function basliklarEtiketi(): string
    {
        return $this->baslik_anahtari === 'genel'
            ? 'İş Sağlığı ve Güvenliği'
            : (config('isg.egitim.ozel_basliklar.'.$this->baslik_anahtari.'.ad') ?? $this->baslik_anahtari);
    }

    public function katilimciSayisi(): int
    {
        return count($this->katilimcilar ?? []);
    }
}
