<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TehlikeKategorisi extends Model
{
    use HasFactory;

    protected $table = 'tehlike_kategorileri';

    protected $guarded = ['id'];

    protected $casts = [
        'ekipman_tipleri' => 'array',
        'nace_gruplari' => 'array',
    ];

    public function tehlikeler(): HasMany
    {
        return $this->hasMany(Tehlike::class);
    }

    /** Ad karşılaştırmasında anlam taşımayan kelimeler ("Ekipman – …", "… 50 madde"). */
    private const ETKISIZ_KELIMELER = ['ekipman', 'is', 'isi', 'isleri', 'madde', 'risk', 'riskleri', 'degerlendirmesi', 'detayli', 've', 'ile', 'icin'];

    /** @return array<int, string> Karşılaştırma için kelimeler (Türkçe harfler sadeleşir, kısa/etkisiz kelimeler atılır). */
    public static function adKelimeleri(string $ad): array
    {
        $ad = mb_strtolower(strtr($ad, ['İ' => 'i', 'I' => 'ı']));
        $ad = strtr($ad, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u']);
        $kelimeler = preg_split('/[^a-z]+/', $ad, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($kelimeler, fn ($k) => mb_strlen($k) >= 3 && ! in_array($k, self::ETKISIZ_KELIMELER, true))));
    }

    /**
     * Aynı ekipman / iş için zaten bir klasör var mı? (13.11.2026: "manito" yazılınca
     * mevcut "Manitou / teleskopik yükleyici" klasörü bulunmadığı için yeni klasör açılıyordu.)
     * Puan: aranan adın her kelimesinin, klasör adındaki en benzer kelimeye benzerliğinin ortalaması (0–100).
     *
     * @return \Illuminate\Support\Collection<int, array{kategori: self, puan: int, madde: int}>
     */
    public static function benzerleri(string $ad, int $limit = 3, int $esik = 80): \Illuminate\Support\Collection
    {
        $aranan = static::adKelimeleri($ad);

        if (! $aranan) {
            return collect();
        }

        return static::query()->withCount('tehlikeler')->get()
            ->map(function (self $k) use ($aranan) {
                $kelimeler = static::adKelimeleri($k->ad);
                if (! $kelimeler) {
                    return null;
                }
                $toplam = 0;
                foreach ($aranan as $a) {
                    $en = 0;
                    foreach ($kelimeler as $b) {
                        similar_text($a, $b, $yuzde);
                        $en = max($en, $yuzde);
                    }
                    $toplam += $en;
                }

                return ['kategori' => $k, 'puan' => (int) round($toplam / count($aranan)), 'madde' => (int) $k->tehlikeler_count];
            })
            ->filter(fn ($s) => $s && $s['puan'] >= $esik)
            ->sortBy([['puan', 'desc'], ['madde', 'desc']])
            ->take($limit)->values();
    }

    /**
     * Firmanın yöntemi ve NACE grubuna uyan kategoriler. "ortak" etiketli kategoriler
     * her NACE grubunda görünür (vinç, kompresör gibi ortak ekipmanlar).
     */
    public function scopeUygun(Builder $sorgu, string $yontem, ?string $naceGrubu): Builder
    {
        return $sorgu->where('yontem', $yontem)
            ->where(function (Builder $q) use ($naceGrubu) {
                $q->whereJsonContains('nace_gruplari', 'ortak');

                if ($naceGrubu) {
                    $q->orWhereJsonContains('nace_gruplari', $naceGrubu);
                }
            });
    }
}
