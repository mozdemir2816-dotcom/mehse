<?php

namespace App\Models;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Ziyaretçi geçiş kartı — işyerine gelen dış kişi için süreli kart. QR, kartın
 * geçerliliğini gösteren herkese açık doğrulama adresine (token) gider.
 */
class Ziyaretci extends Model
{
    protected $table = 'ziyaretciler';

    protected $guarded = ['id'];

    protected $attributes = ['iptal' => false, 'isg_bilgilendirme' => false];

    protected $casts = [
        'gecerlilik_baslangic' => 'datetime',
        'gecerlilik_bitis' => 'datetime',
        'giris_zamani' => 'datetime',
        'cikis_zamani' => 'datetime',
        'isg_bilgilendirme' => 'boolean',
        'iptal' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ziyaretci $z): void {
            $z->token ??= Str::random(40);
            $z->kart_no ??= 'ZYR-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** iptal | cikti | icerde | planli | suresi_doldu | gecerli */
    public function durum(): string
    {
        return match (true) {
            $this->iptal => 'iptal',
            $this->cikis_zamani !== null => 'cikti',
            $this->giris_zamani !== null && $this->gecerlilik_bitis->isFuture() => 'icerde',
            $this->gecerlilik_baslangic->isFuture() => 'planli',
            $this->gecerlilik_bitis->isPast() => 'suresi_doldu',
            default => 'gecerli',
        };
    }

    public function durumEtiketi(): string
    {
        return config('isg.ziyaretci.durumlar.'.$this->durum(), $this->durum());
    }

    /** Kart şu an geçerli mi (giriş yapılabilir / içeride kalabilir). */
    public function gecerliMi(): bool
    {
        return in_array($this->durum(), ['gecerli', 'icerde'], true);
    }

    /**
     * Süresi dolduğu hâlde çıkışı işlenmemiş ziyaretçi de "içeride" sayılır —
     * acil durum sayımında atlanmasın.
     */
    public function iceridemi(): bool
    {
        return ! $this->iptal && $this->giris_zamani !== null && $this->cikis_zamani === null;
    }

    public function dogrulamaAdresi(): string
    {
        return route('ziyaretci.dogrula', $this->token);
    }

    /** QR görseli (SVG data URI — GD gerektirmez, dompdf'te de çalışır). */
    public function qrDataUri(): string
    {
        return (new QRCode(new QROptions(['outputBase64' => true, 'addQuietzone' => true])))
            ->render($this->dogrulamaAdresi());
    }
}
