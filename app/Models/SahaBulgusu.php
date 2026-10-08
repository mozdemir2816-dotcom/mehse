<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Saha bulgusu — saha kontrollerinin ORTAK kaydı ("tek bulgu, çok çıktı",
 * 08.10.2026): tek uygunsuzluk, 5×5 risk, aksiyon, sorumlu/termin, foto
 * kanıt. Saha Gözlem Raporu, DÖF ve Tespit-Öneri Defteri çıktıları bu
 * kayıtlardan üretilir; eski madde yapılarıyla dönüşüm BulguDonusturucu'da.
 */
class SahaBulgusu extends Model
{
    /** DÖF'ün dört durumuyla ortak; DÖF'teki "tamamlandi" = "kapandi". */
    public const DURUMLAR = [
        'acik' => 'Açık',
        'devam_ediyor' => 'Devam ediyor',
        'ertelendi' => 'Ertelendi',
        'kapandi' => 'Kapandı',
    ];

    public const ONCELIKLER = [
        'kritik' => 'Kritik',
        'yuksek' => 'Yüksek',
        'orta' => 'Orta',
        'dusuk' => 'Düşük',
    ];

    public const ONCELIK_RENK = [
        'kritik' => '#b91c1c',
        'yuksek' => '#d97706',
        'orta' => '#ca8a04',
        'dusuk' => '#16a34a',
    ];

    protected $table = 'saha_bulgulari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'acik', 'kaynak' => 'manuel', 'olasilik' => 3, 'siddet' => 3];

    protected $casts = [
        'olasilik' => 'integer',
        'siddet' => 'integer',
        'hedef_olasilik' => 'integer',
        'hedef_siddet' => 'integer',
        'kaynak_kayit_id' => 'integer',
        'kaynak_sira' => 'integer',
        'enlem' => 'float',
        'boylam' => 'float',
        'termin' => 'date',
        'kapanis_tarihi' => 'date',
        'fotograflar' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (SahaBulgusu $b): void {
            $b->bulgu_no ??= 'SB-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
        });

        static::deleting(function (SahaBulgusu $b): void {
            foreach ($b->fotograflar ?? [] as $yol) {
                if (Storage::disk('public')->exists($yol)) {
                    Storage::disk('public')->delete($yol);
                }
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function skor(): int
    {
        return (int) $this->olasilik * (int) $this->siddet;
    }

    public static function seviye(int $skor): string
    {
        return match (true) {
            $skor <= 4 => 'Düşük',
            $skor <= 9 => 'Orta',
            $skor <= 15 => 'Yüksek',
            default => 'Çok Yüksek',
        };
    }

    public function seviyeEtiketi(): string
    {
        return static::seviye($this->skor());
    }

    /** 5×5 skordan öncelik — seviye() ile aynı bantlar (≤4 / ≤9 / ≤15 / üstü). */
    public static function skordanOncelik(int $skor): string
    {
        return match (true) {
            $skor <= 4 => 'dusuk',
            $skor <= 9 => 'orta',
            $skor <= 15 => 'yuksek',
            default => 'kritik',
        };
    }

    /**
     * Yalnız önceliği bilinen madde (DÖF, Tespit-Öneri) için temsilî 5×5 değer:
     * skordanOncelik() aynı önceliği geri verir.
     *
     * @return array{0: int, 1: int} olasılık, şiddet
     */
    public static function onceliktenRisk(string $oncelik): array
    {
        return match ($oncelik) {
            'kritik' => [4, 5],
            'yuksek' => [3, 4],
            'dusuk' => [2, 2],
            default => [3, 3],
        };
    }

    /** Elle verilmiş öncelik, yoksa 5×5 skordan. */
    public function oncelikAnahtari(): string
    {
        return array_key_exists((string) $this->oncelik, static::ONCELIKLER)
            ? $this->oncelik
            : static::skordanOncelik($this->skor());
    }

    public function oncelikEtiketi(): string
    {
        return static::ONCELIKLER[$this->oncelikAnahtari()];
    }

    public function oncelikRengi(): string
    {
        return static::ONCELIK_RENK[$this->oncelikAnahtari()];
    }

    public function acikMi(): bool
    {
        return in_array($this->durum, ['acik', 'devam_ediyor'], true);
    }

    public function terminGectiMi(): bool
    {
        return $this->acikMi() && $this->termin?->lt(today());
    }

    public function konumLinki(): ?string
    {
        return $this->enlem !== null && $this->boylam !== null
            ? 'https://www.google.com/maps?q='.$this->enlem.','.$this->boylam
            : null;
    }
}
