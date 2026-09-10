<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kaza İstatistikleri — firma × yıl. Hesaba dahil kaza sayısı, kayıp gün ve
 * aylık çalışma saatinden Kaza Sıklık Oranı ve Kaza Ağırlık Oranı hesaplanır.
 *
 *   Sıklık Oranı = (Kaza Sayısı × Çarpan) / Toplam Çalışma Saati
 *   Ağırlık Oranı = (Toplam Kayıp Gün × Çarpan) / Toplam Çalışma Saati
 *
 * Çarpan: turkiye_1m → 1.000.000 (SGK/ÇSGB), osha_200k → 200.000 (OSHA).
 * Kazalar: İş Kazası Raporları + Olay Kayıtları (iş kazası tipi) + elle
 * eklenen harici kazalar birleştirilir.
 */
class KazaIstatistigi extends Model
{
    use HasFactory;

    protected $table = 'kaza_istatistikleri';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'aylik_veriler' => 'array',
        'harici_kazalar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (KazaIstatistigi $k): void {
            $k->standart = array_key_exists($k->standart, config('isg.kaza_istatistik.standartlar'))
                ? $k->standart
                : 'turkiye_1m';

            // Aylık veriler her zaman 12 satır — {ort_calisan, calisma_saati}.
            $mevcut = collect($k->aylik_veriler ?? []);
            $k->aylik_veriler = collect(range(0, 11))->map(function (int $ay) use ($mevcut) {
                $satir = $mevcut->get($ay, []);

                return [
                    'ort_calisan' => max(0, (int) ($satir['ort_calisan'] ?? 0)),
                    'calisma_saati' => max(0, (int) ($satir['calisma_saati'] ?? 0)),
                ];
            })->all();

            $k->harici_kazalar = collect($k->harici_kazalar ?? [])
                ->map(fn (array $h): array => [
                    'tarih' => $h['tarih'] ?? null,
                    'aciklama' => $h['aciklama'] ?? null,
                    'kayip_gunu' => max(0, (int) ($h['kayip_gunu'] ?? 0)),
                    'olumlu' => (bool) ($h['olumlu'] ?? false),
                ])
                ->values()
                ->all();
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $kayit = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $kayit->exists) {
            $kayit->standart = 'turkiye_1m';
            $kayit->aylik_veriler = [];
            $kayit->harici_kazalar = [];
            $kayit->save();
        }

        return $kayit;
    }

    public function carpan(): int
    {
        return (int) config('isg.kaza_istatistik.standartlar.'.$this->standart.'.carpan', 1000000);
    }

    public function toplamCalismaSaati(): int
    {
        return collect($this->aylik_veriler ?? [])->sum('calisma_saati');
    }

    public function toplamOrtalamaCalisan(): int
    {
        $aylar = collect($this->aylik_veriler ?? [])->pluck('ort_calisan')->filter(fn ($v) => $v > 0);

        return $aylar->isEmpty() ? 0 : (int) round($aylar->avg());
    }

    /**
     * Bu yıla ait İş Kazası Raporları + iş kazası tipli Olay Kayıtları + harici
     * kazalar — ortak satır şeklinde ({kaynak, tarih, kayip_gunu, olumlu}).
     *
     * @return Collection<int, array{kaynak: string, tarih: ?string, kayip_gunu: int, olumlu: bool, aciklama: ?string}>
     */
    public function tumKazalar(): Collection
    {
        $raporlar = IsKazasiRaporu::query()
            ->where('firma_id', $this->firma_id)
            ->whereYear('kaza_tarihi', $this->yil)
            ->get()
            ->map(fn (IsKazasiRaporu $r): array => [
                'kaynak' => 'İş Kazası Raporu',
                'tarih' => $r->kaza_tarihi?->toDateString(),
                'kayip_gunu' => (int) ($r->kayip_gun_sayisi ?? 0),
                'olumlu' => $r->agirlik_derecesi === 'olumlu',
                'aciklama' => $r->belge_no,
            ]);

        $olaylar = OlayKaydi::query()
            ->where('firma_id', $this->firma_id)
            ->where('olay_tipi', 'is_kazasi')
            ->whereYear('olay_tarihi', $this->yil)
            ->get()
            ->map(fn (OlayKaydi $o): array => [
                'kaynak' => 'Olay Kaydı',
                'tarih' => $o->olay_tarihi?->toDateString(),
                'kayip_gunu' => (int) ($o->kayip_gun_sayisi ?? 0),
                'olumlu' => $o->sonuc_turu === 'olum',
                'aciklama' => $o->belge_no,
            ]);

        $harici = collect($this->harici_kazalar ?? [])->map(fn (array $h): array => [
            'kaynak' => 'Harici (elle)',
            'tarih' => $h['tarih'] ?? null,
            'kayip_gunu' => (int) ($h['kayip_gunu'] ?? 0),
            'olumlu' => (bool) ($h['olumlu'] ?? false),
            'aciklama' => $h['aciklama'] ?? null,
        ]);

        return $raporlar->concat($olaylar)->concat($harici)
            ->sortBy('tarih')
            ->values();
    }

    public function kazaSayisi(): int
    {
        return $this->tumKazalar()->count();
    }

    public function kayipZamanliKazaSayisi(): int
    {
        return $this->tumKazalar()->filter(fn (array $k) => $k['kayip_gunu'] > 0 || $k['olumlu'])->count();
    }

    public function toplamKayipGun(): int
    {
        return (int) $this->tumKazalar()->sum('kayip_gunu');
    }

    public function olumluKazaSayisi(): int
    {
        return $this->tumKazalar()->where('olumlu', true)->count();
    }

    /** Kaza Sıklık Oranı — çalışma saati yoksa null. */
    public function siklikOrani(): ?float
    {
        $saat = $this->toplamCalismaSaati();

        return $saat > 0 ? round($this->kazaSayisi() * $this->carpan() / $saat, 2) : null;
    }

    /** Kaza Ağırlık Oranı — çalışma saati yoksa null. */
    public function agirlikOrani(): ?float
    {
        $saat = $this->toplamCalismaSaati();

        return $saat > 0 ? round($this->toplamKayipGun() * $this->carpan() / $saat, 2) : null;
    }

    public function standartEtiketi(): string
    {
        return config('isg.kaza_istatistik.standartlar.'.$this->standart.'.ad', $this->standart);
    }
}
