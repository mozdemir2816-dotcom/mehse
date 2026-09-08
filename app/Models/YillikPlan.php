<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yıllık Planlar — isgpratik 86-90.jpg. Firma + yıl başına bir kayıt, 3
 * sekme: Çalışma Planı / Eğitim Planı (ikisi de ay durum matrisli) /
 * Değerlendirme Raporu (satır bazlı serbest metin, ay matrisi yok).
 */
class YillikPlan extends Model
{
    use HasFactory;

    protected $table = 'yillik_planlar';

    protected $guarded = ['id'];

    protected $casts = [
        'yil' => 'integer',
        'baslangic_ayi' => 'integer',
        'faaliyetler' => 'array',
        'egitimler' => 'array',
        'degerlendirmeler' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $plan = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $plan->exists) {
            $kilitAy = $firma->planKilitAyIndeksi($yil);

            $plan->faaliyetler = static::maddeAylarIle(config('isg.yillik_plan.varsayilan_faaliyetler'), $kilitAy);
            $plan->egitimler = static::maddeAylarIle(config('isg.yillik_plan.varsayilan_egitimler'), $kilitAy);
            $plan->degerlendirmeler = collect(config('isg.yillik_plan.varsayilan_degerlendirmeler'))
                ->map(fn ($d) => [...$d, 'tarih' => null, 'tekrar_sayisi' => null])
                ->all();
            $plan->save();
        }

        return $plan;
    }

    /**
     * Varsayılan maddelere 12 aylık durum dizisi ekler. `varsayilan_aylar`
     * (0-11) verilenler otomatik "Planlandı" işaretlenir — ancak atanmış uzman
     * öncesindeki ($kilitAy) aylar boş bırakılır: kullanıcı veri girmeden plan
     * dolu gelsin, sonra gerekirse düzeltsin.
     *
     * @param  array<int, array<string, mixed>>  $maddeler
     * @return array<int, array<string, mixed>>
     */
    public static function maddeAylarIle(array $maddeler, int $kilitAy = 0): array
    {
        return collect($maddeler)
            ->map(function (array $m) use ($kilitAy): array {
                $aylar = array_fill(0, 12, 'bos');

                foreach ($m['varsayilan_aylar'] ?? [] as $ay) {
                    if ($ay >= $kilitAy && $ay <= 11) {
                        $aylar[$ay] = 'planlandi';
                    }
                }

                return [...$m, 'aylar' => $aylar];
            })
            ->all();
    }
}
