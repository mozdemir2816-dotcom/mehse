<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Saha Analizi → Saha Gözlem Raporu — isgpratik AI SAHA ANALİZİ/1-6.jpg,
 * 03.10.2026'dan itibaren isgsuite "Saha Gözlem Raporu" akışı: taslak →
 * tamamlandı (kilitli), madde başına Fine-Kinney ve DÖF bayrağı.
 */
class SahaAnalizi extends Model
{
    protected $table = 'saha_analizleri';

    protected $guarded = ['id'];

    public const TASLAK = 'taslak';

    public const TAMAMLANDI = 'tamamlandi';

    protected $casts = [
        'rapor_tarihi' => 'date',
        'tamamlanma_tarihi' => 'datetime',
        'bulgular' => 'array',
        'fotograflar' => 'array',
        'mevzuat_referanslari' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (SahaAnalizi $s): void {
            $s->belge_no ??= 'SAHA-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });

        // Taslak maddeler sık değişir; rapor tamamlanınca ortak bulgulara bağlanır.
        static::saved(function (SahaAnalizi $s): void {
            if ($s->tamamlandiMi()) {
                \App\Support\BulguHavuzu::esle($s);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function dofRaporu(): BelongsTo
    {
        return $this->belongsTo(DofRaporu::class);
    }

    public function tamamlandiMi(): bool
    {
        return $this->durum === self::TAMAMLANDI;
    }

    /** Fine-Kinney skoru: Olasılık × Frekans × Şiddet (değer eksikse null). */
    public static function fineKinneySkoru(array $bulgu): ?float
    {
        $o = $bulgu['olasilik'] ?? null;
        $f = $bulgu['frekans'] ?? null;
        $s = $bulgu['siddet'] ?? null;

        if (blank($o) || blank($f) || blank($s)) {
            return null;
        }

        return round((float) $o * (float) $f * (float) $s, 2);
    }

    /** @return array{min: int, ad: string, renk: string, eylem: string}|null config isg.risk_fine_kinney.bantlar */
    public static function fineKinneyBandi(?float $skor): ?array
    {
        if ($skor === null) {
            return null;
        }

        return collect(config('isg.risk_fine_kinney.bantlar'))->first(fn ($b) => $skor >= $b['min']);
    }

    /** Eski 1–4 risk derecesi (yatay Gözetim Raporu ve DÖF önceliği) skordan türetilir. */
    public static function skordanDerece(?float $skor, int $varsayilan = 3): int
    {
        return match (true) {
            $skor === null => $varsayilan,
            $skor >= 400 => 1,
            $skor >= 200 => 2,
            $skor >= 70 => 3,
            default => 4,
        };
    }

    /** Ölçek değerinin etiketi — config anahtarları string ("0.5", "6"). */
    public static function olcekEtiketi(string $olcek, mixed $deger): ?string
    {
        if (blank($deger)) {
            return null;
        }

        foreach (config('isg.risk_fine_kinney.'.$olcek, []) as $k => $etiket) {
            if ((float) $k === (float) $deger) {
                return $etiket;
            }
        }

        return null;
    }

    /** Bir ölçekte verilen sayıya en yakın geçerli değer (AI serbest sayı döndürebilir). */
    public static function olcegeYuvarla(string $olcek, mixed $deger): ?string
    {
        if (! is_numeric($deger)) {
            return null;
        }

        $anahtarlar = array_map('strval', array_keys(config('isg.risk_fine_kinney.'.$olcek, [])));

        return collect($anahtarlar)->sortBy(fn ($k) => abs((float) $k - (float) $deger))->first();
    }

    public static function riskDerecesiEtiketi(?int $derece): string
    {
        return config('isg.saha_analiz.risk_dereceleri.'.$derece, (string) $derece);
    }
}
