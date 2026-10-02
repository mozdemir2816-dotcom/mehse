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
                    'sonuc' => 'bekliyor', 'not' => null, 'gecmis' => [],
                ], $m);

                $m['periyot_ay'] = max(1, (int) ($m['periyot_ay'] ?: 24));
                $m['sonuc'] = array_key_exists($m['sonuc'], config('isg.ortam_olcum.sonuclar')) ? $m['sonuc'] : 'bekliyor';
                $m['gecmis'] = array_values((array) ($m['gecmis'] ?? []));

                // Değer + sınır sayısal ve sonuç henüz seçilmemişse otomatik belirle.
                if ($m['sonuc'] === 'bekliyor' && ($oto = static::otomatikSonuc($m['olculen_deger'], $m['sinir_deger']))) {
                    $m['sonuc'] = $oto;
                }

                // Ölçüm tarihi girilmiş ve sonraki elle verilmemişse periyottan türet.
                if (! empty($m['olcum_tarihi']) && empty($m['sonraki_olcum_tarihi'])) {
                    $m['sonraki_olcum_tarihi'] = Carbon::parse($m['olcum_tarihi'])
                        ->addMonths($m['periyot_ay'])->toDateString();
                }

                return $m;
            })->values()->all();
        });
    }

    /**
     * Ölçülen değer sınır değerle karşılaştırılır (ikisi de sayıysa): sınırı
     * aşan → asim, sınırın %80'i ve üstü → sinir, altı → uygun. Sınırı "—"
     * olan (ör. aydınlatma — alt sınır) ölçümlerde null döner, elle seçilir.
     */
    public static function otomatikSonuc(mixed $olculen, mixed $sinir): ?string
    {
        $sayi = fn ($v) => is_numeric($v = str_replace(',', '.', trim((string) $v))) ? (float) $v : null;
        $o = $sayi($olculen);
        $s = $sayi($sinir);

        if ($o === null || $s === null || $s <= 0) {
            return null;
        }

        return match (true) {
            $o > $s => 'asim',
            $o >= $s * (float) config('isg.ortam_olcum.sinir_yakin_orani', 0.8) => 'sinir',
            default => 'uygun',
        };
    }

    /** Ölçüm satırının termin durumu: olculmedi | gecikmis | yaklasan | guncel. */
    public static function terminDurumu(array $m): string
    {
        if (empty($m['olcum_tarihi'])) {
            return 'olculmedi';
        }

        if (empty($m['sonraki_olcum_tarihi'])) {
            return 'guncel';
        }

        $kalan = (int) Carbon::today()->diffInDays(Carbon::parse($m['sonraki_olcum_tarihi']), false);

        return match (true) {
            $kalan < 0 => 'gecikmis',
            $kalan <= (int) config('isg.ortam_olcum.yaklasan_gun', 60) => 'yaklasan',
            default => 'guncel',
        };
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
            'gecikmis' => $olcumler->filter(fn (array $m) => static::terminDurumu($m) === 'gecikmis')->count(),
            'olculmedi' => $olcumler->filter(fn (array $m) => static::terminDurumu($m) === 'olculmedi')->count(),
        ];
    }
}
