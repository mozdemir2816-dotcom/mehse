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

    public static function firmaKriterKarsilarMi(Firma $firma, string $anahtar): bool
    {
        return match ($anahtar) {
            'risk_degerlendirmesi' => $firma->riskDegerlendirmeleri()->exists(),
            default => false, // ilgili modül kurulunca burada gerçek kontrol
        };
    }

    /**
     * Profilim "Evrak / Firma Takip" — firma × kriter matrisi (isgpratik 141-142).
     *
     * @return array<int, array{firma: Firma, hucreler: array<string, bool>, oran: int}>
     */
    public static function firmaKriterMatrisi(int $userId): array
    {
        $kriterler = config('isg.kontrol_merkezi.kriterler', []);

        return Firma::query()
            ->where('user_id', $userId)
            ->orderBy('unvan')
            ->get()
            ->map(function (Firma $firma) use ($kriterler): array {
                $hucreler = [];
                $karsilanan = 0;
                $hazirSayi = 0;

                foreach ($kriterler as $k) {
                    $var = $k['hazir'] && static::firmaKriterKarsilarMi($firma, $k['anahtar']);
                    $hucreler[$k['anahtar']] = $var;

                    if ($k['hazir']) {
                        $hazirSayi++;
                        $karsilanan += $var ? 1 : 0;
                    }
                }

                return [
                    'firma' => $firma,
                    'hucreler' => $hucreler,
                    'oran' => $hazirSayi > 0 ? (int) round($karsilanan / $hazirSayi * 100) : 0,
                ];
            })
            ->all();
    }

    /**
     * Genel Bakış — Çalışan Dağılımı (firma başına aktif çalışan). isgpratik 6.jpg.
     *
     * @return array<string, int>
     */
    public static function calisanDagilimi(int $userId): array
    {
        return Firma::query()
            ->where('user_id', $userId)
            ->withCount(['calisanlar as aktif_calisan' => fn ($q) => $q->where('aktif', true)])
            ->orderByDesc('aktif_calisan')
            ->orderBy('unvan')
            ->get()
            ->mapWithKeys(fn (Firma $f) => [$f->unvan => (int) $f->aktif_calisan])
            ->all();
    }

    /**
     * Genel Bakış — günlük aktivite (son N gün). isgpratik 6.jpg: trend + heatmap.
     * Risk değerlendirmesi / madde / şablon / firma / çalışan eklemeleri sayılır.
     *
     * @return array<string, int>  'Y-m-d' => adet   (bugüne kadar, sıralı)
     */
    public static function aktiviteGunluk(int $userId, int $gun = 90): array
    {
        $baslangic = now()->subDays($gun - 1)->startOfDay();

        $tarihler = [];
        for ($i = 0; $i < $gun; $i++) {
            $tarihler[$baslangic->copy()->addDays($i)->toDateString()] = 0;
        }

        $ekle = function (\Illuminate\Support\Collection $tarihKolonu) use (&$tarihler): void {
            foreach ($tarihKolonu as $tarih) {
                $g = \Illuminate\Support\Carbon::parse($tarih)->toDateString();
                if (array_key_exists($g, $tarihler)) {
                    $tarihler[$g]++;
                }
            }
        };

        $ekle(\App\Models\RiskDegerlendirmesi::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(\App\Models\RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi.firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(\App\Models\RiskSablonu::query()->where('user_id', $userId)
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(Firma::query()->where('user_id', $userId)
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        $ekle(Calisan::query()->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('created_at', '>=', $baslangic)->pluck('created_at'));

        return $tarihler;
    }

    /** Profilim başlık kartları + Genel Bakış (isgpratik 5.jpg). */
    public static function profilOzeti(int $userId): array
    {
        $firmalar = Firma::query()->where('user_id', $userId)->get();

        $tehlikeDagilimi = collect(config('isg.tehlike_siniflari'))
            ->mapWithKeys(fn ($ad, $anahtar) => [$ad => $firmalar->where('tehlike_sinifi', $anahtar)->count()])
            ->all();

        $onemliEsik = (int) config('isg.onemli_risk_esigi', 140);
        $onemliRisk = \App\Models\RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi.firma', fn ($q) => $q->where('user_id', $userId))
            ->where('puan', '>', $onemliEsik)
            ->count();

        $calisansizFirma = Firma::query()
            ->where('user_id', $userId)
            ->whereDoesntHave('calisanlar', fn ($q) => $q->where('aktif', true))
            ->count();

        $ozet = static::ozet($userId);

        return [
            'firma' => $firmalar->count(),
            'calisan' => (int) Calisan::query()
                ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
                ->where('aktif', true)->count(),
            'risk_degerlendirmesi' => \App\Models\RiskDegerlendirmesi::query()
                ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))->count(),
            'risk_sablonu' => \App\Models\RiskSablonu::query()->where('user_id', $userId)->count(),
            'onemli_risk' => $onemliRisk,
            'calisansiz_firma' => $calisansizFirma,
            'evrak_eksigi' => $ozet['evrak_eksigi'],
            'tehlike_dagilimi' => $tehlikeDagilimi,
            'uyum_yuzde' => $ozet['uyum_yuzde'],
        ];
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
