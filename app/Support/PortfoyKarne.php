<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use Illuminate\Support\Carbon;

/**
 * Kontrol Merkezi "Firma / Çalışan Asistanı" hesapları — isgpratik 135-136.jpg.
 * Portföy genelinde 12 yasal kriterin firma bazlı tamamlanma oranı ve çalışan
 * eksikleri. Modülü olmayan kriterler 0/N döner (`hazir=false`).
 */
class PortfoyKarne
{
    /** @return array<string, int|float> portföy özeti */
    public static function ozet(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->get();
        $toplam = $firmalar->count();

        $riskOlan = $firmalar->filter(fn (Firma $f) => $f->riskDegerlendirmeleri()->exists())->count();
        $tamUyumlu = $firmalar->filter(fn (Firma $f) => static::firmaTamUyumluMu($f))->count();

        $calisan = (int) Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('aktif', true)->count();

        $kriterler = static::kriterler($userId);
        $hazirKriterler = array_filter($kriterler, fn ($k) => $k['hazir']);
        $uyumYuzde = $hazirKriterler
            ? round(array_sum(array_column($hazirKriterler, 'yuzde')) / count($hazirKriterler))
            : 0;

        return [
            'firma' => $toplam,
            'calisan' => $calisan ?: (int) $firmalar->sum('calisan_sayisi'),
            'risk_olan' => $riskOlan,
            'evrak_eksigi' => $toplam - $riskOlan,
            'tam_uyumlu' => $tamUyumlu,
            'uyum_yuzde' => $uyumYuzde,
        ];
    }

    /**
     * 12 kriterin portföydeki tamamlanma oranı.
     *
     * @return array<int, array{anahtar:string, ad:string, ikon:string, tamam:int, toplam:int, yuzde:int, hazir:bool}>
     */
    public static function kriterler(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->get();
        $toplam = $firmalar->count();

        return array_map(function (array $kriter) use ($firmalar, $toplam): array {
            $kapsam = ($kriter['kosul'] ?? null) === 'elli_calisan'
                ? $firmalar->filter(fn (Firma $f) => (int) $f->calisan_sayisi >= 50)
                : $firmalar;

            $kapsamSayi = $kapsam->count();

            $tamam = ($kriter['hazir'] && $kapsamSayi > 0)
                ? $kapsam->filter(fn (Firma $f) => static::firmaKriterKarsilarMi($f, $kriter['anahtar']))->count()
                : 0;

            return [
                'anahtar' => $kriter['anahtar'],
                'ad' => $kriter['ad'],
                'ikon' => $kriter['ikon'],
                'tamam' => $tamam,
                'toplam' => $kapsamSayi ?: $toplam,
                'yuzde' => $kapsamSayi > 0 ? (int) round($tamam / $kapsamSayi * 100) : 0,
                'hazir' => (bool) $kriter['hazir'],
            ];
        }, config('isg.kontrol_merkezi.kriterler', []));
    }

    private static function firmaKriterKarsilarMi(Firma $firma, string $anahtar): bool
    {
        return match ($anahtar) {
            'risk_degerlendirmesi' => $firma->riskDegerlendirmeleri()->exists(),
            default => false, // ilgili modül kurulunca burada gerçek kontrol
        };
    }

    private static function firmaTamUyumluMu(Firma $firma): bool
    {
        foreach (config('isg.kontrol_merkezi.kriterler', []) as $kriter) {
            if (! $kriter['hazir']) {
                continue;
            }

            if (! static::firmaKriterKarsilarMi($firma, $kriter['anahtar'])) {
                return false;
            }
        }

        return true;
    }

    /** Tek firma için çalışan eksikleri (Çalışan Asistanı sekmesi). */
    public static function calisanKarne(Firma $firma): array
    {
        $calisanlar = $firma->calisanlar()->where('aktif', true)->get();

        $genc = $calisanlar->filter(function (Calisan $c): bool {
            return $c->dogum_tarihi
                && Carbon::parse($c->dogum_tarihi)->age < 18;
        });

        return [
            'toplam' => $calisanlar->count(),
            'genc' => $genc->values(),
            'agir_tehlikeli' => $calisanlar->where('agir_tehlikeli_iste', true)->count(),
            // Muayene / eğitim / MYK takibi ilgili modüller kurulunca eklenecek
            'moduller_bekliyor' => ['Periyodik muayene', 'İSG eğitimi', 'MYK belgesi'],
        ];
    }
}
