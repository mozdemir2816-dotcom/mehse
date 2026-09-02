<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sertifika — isgpratik 66-68.jpg. 4 tip (isg/yukseklik/kapali_alan/yangin);
 * katılımcı başına PDF'te ayrı bir sayfa üretilir.
 */
class Sertifika extends Model
{
    protected $table = 'sertifikalar';

    protected $guarded = ['id'];

    protected $casts = [
        'gun_sayisi' => 'integer',
        'egitim_tarihleri' => 'array',
        'gecerlilik_tarihi' => 'date',
        'egitici_igu_dahil' => 'boolean',
        'egitici_hekim_dahil' => 'boolean',
        'konu_icerigi' => 'array',
        'katilimcilar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Sertifika $s): void {
            $s->belge_no ??= 'SRT-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function tipTanimi(): array
    {
        return config('isg.sertifika.tipler.'.$this->tip, []);
    }

    public function tipEtiketi(): string
    {
        return $this->tipTanimi()['ad'] ?? $this->tip;
    }

    public function tipBasligi(): string
    {
        return $this->tipTanimi()['baslik'] ?? $this->tipEtiketi();
    }

    public function cokluEgiticiMi(): bool
    {
        return (bool) ($this->tipTanimi()['coklu_egitici'] ?? false);
    }

    public function turEtiketi(): string
    {
        return config('isg.sertifika.turler.'.$this->tur, $this->tur ?? '—');
    }

    public function sekilEtiketi(): string
    {
        return config('isg.sertifika.sekiller.'.$this->sekil, $this->sekil ?? '—');
    }
}
