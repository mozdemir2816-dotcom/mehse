<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kimyasal Risk Değerlendirmesi — firma başına bir kayıt. Her satır bir kimyasal
 * ürünü değerlendirir; tehlike grubu × miktar × uçuculuk'tan kontrol yaklaşımı
 * (1-4) otomatik hesaplanır (COSHH Essentials yaklaşımı).
 */
class KimyasalRiskDegerlendirmesi extends Model
{
    use HasFactory;

    protected $table = 'kimyasal_risk_degerlendirmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'satirlar' => 'array',
        'degerlendirme_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (KimyasalRiskDegerlendirmesi $d): void {
            $d->satirlar = collect($d->satirlar ?? [])->map(function (array $s): array {
                $s = array_merge([
                    'kimyasal_urun_id' => null, 'kimyasal_adi' => '', 'kullanim_alani' => null,
                    'tehlike_grubu' => 'A', 'deri_goz_yolu' => false,
                    'miktar' => 'az', 'ucuculuk' => 'dusuk',
                    'maruziyet_yollari' => [], 'cmr' => false,
                    'kontrol_yaklasimi' => 1, 'alinan_onlemler' => null,
                    'artik_risk' => 'dusuk', 'not' => null,
                ], $s);

                $s['kontrol_yaklasimi'] = static::kontrolYaklasimi(
                    $s['tehlike_grubu'], $s['miktar'], $s['ucuculuk'], (bool) $s['cmr'],
                );

                return $s;
            })
                ->filter(fn (array $s) => trim((string) $s['kimyasal_adi']) !== '')
                ->values()
                ->all();
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kayit = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kayit->exists) {
            $kayit->satirlar = [];
            $kayit->save();
        }

        return $kayit;
    }

    /**
     * COSHH Essentials benzeri kontrol bantlama. Tehlike (A=0..E=4) + miktar
     * (az=0..cok=2) + uçuculuk (dusuk=0..yuksek=2) toplamı 0-8 → yaklaşım 1-4.
     * E grubu ve CMR maddeler en az yaklaşım 3; E + yüksek maruziyet → 4.
     */
    public static function kontrolYaklasimi(string $grup, string $miktar, string $ucuculuk, bool $cmr = false): int
    {
        $tehlikePuan = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'S' => 1][$grup] ?? 1;
        $miktarPuan = ['az' => 0, 'orta' => 1, 'cok' => 2][$miktar] ?? 0;
        $ucucuPuan = ['dusuk' => 0, 'orta' => 1, 'yuksek' => 2][$ucuculuk] ?? 0;

        $toplam = $tehlikePuan + $miktarPuan + $ucucuPuan;

        $yaklasim = match (true) {
            $toplam <= 1 => 1,
            $toplam <= 3 => 2,
            $toplam <= 5 => 3,
            default => 4,
        };

        if ($cmr || $grup === 'E') {
            $yaklasim = max($yaklasim, 3);
        }

        if ($grup === 'E' && ($miktarPuan + $ucucuPuan) >= 3) {
            $yaklasim = 4;
        }

        return $yaklasim;
    }

    public function doluMu(): bool
    {
        return filled($this->satirlar);
    }

    public function ozet(): array
    {
        $satirlar = collect($this->satirlar ?? []);

        return [
            'toplam' => $satirlar->count(),
            'yaklasim3_4' => $satirlar->whereIn('kontrol_yaklasimi', [3, 4])->count(),
            'cmr' => $satirlar->where('cmr', true)->count(),
        ];
    }
}
