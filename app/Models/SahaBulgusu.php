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
        'yuksek' => '#c2410c',
        'orta' => '#a16207',
        'dusuk' => '#15803d',
    ];

    /** ISO 45001 Kontrol Hiyerarşisi (F1 — kapalı devre DÖF). */
    public const KONTROL_HIYERARSISI = [
        'eliminasyon' => 'Eliminasyon (Ortadan Kaldırma)',
        'ikame' => 'İkame (Daha Güvenli ile Değiştirme)',
        'muhendislik' => 'Mühendislik Kontrolü',
        'idari' => 'İdari Kontrol / Talimat / Eğitim',
        'kkd' => 'Kişisel Koruyucu Donanım (KKD)',
    ];

    public const KONTROL_HIYERARSISI_KISA = [
        'eliminasyon' => 'Eliminasyon',
        'ikame' => 'İkame',
        'muhendislik' => 'Mühendislik',
        'idari' => 'İdari',
        'kkd' => 'KKD',
    ];

    public const KONTROL_HIYERARSISI_RENK = [
        'eliminasyon' => '#047857',
        'ikame' => '#0d9488',
        'muhendislik' => '#2563eb',
        'idari' => '#b45309',
        'kkd' => '#7c3aed',
    ];

    protected $table = 'saha_bulgulari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'acik', 'kaynak' => 'manuel', 'olasilik' => 3, 'siddet' => 3];

    protected $casts = [
        'olasilik' => 'integer',
        'siddet' => 'integer',
        'hedef_olasilik' => 'integer',
        'hedef_siddet' => 'integer',
        'etkinlik_puani' => 'integer',
        'kaynak_kayit_id' => 'integer',
        'kaynak_sira' => 'integer',
        'enlem' => 'float',
        'boylam' => 'float',
        'termin' => 'date',
        'kapanis_tarihi' => 'date',
        'isveren_kapanis_talebi_at' => 'datetime',
        'isveren_ret_gecmisi' => 'array',
        'fotograflar' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (SahaBulgusu $b): void {
            $b->bulgu_no ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'bulgu_no', 'SB-'.now()->format('Y').'-', 4);
        });

        static::created(function (SahaBulgusu $b): void {
            \App\Support\DenetimKaydedici::kaydet('Saha Bulguları / DÖF', 'olusturdu', ($b->bulgu_no ?: 'Bulgu').': '.\Illuminate\Support\Str::limit((string) $b->uygunsuzluk, 120), $b->firma_id, static::class, $b->id);
        });

        // Kapatma / yeniden açma bağlı DÖF maddelerine yansır.
        static::updated(function (SahaBulgusu $b): void {
            if ($b->wasChanged('durum')) {
                \App\Support\BulguHavuzu::dofaYansit($b);
                $islem = $b->durum === 'kapandi' ? 'onayladi' : 'guncelledi';
                \App\Support\DenetimKaydedici::kaydet('Saha Bulguları / DÖF', $islem, ($b->bulgu_no ?: 'Bulgu').' durumu → '.(self::DURUMLAR[$b->durum] ?? $b->durum), $b->firma_id, static::class, $b->id);
            }
        });

        static::deleting(function (SahaBulgusu $b): void {
            \App\Support\DenetimKaydedici::kaydet('Saha Bulguları / DÖF', 'sildi', ($b->bulgu_no ?: 'Bulgu').' silindi', $b->firma_id, static::class, $b->id);
            foreach ($b->fotograflar ?? [] as $yol) {
                if (Storage::disk('public')->exists($yol)) {
                    Storage::disk('public')->delete($yol);
                }
            }
        });
    }

    public function kontrolHiyerarsisiEtiketi(bool $kisa = true): ?string
    {
        if (! $this->kontrol_hiyerarsisi) {
            return null;
        }

        return $kisa
            ? (self::KONTROL_HIYERARSISI_KISA[$this->kontrol_hiyerarsisi] ?? $this->kontrol_hiyerarsisi)
            : (self::KONTROL_HIYERARSISI[$this->kontrol_hiyerarsisi] ?? $this->kontrol_hiyerarsisi);
    }

    public function artikRiskSkoru(): ?int
    {
        if (! $this->hedef_olasilik || ! $this->hedef_siddet) {
            return null;
        }

        return (int) $this->hedef_olasilik * (int) $this->hedef_siddet;
    }

    public function isverenOnayiBekliyorMu(): bool
    {
        return $this->isveren_kapanis_talebi_at !== null && $this->durum !== 'kapandi';
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
