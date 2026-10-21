<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * KKD Seçim Matrisi — firma başına bir kayıt. Her satır bir iş kalemi/görevdir;
 * `config isg.kkd_matris.sutunlar` anahtarları (baret, gozluk, kulaklik, maske,
 * eldiven, ayakkabi, yelek, kemer, diger) için gereklilik metni tutar.
 */
class KkdMatrisi extends Model
{
    use HasFactory;

    protected $table = 'kkd_matrisleri';

    protected $guarded = ['id'];

    protected $casts = [
        'satirlar' => 'array',
        'gecerli_bolumler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (KkdMatrisi $m): void {
            $sutunlar = array_keys(config('isg.kkd_matris.sutunlar'));

            $m->satirlar = collect($m->satirlar ?? [])
                ->map(function (array $s) use ($sutunlar): array {
                    $temiz = ['is_kalemi' => trim((string) ($s['is_kalemi'] ?? '')), 'grup' => $s['grup'] ?? null];

                    foreach ($sutunlar as $sutun) {
                        $temiz[$sutun] = isset($s[$sutun]) ? trim((string) $s[$sutun]) : '';
                    }

                    return $temiz;
                })
                ->filter(fn (array $s) => $s['is_kalemi'] !== '')
                ->values()
                ->all();
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
            $kayit->gecerli_bolumler = static::onerilenBolumler($firma->nace_kodu);
            $kayit->save();
        }

        return $kayit;
    }

    /**
     * Firma NACE koduna göre `config/kkd_teknik_standartlar.php`'deki
     * `nace_eslesme` tablosundan öngörülen faaliyet bölümleri (önek eşleşmesi,
     * örn. "25.62.00" → "25.62"). Yalnız firmanın İLK KKD Matrisi kaydı
     * oluşturulurken varsayılan olarak uygulanır (bkz. firmaIcin()).
     *
     * @return array<int, string>
     */
    public static function onerilenBolumler(?string $naceKodu): array
    {
        if (blank($naceKodu)) {
            return [];
        }

        $sonuc = [];

        foreach ((array) config('kkd_teknik_standartlar.nace_eslesme', []) as $onek => $bolumler) {
            if (str_starts_with($naceKodu, $onek)) {
                $sonuc = [...$sonuc, ...$bolumler];
            }
        }

        return array_values(array_unique($sonuc));
    }

    public function doluMu(): bool
    {
        return filled($this->satirlar);
    }
}
