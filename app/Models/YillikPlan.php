<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yıllık Çalışma Planı — isgpratik 86-87.jpg. Firma + yıl başına bir kayıt.
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
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaYilIcin(Firma $firma, int $yil): self
    {
        $plan = static::firstOrNew(['firma_id' => $firma->id, 'yil' => $yil]);

        if (! $plan->exists) {
            $plan->faaliyetler = collect(config('isg.yillik_plan.varsayilan_faaliyetler'))
                ->map(fn ($f) => [...$f, 'aylar' => array_fill(0, 12, 'bos')])
                ->all();
            $plan->save();
        }

        return $plan;
    }
}
