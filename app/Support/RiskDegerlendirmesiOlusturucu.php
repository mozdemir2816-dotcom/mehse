<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;

/**
 * "Madde" dizisinden (RiskSihirbazı'nın kullandığı aynı şekil — bkz.
 * RiskKutuphanesi::maddeyeCevir) doğrudan, sihirbaza yüklenmeden bir Risk
 * Değerlendirmesi kurar. RiskSihirbazi::buyukSablonuDogrudanUygula (çok
 * maddeli şablon) ve IsKalemiEvrakHazirlayici (iş kalemi → risk kütüphanesi)
 * bu ortak toplu-insert mantığını kullanır.
 */
class RiskDegerlendirmesiOlusturucu
{
    /**
     * @param  array<int, array<string, mixed>>  $maddeler
     */
    public static function maddelerdenOlustur(
        Firma $firma,
        string $yontem,
        array $maddeler,
        ?string $raporTarihi = null,
        ?string $gecerlilikTarihi = null,
        bool $etkilenenDiger = false,
        ?string $varsayilanTermin = null,
        string $durum = 'taslak',
        ?int $iguId = null,
        ?RiskDegerlendirmesi $mevcut = null,
    ): RiskDegerlendirmesi {
        @set_time_limit(app()->runningInConsole() ? 0 : 300);

        $raporTarihi ??= now()->toDateString();
        $gecerlilikTarihi ??= now()->addYears($firma->riskGecerlilikYili())->toDateString();

        // $mevcut verilirse yeni kayıt açılmaz: maddeler eski değerlendirmenin sonuna eklenir (eskisi korunur).
        if ($mevcut) {
            $rd = $mevcut;
            $yontem = $rd->yontem;
            $sira = (int) ($rd->maddeler()->max('sira') ?? 0);
        } else {
            $rd = new RiskDegerlendirmesi([
                'firma_id' => $firma->id,
                'igu_id' => $iguId,
                'yontem' => $yontem,
                'rapor_tarihi' => $raporTarihi,
                'gecerlilik_tarihi' => $gecerlilikTarihi,
                'durum' => $durum,
            ]);
            $rd->save();
            $sira = 0;
        }

        $fk = RiskSkorlama::ucEksenliMi($yontem);

        foreach (array_chunk($maddeler, 250) as $parca) {
            $satirlar = [];

            foreach ($parca as $m) {
                $sira++;
                $o = self::sayiVeyaNull($m['olasilik'] ?? null);
                $s = self::sayiVeyaNull($m['siddet'] ?? null);
                $f = $fk ? self::sayiVeyaNull($m['frekans'] ?? null) : null;
                $mevcut = RiskSkorlama::hesapla($yontem, $o, $s, $f);

                $so = self::sayiVeyaNull($m['son_olasilik'] ?? null) ?? ($o !== null ? 1.0 : null);
                $ss = self::sayiVeyaNull($m['son_siddet'] ?? null) ?? $s;
                $sf = $fk ? (self::sayiVeyaNull($m['son_frekans'] ?? null) ?? $f) : null;
                $son = RiskSkorlama::hesapla($yontem, $so, $ss, $sf);

                $satirlar[] = [
                    'risk_degerlendirmesi_id' => $rd->id,
                    'sira' => $sira,
                    'tehlike_id' => is_numeric($m['tehlike_id'] ?? null) ? (int) $m['tehlike_id'] : null,
                    'bolum' => $m['bolum'] ?? null,
                    'faaliyet' => $m['faaliyet'] ?? null,
                    'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                    'risk' => $m['risk'] ?? null,
                    'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                    // insert() cast uygulamaz: JSON kolonu elle kodlanır.
                    'hazop' => isset($m['hazop']) ? json_encode($m['hazop'], JSON_UNESCAPED_UNICODE) : null,
                    'etkilenen_calisan' => true,
                    'etkilenen_diger' => $etkilenenDiger,
                    'olasilik' => $o,
                    'frekans' => $f,
                    'siddet' => $s,
                    'puan' => $mevcut['puan'] ?: null,
                    'duzey' => $mevcut['puan'] ? $mevcut['duzey'] : null,
                    'oneri' => $m['oneri'] ?? null,
                    'mevzuat' => filled($m['mevzuat'] ?? null) ? $m['mevzuat'] : null,
                    'sorumlu' => $m['sorumlu'] ?? null,
                    'termin' => ($m['termin'] ?? '') ?: $varsayilanTermin,
                    'aciklama' => $m['aciklama'] ?? null,
                    'son_olasilik' => $so,
                    'son_frekans' => $sf,
                    'son_siddet' => $ss,
                    'son_puan' => $son['puan'] ?: null,
                    'son_duzey' => $son['puan'] ? $son['duzey'] : null,
                    'durum' => 'acik',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            RiskMaddesi::insert($satirlar);
        }

        if ($mevcut) {
            $rd->pdfOnbellegiTemizle();
            $rd->saveQuietly();
        }

        return $rd;
    }

    private static function sayiVeyaNull($deger): ?float
    {
        return ($deger === null || $deger === '') ? null : (float) $deger;
    }
}
