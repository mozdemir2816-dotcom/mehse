<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sağlık Gözetimi Takibi — firma başına bir kayıt. Her satır bir çalışanın bir
 * sağlık tetkiki kaydıdır; tetkik tarihi + periyot girilince sonraki tetkik
 * tarihi otomatik hesaplanır (periyot yoksa tehlike sınıfına göre yıl bazlı).
 */
class SaglikGozetimi extends Model
{
    use HasFactory;

    protected $table = 'saglik_gozetimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'satirlar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (SaglikGozetimi $g): void {
            $tehlike = $g->firma?->tehlike_sinifi ?? 'tehlikeli';
            $periyodikYil = (int) config('isg.saglik_periyodik_yili.'.$tehlike, 3);

            $g->satirlar = collect($g->satirlar ?? [])->map(function (array $s) use ($periyodikYil): array {
                $s = array_merge([
                    'calisan_id' => null, 'calisan_adi' => '', 'gorev' => null,
                    'tetkik_turu' => 'periyodik', 'tarih' => null, 'sonraki_tarih' => null,
                    'sonuc' => 'bekliyor', 'rapor_no' => null, 'not' => null,
                ], $s);

                $s['sonuc'] = array_key_exists($s['sonuc'], config('isg.saglik_tetkik.sonuclar')) ? $s['sonuc'] : 'bekliyor';

                if (! empty($s['tarih']) && empty($s['sonraki_tarih'])) {
                    $tanim = config('isg.saglik_tetkik.turleri.'.$s['tetkik_turu']);
                    $ay = $tanim['periyot_ay'] ?? null;

                    $s['sonraki_tarih'] = $ay !== null
                        ? Carbon::parse($s['tarih'])->addMonths($ay)->toDateString()
                        : Carbon::parse($s['tarih'])->addYears($periyodikYil)->toDateString();
                }

                return $s;
            })->values()->all();
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

    public function baslatilmisMi(): bool
    {
        return collect($this->satirlar ?? [])->contains(fn (array $s) => filled($s['tarih'] ?? null));
    }

    /** @return array<int, array<string, mixed>> Süresi geçmiş / verilen gün içinde dolacak tetkikler. */
    public function yaklasanlar(int $gun = 60): array
    {
        $sinir = Carbon::today()->addDays($gun);

        return collect($this->satirlar ?? [])
            ->filter(fn (array $s) => filled($s['sonraki_tarih'] ?? null)
                && Carbon::parse($s['sonraki_tarih'])->lte($sinir))
            ->values()->all();
    }

    public function ozet(): array
    {
        $satirlar = collect($this->satirlar ?? []);

        return [
            'toplam' => $satirlar->count(),
            'gecerli' => $satirlar->where('sonuc', 'uygun')->count(),
            'bekleyen' => $satirlar->where('sonuc', 'bekliyor')->count(),
            'yaklasan' => count($this->yaklasanlar()),
        ];
    }
}
