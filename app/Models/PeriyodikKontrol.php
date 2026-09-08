<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İş Ekipmanları Periyodik Kontrol takibi — firma başına bir kayıt. Ekipman
 * listesi kapasite raporundan girilir; her satır için son kontrol tarihi + sonuç
 * girilince sonraki kontrol tarihi periyoda göre otomatik hesaplanır (EK-3).
 */
class PeriyodikKontrol extends Model
{
    use HasFactory;

    protected $table = 'periyodik_kontroller';

    protected $guarded = ['id'];

    protected $casts = [
        'ekipmanlar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (PeriyodikKontrol $k): void {
            $k->ekipmanlar = collect($k->ekipmanlar ?? [])->map(function (array $e): array {
                $e = array_merge([
                    'ad' => '', 'kategori' => null, 'adet' => 1, 'tanim' => null,
                    'periyot_ay' => 12, 'son_kontrol_tarihi' => null, 'kontrol_eden' => null,
                    'rapor_no' => null, 'sonuc' => 'bekliyor', 'sonraki_kontrol_tarihi' => null, 'not' => null,
                ], $e);

                $e['periyot_ay'] = max(1, (int) ($e['periyot_ay'] ?: 12));
                $e['sonuc'] = array_key_exists($e['sonuc'], config('isg.periyodik_kontrol.sonuclar')) ? $e['sonuc'] : 'bekliyor';

                // Son kontrol tarihi girilmiş ve sonraki elle verilmemişse periyottan türet.
                if (! empty($e['son_kontrol_tarihi']) && empty($e['sonraki_kontrol_tarihi'])) {
                    $e['sonraki_kontrol_tarihi'] = Carbon::parse($e['son_kontrol_tarihi'])
                        ->addMonths($e['periyot_ay'])->toDateString();
                }

                return $e;
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
            $kayit->ekipmanlar = [];
            $kayit->save();
        }

        return $kayit;
    }

    /** En az bir ekipmana kontrol tarihi girilmiş mi (Kontrol Merkezi kriteri). */
    public function baslatilmisMi(): bool
    {
        return collect($this->ekipmanlar ?? [])->contains(fn (array $e) => filled($e['son_kontrol_tarihi'] ?? null));
    }

    /** Kontrolü geçmiş / 30 gün içinde dolacak ekipmanlar. */
    public function yaklasanlar(int $gun = 30): array
    {
        $sinir = Carbon::today()->addDays($gun);

        return collect($this->ekipmanlar ?? [])
            ->filter(fn (array $e) => filled($e['sonraki_kontrol_tarihi'] ?? null)
                && Carbon::parse($e['sonraki_kontrol_tarihi'])->lte($sinir))
            ->values()->all();
    }

    public function ozet(): array
    {
        $ekipmanlar = collect($this->ekipmanlar ?? []);

        return [
            'toplam' => $ekipmanlar->count(),
            'uygun' => $ekipmanlar->where('sonuc', 'uygun')->count(),
            'bekleyen' => $ekipmanlar->where('sonuc', 'bekliyor')->count(),
            'uygun_degil' => $ekipmanlar->where('sonuc', 'uygun_degil')->count(),
            'yaklasan' => count($this->yaklasanlar()),
        ];
    }
}
