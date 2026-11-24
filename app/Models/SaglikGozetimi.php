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
                    'sonuc' => 'bekliyor', 'rapor_no' => null, 'not' => null, 'kursun_deger' => null,
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

    /**
     * EK-2 Muayene Formu kaydedildiğinde çalışanın Sağlık Gözetimi satırını otomatik oluşturur/günceller.
     */
    public static function muayenedenSenkronla(MuayeneFormu $m): void
    {
        if (! $m->firma || blank($m->calisan_ad_soyad)) {
            return;
        }

        $gozetim = static::firmaIcin($m->firma);
        $satirlar = $gozetim->satirlar ?? [];

        $tetkikTuru = match ($m->muayene_turu) {
            'ise_giris' => 'ise_giris',
            'ise_donus' => 'ise_donus',
            default => 'periyodik',
        };
        $sonuc = match ($m->sonuc_kanaati) {
            'uygun' => 'uygun',
            'sartli_uygun' => 'sartli',
            'uygun_degil' => 'uygun_degil',
            default => 'bekliyor',
        };

        $adNorm = mb_strtolower(trim((string) $m->calisan_ad_soyad), 'UTF-8');
        $indeks = null;

        foreach ($satirlar as $i => $s) {
            if (($s['tetkik_turu'] ?? null) !== $tetkikTuru) {
                continue;
            }
            if ($m->calisan_id && (int) ($s['calisan_id'] ?? 0) === (int) $m->calisan_id) {
                $indeks = $i;
                break;
            }
            if (! $m->calisan_id && mb_strtolower(trim((string) ($s['calisan_adi'] ?? '')), 'UTF-8') === $adNorm) {
                $indeks = $i;
                break;
            }
        }

        $guncel = [
            'calisan_id' => $m->calisan_id,
            'calisan_adi' => $m->calisan_ad_soyad,
            'gorev' => $m->calisan_gorevi,
            'tetkik_turu' => $tetkikTuru,
            'tarih' => $m->muayene_tarihi?->toDateString(),
            'sonraki_tarih' => $m->onerilen_kontrol_tarihi?->toDateString(),
            'sonuc' => $sonuc,
            'rapor_no' => $m->belge_no,
        ];

        if ($indeks !== null) {
            $satirlar[$indeks] = array_merge($satirlar[$indeks], array_filter($guncel, fn ($v) => $v !== null));
        } else {
            $satirlar[] = $guncel;
        }

        $gozetim->update(['satirlar' => array_values($satirlar)]);
    }
}
