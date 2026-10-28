<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use Illuminate\Support\Facades\DB;

/**
 * Risk maddelerinde mükerrer (aynı madde iki kez yazılmış) tespiti ve temizliği (28.10.2026).
 *
 * Mükerrer ölçütü: aynı değerlendirmede bölüm + faaliyet + tehlike + risk metni (boşluk / büyük-küçük
 * harf farkı yok sayılarak) aynı. Yalnız tehlike metninin aynı olması mükerrer SAYILMAZ — farklı
 * faaliyetlerde aynı tehlike meşrudur. Her grupta en dolu olan satır kalır (puan, öneri, önlem,
 * sorumlu, termin), eşitlikte ilk sıradaki.
 */
class RiskMaddeMukerrer
{
    /** Madde kimliği — bölüm|faaliyet|tehlike|risk, normalize. */
    public static function kimlik(array $m): string
    {
        $parcalar = array_map(function ($alan) use ($m) {
            $metin = preg_replace('/\s+/u', ' ', trim((string) ($m[$alan] ?? '')));

            return mb_strtolower(strtr($metin, ['I' => 'ı', 'İ' => 'i']));
        }, ['bolum', 'faaliyet', 'tehlike', 'risk']);

        return implode('|', $parcalar);
    }

    /** Satırın doluluk puanı — hangisinin kalacağını belirler. */
    private static function doluluk(array $m): int
    {
        $puan = 0;

        foreach (['olasilik', 'siddet', 'frekans', 'oneri', 'mevcut_onlem', 'sorumlu', 'termin', 'son_olasilik', 'son_siddet'] as $alan) {
            if (($m[$alan] ?? null) !== null && trim((string) $m[$alan]) !== '') {
                $puan++;
            }
        }

        return $puan;
    }

    /**
     * Silinmesi gereken satırların anahtarları (dizinin kendi anahtarları). Tehlike metni boş satırlar atlanır.
     *
     * @param  array<int|string, array<string, mixed>>  $maddeler
     * @return list<int|string>
     */
    public static function tekrarlar(array $maddeler): array
    {
        $kalan = [];
        $silinecek = [];

        foreach ($maddeler as $anahtar => $m) {
            if (trim((string) ($m['tehlike'] ?? '')) === '') {
                continue;
            }

            $kimlik = static::kimlik($m);

            if (! isset($kalan[$kimlik])) {
                $kalan[$kimlik] = $anahtar;

                continue;
            }

            // Daha dolu olan kalır; eşitte önceki kalır.
            if (static::doluluk($m) > static::doluluk($maddeler[$kalan[$kimlik]])) {
                $silinecek[] = $kalan[$kimlik];
                $kalan[$kimlik] = $anahtar;
            } else {
                $silinecek[] = $anahtar;
            }
        }

        return $silinecek;
    }

    /**
     * Mükerrerleri çıkarılmış liste (anahtarlar korunur).
     *
     * @param  array<int|string, array<string, mixed>>  $maddeler
     * @return array<int|string, array<string, mixed>>
     */
    public static function temizle(array $maddeler): array
    {
        return array_diff_key($maddeler, array_flip(static::tekrarlar($maddeler)));
    }

    /**
     * Kayıtlı bir değerlendirmedeki mükerrerleri siler, sırayı yeniden numaralar. Hastane şablonlu
     * değerlendirme kaynak formu olduğu gibi korunur (dokunulmaz). Silinen satır sayısını döner;
     * $denemeKosusu true ise silmeden yalnız sayar.
     */
    public static function degerlendirmeTemizle(RiskDegerlendirmesi $rd, bool $denemeKosusu = false): int
    {
        if ($rd->cikti_sablonu === 'hastane') {
            return 0;
        }

        $satirlar = $rd->maddeler()->orderBy('sira')->orderBy('id')->get()->keyBy('id');
        $silinecek = static::tekrarlar($satirlar->map->toArray()->all());

        if ($denemeKosusu || ! $silinecek) {
            return count($silinecek);
        }

        DB::transaction(function () use ($rd, $silinecek) {
            $rd->maddeler()->whereIn('id', $silinecek)->delete();

            foreach ($rd->maddeler()->orderBy('sira')->orderBy('id')->pluck('id') as $i => $id) {
                $rd->maddeler()->where('id', $id)->update(['sira' => $i + 1]);
            }
        });

        return count($silinecek);
    }
}
