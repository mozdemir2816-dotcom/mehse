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
            $plan->faaliyetler = static::maddeAylarIle(config('isg.yillik_plan.varsayilan_faaliyetler'));
            $plan->egitimler = static::maddeAylarIle(config('isg.yillik_plan.varsayilan_egitimler'));
            $plan->degerlendirmeler = collect(config('isg.yillik_plan.varsayilan_degerlendirmeler'))
                ->map(fn ($d) => [...$d, 'tarih' => null, 'tekrar_sayisi' => null])
                ->all();
            $plan->save();
        }

        return $plan;
    }

    /** @return array<int, array<string, mixed>> */
    private static function maddeAylarIle(array $maddeler): array
    {
        return collect($maddeler)
            ->map(fn ($m) => [...$m, 'aylar' => array_fill(0, 12, 'bos')])
            ->all();
    }
}
