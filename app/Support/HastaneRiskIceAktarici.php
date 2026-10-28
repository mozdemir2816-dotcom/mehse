<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Bursa Şehir Hastanesi risk formunu (A–S) olduğu gibi içe aktarır: puanlar standart
 * Fine-Kinney ölçeğinde (Ş 40 = ölüm) dönüştürülmeden, boş "Son Risk" boş, RG harfi
 * dosyadaki gibi saklanır. Her dosya / sayfa hastane firmasında AYRI bir değerlendirme olur.
 */
class HastaneRiskIceAktarici
{
    /**
     * Dosyadaki hastane formu sayfalarını okur.
     *
     * @param  list<string>|null  $yalnizSayfalar  null = düzeni tanınan tüm sayfalar
     * @return list<array{sayfa: string, meta: array<string, mixed>, maddeler: list<array<string, mixed>>}>
     */
    public static function oku(string $yol, ?array $yalnizSayfalar = null): array
    {
        $kitap = HastaneRiskSablonu::yukle($yol);
        $sonuc = [];

        foreach ($kitap->getWorksheetIterator() as $ws) {
            if ($yalnizSayfalar !== null && ! in_array($ws->getTitle(), $yalnizSayfalar, true)) {
                continue;
            }

            $duzen = HastaneRiskSablonu::duzen($ws);
            if (! $duzen) {
                continue;
            }

            $maddeler = static::maddeleriOku($ws, $duzen);
            if ($maddeler) {
                $sonuc[] = ['sayfa' => $ws->getTitle(), 'meta' => static::kunye($ws, $duzen), 'maddeler' => $maddeler];
            }
        }

        $kitap->disconnectWorksheets();

        return $sonuc;
    }

    /** @param  array<string, int|null>  $duzen */
    private static function maddeleriOku(Worksheet $ws, array $duzen): array
    {
        $maddeler = [];
        $bolum = null;

        for ($r = $duzen['ilk']; $r <= $duzen['son_veri']; $r++) {
            if (HastaneRiskSablonu::bantMi($ws, $r)) {
                $bolum = trim(preg_replace('/\s+/u', ' ', HastaneRiskSablonu::metin($ws, 'A', $r)));

                continue;
            }
            if (! HastaneRiskSablonu::veriMi($ws, $r)) {
                continue;
            }

            $h = fn (string $s) => HastaneRiskSablonu::ham($ws, $s, $r);
            $sayi = fn (string $s) => RiskSkorlama::sayi($ws->getCell($s.$r)->getCalculatedValue());
            $harf = fn (string $s) => ($v = strtoupper(preg_replace('/\s+/u', '', (string) HastaneRiskSablonu::ham($ws, $s, $r)))) !== '' ? mb_substr($v, 0, 1) : null;

            $maddeler[] = [
                'bolum' => $bolum,
                'tehlike' => $h('B'),
                'risk' => $h('C'),
                'etki_alani' => $h('D'),
                'mevcut_onlem' => $h('E'),
                'olasilik' => $sayi('F'), 'frekans' => $sayi('G'), 'siddet' => $sayi('H'),
                'duzey' => $harf('J'),
                'oneri' => $h('K'),
                'son_olasilik' => $sayi('L'), 'son_frekans' => $sayi('M'), 'son_siddet' => $sayi('N'),
                'son_duzey' => $harf('P'),
                'sorumlu' => $h('Q'),
                'termin' => $h('R'),
                'aciklama' => $h('S'),
            ];
        }

        return $maddeler;
    }

    /**
     * Künye: yayın / geçerlilik tarihi (metinden), kaynak başlığı.
     *
     * @param  array<string, int|null>  $duzen
     * @return array{yayin: ?Carbon, gecerlilik: ?Carbon, baslik: string}
     */
    private static function kunye(Worksheet $ws, array $duzen): array
    {
        $yayin = $gecerlilik = null;
        $baslik = '';

        for ($r = 1; $r < $duzen['ust']; $r++) {
            foreach (['A', 'C', 'D', 'K', 'Q'] as $s) {
                $t = HastaneRiskSablonu::metin($ws, $s, $r);
                if ($t === '') {
                    continue;
                }
                if ($baslik === '' && preg_match('/HASTANESİ/u', $t)) {
                    $baslik = $t;
                }
                if (preg_match('/GEÇERLİLİK TARİHİ\s*:?\s*(\d{1,2})\s*[.\-\/]\s*(\d{1,2})\s*[.\-\/]\s*(\d{4})/u', $t, $e)) {
                    $gecerlilik ??= static::tarih($e);
                } elseif (preg_match('/YAYIN TARİHİ\s*:?\s*(\d{1,2})\s*[.\-\/]\s*(\d{1,2})\s*[.\-\/]\s*(\d{4})/u', $t, $e)) {
                    $yayin ??= static::tarih($e);
                }
            }
        }

        return ['yayin' => $yayin, 'gecerlilik' => $gecerlilik, 'baslik' => $baslik];
    }

    private static function tarih(array $e): ?Carbon
    {
        return checkdate((int) $e[2], (int) $e[1], (int) $e[3]) ? Carbon::create((int) $e[3], (int) $e[2], (int) $e[1]) : null;
    }

    /**
     * Okunan sayfayı firmaya ayrı bir risk değerlendirmesi olarak yazar.
     *
     * @param  array{sayfa: string, meta: array<string, mixed>, maddeler: list<array<string, mixed>>}  $sayfa
     */
    public static function kaydet(Firma $firma, array $sayfa, string $birimAdi, string $sablonDosyasi): RiskDegerlendirmesi
    {
        return DB::transaction(function () use ($firma, $sayfa, $birimAdi, $sablonDosyasi) {
            $rd = RiskDegerlendirmesi::create(array_filter([
                'firma_id' => $firma->id,
                'yontem' => 'fine_kinney',
                'cikti_sablonu' => 'hastane',
                'sablon_verisi' => ['dosya' => $sablonDosyasi, 'sayfa' => $sayfa['sayfa']],
                'proje_adi' => $birimAdi,
                'rapor_tarihi' => $sayfa['meta']['yayin'],
                'gecerlilik_tarihi' => $sayfa['meta']['gecerlilik'],
            ], fn ($v) => $v !== null));

            foreach ($sayfa['maddeler'] as $i => $madde) {
                RiskMaddesi::create($madde + ['risk_degerlendirmesi_id' => $rd->id, 'sira' => $i + 1, 'durum' => 'acik']);
            }

            return $rd;
        });
    }
}
