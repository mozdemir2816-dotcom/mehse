<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kontrol Kartı (14.11.2026) — alan bazlı kontrol listesi: tek fotoğraf üzerinde
 * numaralı işaretler, her madde Uygun / Uygunsuz / Kapsam dışı; uygunsuz
 * maddede Fine-Kinney önce (mevcut) ve sonra (ek önlemle) değerlendirmesi.
 * Şablonlar config/kontrol_kartlari.php.
 */
class KontrolKarti extends Model
{
    public const UYGUN = 'uygun';

    public const UYGUNSUZ = 'uygunsuz';

    public const KAPSAM_DISI = 'kapsam_disi';

    public const DURUMLAR = [
        self::UYGUN => 'Uygun',
        self::UYGUNSUZ => 'Uygunsuz',
        self::KAPSAM_DISI => 'Kapsam dışı',
    ];

    protected $table = 'kontrol_kartlari';

    protected $guarded = ['id'];

    protected $casts = [
        'maddeler' => 'array',
        'belge_tarihi' => 'date',
        'kontrol_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (KontrolKarti $k): void {
            $k->belge_no ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'belge_no', 'KK-'.now()->format('Y').'-', 3);
            $k->belge_tarihi ??= now()->toDateString();
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /**
     * Hazır şablonlar (config) + Gemini'den eklenen şablonlar (kontrol_karti_sablonlari).
     *
     * @return array<string, string> anahtar => ad
     */
    public static function sablonSecenekleri(): array
    {
        $hazir = collect(config('kontrol_kartlari', []))->map(fn ($s) => $s['ad'])->all();

        try {
            $eklenen = KontrolKartiSablonu::query()->orderBy('ad')->pluck('ad', 'anahtar')->all();
        } catch (\Illuminate\Database\QueryException) {
            $eklenen = [];   // tablo henüz kurulmadıysa (canlı kurulumdan önce)
        }

        return $hazir + $eklenen;
    }

    /** @return array<string, mixed>|null */
    public static function sablon(?string $anahtar): ?array
    {
        if (! $anahtar) {
            return null;
        }

        if ($hazir = config('kontrol_kartlari.'.$anahtar)) {
            return $hazir;
        }

        try {
            return KontrolKartiSablonu::where('anahtar', $anahtar)->first()?->sablonDizisi();
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }
    }

    /**
     * Şablon maddelerinden boş form durumu: durum seçilmemiş, işaret yok,
     * risk alanları şablonun önerdiği değerlerle dolu (uzman değiştirir).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function bosMaddeler(?string $anahtar): array
    {
        return collect(static::sablon($anahtar)['maddeler'] ?? [])->map(fn (array $m) => [
            'kod' => $m['kod'],
            'baslik' => $m['baslik'],
            'kontrol' => $m['kontrol'],
            'durum' => null,
            'isaret' => null,
            'tespit' => $m['tespit'] ?? '',
            'olasi_sonuc' => $m['olasi_sonuc'] ?? '',
            'etkilenenler' => $m['etkilenenler'] ?? 'Tüm çalışanlar',
            'mevcut_onlem' => 'Yok',
            'olasilik' => (string) ($m['once'][0] ?? ''),
            'frekans' => (string) ($m['once'][1] ?? ''),
            'siddet' => (string) ($m['once'][2] ?? ''),
            'oneri' => $m['oneri'] ?? '',
            'sorumlu' => $m['sorumlu'] ?? 'İşveren / İşveren Vekili',
            'hedef_gun' => (string) ($m['hedef_gun'] ?? 30),
            'yeni_olasilik' => (string) ($m['sonra'][0] ?? ''),
            'yeni_frekans' => (string) ($m['sonra'][1] ?? ''),
            'yeni_siddet' => (string) ($m['sonra'][2] ?? ''),
            'yonetmelik' => $m['yonetmelik'] ?? '',
            'standart' => $m['standart'] ?? '',
        ])->values()->all();
    }

    /** Mevcut durum skoru (O × F × Ş). */
    public static function skor(array $m): ?float
    {
        return SahaAnalizi::fineKinneySkoru($m);
    }

    /** Ek önlem sonrası beklenen skor. */
    public static function yeniSkor(array $m): ?float
    {
        return SahaAnalizi::fineKinneySkoru([
            'olasilik' => $m['yeni_olasilik'] ?? null,
            'frekans' => $m['yeni_frekans'] ?? null,
            'siddet' => $m['yeni_siddet'] ?? null,
        ]);
    }

    /** @return array{uygun: int, uygunsuz: int, kapsam_disi: int, bos: int, toplam: int} */
    public static function sayim(array $maddeler): array
    {
        $durumlar = array_column($maddeler, 'durum');

        return [
            'uygun' => count(array_keys($durumlar, self::UYGUN, true)),
            'uygunsuz' => count(array_keys($durumlar, self::UYGUNSUZ, true)),
            'kapsam_disi' => count(array_keys($durumlar, self::KAPSAM_DISI, true)),
            'bos' => count(array_filter($durumlar, fn ($d) => $d === null || $d === '')),
            'toplam' => count($maddeler),
        ];
    }

    public function sablonAdi(): string
    {
        return static::sablon($this->sablon)['ad'] ?? $this->sablon;
    }
}
