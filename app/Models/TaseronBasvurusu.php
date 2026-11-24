<?php

namespace App\Models;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Taşeron Self-Servis Evrak & Başvuru Portalı kaydı (K2).
 */
class TaseronBasvurusu extends Model
{
    protected $table = 'taseron_basvurulari';

    protected $guarded = ['id'];

    protected $casts = [
        'yuklenen_belgeler' => 'array',
        'basvuru_at' => 'datetime',
        'incelenme_at' => 'datetime',
    ];

    public const GECERLILIK_AY = 6;

    public const DURUMLAR = [
        'link_acik' => 'Link Gönderildi (Bekleniyor)',
        'bekliyor' => 'İnceleme Bekliyor',
        'onaylandi' => 'Onaylandı',
        'reddedildi' => 'Reddedildi',
    ];

    public function gecerlilikBitis(): \Illuminate\Support\Carbon
    {
        return ($this->created_at ?? now())->copy()->addMonthsNoOverflow(self::GECERLILIK_AY);
    }

    public function linkSuresiDolduMu(): bool
    {
        return $this->durum !== 'onaylandi' && $this->gecerlilikBitis()->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (self $b): void {
            $b->token ??= Str::random(36);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function taseron(): BelongsTo
    {
        return $this->belongsTo(Taseron::class);
    }

    public function basvuruLinki(): string
    {
        return route('taseron.basvuru', $this->token);
    }

    public function qrDataUri(): string
    {
        return (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))
            ->render($this->basvuruLinki());
    }
}
