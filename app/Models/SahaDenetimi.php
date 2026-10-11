<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saha Denetimi ("Şantiye Denetim ve Değerlendirme") — isgpratik SAHA
 * DENETİMİ/1-15.jpg + gerçek örnek PDF.
 */
class SahaDenetimi extends Model
{
    protected $table = 'saha_denetimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'denetim_tarihi' => 'date',
        'cevaplar' => 'array',
        'ekip_uyeleri' => 'array',
        'uygunluk_yuzdesi' => 'float',
        'kritik_uygunsuzluk_var' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Tamamlanan denetimin "uygun değil" maddeleri Saha Bulguları'na düşer
        // (saha kontrolleri 5. aşama, 08.10.2026); taslak bulgu açmaz.
        static::saved(function (SahaDenetimi $d): void {
            if ($d->durum === 'tamamlandi') {
                \App\Support\BulguHavuzu::esle($d);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function sonucEtiketi(): string
    {
        if ($this->kritik_uygunsuzluk_var) {
            return 'Kritik Uygunsuzluk Var';
        }

        $uygunsuzVar = collect($this->cevaplar ?? [])->contains('sonuc', 'uygun_degil');

        return $uygunsuzVar ? 'Uygunsuzluk Var' : 'Uygun';
    }

    public function belgeAdi(): string
    {
        return config('isg.saha_denetimi.dokuman_kodu').' · Revizyon '.$this->revizyon;
    }

    public function sektorEtiketi(): ?string
    {
        return $this->sektor_anahtari
            ? config('isg.risk_ai.sektorler.'.$this->sektor_anahtari.'.ad', $this->sektor_anahtari)
            : null;
    }

    /**
     * Bir cevap maddesinin fotoğraf yolları. Eski kayıtlarda yalnız tekil
     * 'foto_yolu' vardır; yenilerde 'fotolar' (madde başına birden fazla kanıt).
     *
     * @return array<int, string>
     */
    public static function maddeFotolari(array $cevap): array
    {
        $fotolar = $cevap['fotolar'] ?? [];

        if (empty($fotolar) && ! empty($cevap['foto_yolu'])) {
            $fotolar = [$cevap['foto_yolu']];
        }

        return array_values(array_filter($fotolar));
    }

    /**
     * Rapor sonundaki "Fotoğraf Kanıtları" sayfaları — sıra numarası, madde
     * kodu ve kanıt kimliği (depodaki benzersiz dosya adı) ile.
     *
     * @return array<int, array{sira: int, kod: string, yol: string, kimlik: string}>
     */
    public function kanitlar(): array
    {
        $kanitlar = [];

        foreach ($this->cevaplar ?? [] as $c) {
            foreach (static::maddeFotolari($c) as $yol) {
                $kanitlar[] = [
                    'sira' => count($kanitlar) + 1,
                    'kod' => $c['kod'] ?? '',
                    'yol' => $yol,
                    'kimlik' => pathinfo($yol, PATHINFO_FILENAME),
                ];
            }
        }

        return $kanitlar;
    }

    public function taslakMi(): bool
    {
        return $this->durum === 'taslak';
    }
}
