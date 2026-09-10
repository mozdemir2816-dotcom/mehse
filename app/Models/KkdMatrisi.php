<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KKD Seçim Matrisi — firma başına bir kayıt. Her satır bir iş kalemi/görevdir;
 * `config isg.kkd_matris.sutunlar` anahtarları (baret, gozluk, kulaklik, maske,
 * eldiven, ayakkabi, yelek, kemer, diger) için gereklilik metni tutar.
 */
class KkdMatrisi extends Model
{
    use HasFactory;

    protected $table = 'kkd_matrisleri';

    protected $guarded = ['id'];

    protected $casts = [
        'satirlar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (KkdMatrisi $m): void {
            $sutunlar = array_keys(config('isg.kkd_matris.sutunlar'));

            $m->satirlar = collect($m->satirlar ?? [])
                ->map(function (array $s) use ($sutunlar): array {
                    $temiz = ['is_kalemi' => trim((string) ($s['is_kalemi'] ?? '')), 'grup' => $s['grup'] ?? null];

                    foreach ($sutunlar as $sutun) {
                        $temiz[$sutun] = isset($s[$sutun]) ? trim((string) $s[$sutun]) : '';
                    }

                    return $temiz;
                })
                ->filter(fn (array $s) => $s['is_kalemi'] !== '')
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

    public function doluMu(): bool
    {
        return filled($this->satirlar);
    }
}
