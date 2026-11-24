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
            $hamEski = $o->getRawOriginal('olcumler');
            $eskiListe = is_string($hamEski) ? (json_decode($hamEski, true) ?: []) : (is_array($hamEski) ? $hamEski : []);

            $o->olcumler = collect($o->olcumler ?? [])->values()->map(function (array $m, int $i) use ($eskiListe): array {
                $m = array_merge([
                    'parametre' => '', 'grup' => null, 'bolge' => null,
                    'periyot_ay' => 24, 'olcum_tarihi' => null, 'sonraki_olcum_tarihi' => null,
                    'laboratuvar' => null, 'yetki_no' => null, 'rapor_no' => null,
                    'olculen_deger' => null, 'sinir_deger' => null, 'birim' => null,
                    'sonuc' => 'bekliyor', 'not' => null, 'gecmis' => [],
                ], $m);

                $m['periyot_ay'] = max(1, (int) ($m['periyot_ay'] ?: 24));
                $m['sonuc'] = array_key_exists($m['sonuc'], config('isg.ortam_olcum.sonuclar')) ? $m['sonuc'] : 'bekliyor';
                $m['gecmis'] = array_values((array) ($m['gecmis'] ?? []));

                // Değer + sınır sayısal ve sonuç henüz seçilmemişse otomatik belirle.
                if ($m['sonuc'] === 'bekliyor' && ($oto = static::otomatikSonuc($m['olculen_deger'], $m['sinir_deger'], $m['birim'] ?? null, $m['parametre'] ?? null))) {
                    $m['sonuc'] = $oto;
                }

                // Ölçüm tarihi / periyot değiştiğinde (sonraki tarih elle değiştirilmediyse) yeniden hesapla.
                $eski = is_array($eskiListe[$i] ?? null) ? $eskiListe[$i] : null;
                $tarihVeyaPeriyotDegisti = $eski !== null
                    && (($eski['olcum_tarihi'] ?? null) !== $m['olcum_tarihi'] || (int) ($eski['periyot_ay'] ?? 24) !== $m['periyot_ay'])
                    && ($eski['sonraki_olcum_tarihi'] ?? null) === $m['sonraki_olcum_tarihi'];

                if (! empty($m['olcum_tarihi']) && (empty($m['sonraki_olcum_tarihi']) || $tarihVeyaPeriyotDegisti)) {
                    $m['sonraki_olcum_tarihi'] = Carbon::parse($m['olcum_tarihi'])
                        ->addMonthsNoOverflow($m['periyot_ay'])->toDateString();
                }

                return $m;
            })->values()->all();
        });
    }

    /**
     * Ölçülen değer sınır değerle karşılaştırılır (ikisi de sayıysa):
     * - Gürültü / dB(A) ölçümleri logaritmik ölçektedir (Gürültü Yön. m.5):
     *   sınır üstü → asim, ≥ 80 dB(A) (en düşük maruziyet eylem değeri) → sinir, < 80 → uygun.
     * - Diğer lineer ölçümlerde: sınırı aşan → asim, sınırın %80'i ve üstü → sinir, altı → uygun.
     * Sınırı "—" olan (ör. aydınlatma — alt sınır) ölçümlerde null döner, elle seçilir.
     */
    public static function otomatikSonuc(mixed $olculen, mixed $sinir, ?string $birim = null, ?string $parametre = null): ?string
    {
        $sayi = fn ($v) => is_numeric($v = str_replace(',', '.', trim((string) $v))) ? (float) $v : null;
        $o = $sayi($olculen);
        $s = $sayi($sinir);

        if ($o === null || $s === null || $s <= 0) {
            return null;
        }

        $gurultuMu = str_contains(mb_strtolower((string) $birim, 'UTF-8'), 'db')
            || str_contains(mb_strtolower((string) $parametre, 'UTF-8'), 'gürültü')
            || str_contains(mb_strtolower((string) $parametre, 'UTF-8'), 'gurultu');

        if ($gurultuMu) {
            return match (true) {
                $o > $s => 'asim',
                $o >= 80.0 => 'sinir',
                default => 'uygun',
            };
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
