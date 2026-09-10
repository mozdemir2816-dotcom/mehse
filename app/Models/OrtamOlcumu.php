<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İş Hijyeni / Ortam Ölçümleri Takibi — firma başına bir kayıt. Her ölçüm satırı
 * için ölçüm tarihi + periyot girilince bir sonraki ölçüm tarihi otomatik
 * hesaplanır; ölçülen değer sınır değerle karşılaştırılıp sonuç işaretlenir.
 */
class OrtamOlcumu extends Model
{
    use HasFactory;

    protected $table = 'ortam_olcumleri';

    protected $guarded = ['id'];

    protected $casts = [
        'olcumler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrtamOlcumu $o): void {
            $o->olcumler = collect($o->olcumler ?? [])->map(function (array $m): array {
                $m = array_merge([
                    'parametre' => '', 'grup' => null, 'bolge' => null,
                    'periyot_ay' => 24, 'olcum_tarihi' => null, 'sonraki_olcum_tarihi' => null,
                    'laboratuvar' => null, 'rapor_no' => null,
                    'olculen_deger' => null, 'sinir_deger' => null, 'birim' => null,
                    'sonuc' => 'bekliyor', 'not' => null,
                ], $m);

                $m['periyot_ay'] = max(1, (int) ($m['periyot_ay'] ?: 24));
                $m['sonuc'] = array_key_exists($m['sonuc'], config('isg.ortam_olcum.sonuclar')) ? $m['sonuc'] : 'bekliyor';

                // Ölçüm tarihi girilmiş ve sonraki elle verilmemişse periyottan türet.
                if (! empty($m['olcum_tarihi']) && empty($m['sonraki_olcum_tarihi'])) {
                    $m['sonraki_olcum_tarihi'] = Carbon::parse($m['olcum_tarihi'])
                        ->addMonths($m['periyot_ay'])->toDateString();
                }

                return $m;
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
            $kayit->olcumler = [];
            $kayit->save();
        }

        return $kayit;
    }

    /** En az bir ölçüme tarih girilmiş mi (Kontrol Merkezi kriteri). */
    public function baslatilmisMi(): bool
    {
        return collect($this->olcumler ?? [])->contains(fn (array $m) => filled($m['olcum_tarihi'] ?? null));
    }

    /** Ölçümü geçmiş / verilen gün içinde dolacak ölçümler. */
    public function yaklasanlar(int $gun = 60): array
    {
        $sinir = Carbon::today()->addDays($gun);

        return collect($this->olcumler ?? [])
            ->filter(fn (array $m) => filled($m['sonraki_olcum_tarihi'] ?? null)
                && Carbon::parse($m['sonraki_olcum_tarihi'])->lte($sinir))
            ->values()->all();
    }

    public function ozet(): array
    {
        $olcumler = collect($this->olcumler ?? []);

        return [
            'toplam' => $olcumler->count(),
            'uygun' => $olcumler->where('sonuc', 'uygun')->count(),
            'bekleyen' => $olcumler->where('sonuc', 'bekliyor')->count(),
            'asim' => $olcumler->where('sonuc', 'asim')->count(),
            'yaklasan' => count($this->yaklasanlar()),
        ];
    }
}
