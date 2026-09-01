<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Acil Durum Eylem Planı — firma başına bir kayıt. Konu sayfaları, destek
 * ekipleri ve kapak çerçevesi seçilir; `AcilDurumPlaniUretici` PDF üretir.
 */
class AcilDurumPlani extends Model
{
    use HasFactory;

    protected $table = 'acil_durum_planlari';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'gecerlilik_tarihi' => 'date',
        'konular' => 'array',
        'ekipler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (AcilDurumPlani $p): void {
            $p->dokuman_no ??= 'AD-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 2, '0', STR_PAD_LEFT);
            $p->kapak_cercevesi ??= 'klasik';

            if (empty($p->konular)) {
                $p->konular = collect(config('isg.acil_durum.konular'))
                    ->where('varsayilan', true)->pluck('anahtar')->all();
            }

            if ($p->rapor_tarihi && ! $p->gecerlilik_tarihi) {
                $sinif = $p->firma?->tehlike_sinifi ?? 'az_tehlikeli';
                $yil = config('isg.acil_durum.gecerlilik_yili.'.$sinif, 6);
                $p->gecerlilik_tarihi = Carbon::parse($p->rapor_tarihi)->addYears($yil);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Seçili konuların ad listesi (config sırasında). */
    public function konuAdlari(): array
    {
        return collect(config('isg.acil_durum.konular'))
            ->whereIn('anahtar', $this->konular ?? [])
            ->pluck('ad')
            ->all();
    }

    /** @return array<string, array<int, string>> ekip anahtarı => isimler */
    public function ekipListesi(): array
    {
        $ekipler = $this->ekipler ?? [];

        return collect(config('isg.acil_durum.ekipler'))
            ->mapWithKeys(fn ($ad, $anahtar) => [$anahtar => array_values(array_filter($ekipler[$anahtar] ?? []))])
            ->all();
    }

    public static function firmaIcin(Firma $firma): self
    {
        $plan = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $plan->exists) {
            $plan->rapor_tarihi = now();
            $plan->save();
        }

        return $plan;
    }
}
