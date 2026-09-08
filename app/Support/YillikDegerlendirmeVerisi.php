<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsKazasiRaporu;
use App\Models\KurulToplantisi;
use App\Models\MuayeneFormu;
use App\Models\RiskDegerlendirmesi;
use App\Models\SahaDenetimi;
use App\Models\TatbikatTutanagi;

/**
 * Yıllık Değerlendirme Raporu satırlarını ("Yapılan Çalışmalar") sisteme
 * girilmiş gerçek kayıtlardan doldurur — kullanıcı elle tarih/tekrar sayısı
 * yazmasın. Satır adı ilgili modüle eşlenir; o firma + yıl için kayıt sayısı
 * "tekrar sayısı", en son kaydın tarihi "tarih" olur. Eşleşmeyen satırlara
 * dokunulmaz.
 */
class YillikDegerlendirmeVerisi
{
    /**
     * @return array{tarih: ?string, tekrar_sayisi: ?int}|null
     */
    public static function satirIcin(Firma $firma, int $yil, string $calisma): ?array
    {
        $n = static::normalize($calisma);

        $kaynak = match (true) {
            str_contains($n, 'riskdegerlendirmesi') || str_contains($n, 'riskanalizi') => [RiskDegerlendirmesi::class, 'rapor_tarihi'],
            str_contains($n, 'isegiris') && str_contains($n, 'muayene') => [MuayeneFormu::class, 'muayene_tarihi', ['muayene_turu', 'ise_giris']],
            str_contains($n, 'periyodik') && str_contains($n, 'muayene') => [MuayeneFormu::class, 'muayene_tarihi', ['muayene_turu', 'periyodik']],
            str_contains($n, 'muayene') => [MuayeneFormu::class, 'muayene_tarihi'],
            str_contains($n, 'egitim') => [EgitimKatilim::class, 'belge_tarihi'],
            str_contains($n, 'sahadenetim') || str_contains($n, 'sahagozetim') || str_contains($n, 'sahaturu') => [SahaDenetimi::class, 'denetim_tarihi'],
            str_contains($n, 'tatbikat') => [TatbikatTutanagi::class, 'tatbikat_tarihi'],
            str_contains($n, 'kurul') => [KurulToplantisi::class, 'tarih'],
            str_contains($n, 'iskazasi') || str_contains($n, 'kazainceleme') => [IsKazasiRaporu::class, 'kaza_tarihi'],
            default => null,
        };

        if ($kaynak === null) {
            return null;
        }

        [$model, $tarihAlani] = $kaynak;
        $kosul = $kaynak[2] ?? null;

        $sorgu = $model::query()
            ->where('firma_id', $firma->id)
            ->whereYear($tarihAlani, $yil);

        if ($kosul) {
            $sorgu->where($kosul[0], $kosul[1]);
        }

        $adet = (clone $sorgu)->count();

        if ($adet === 0) {
            return ['tarih' => null, 'tekrar_sayisi' => 0];
        }

        $sonTarih = (clone $sorgu)->max($tarihAlani);

        return [
            'tarih' => $sonTarih ? substr((string) $sonTarih, 0, 10) : null,
            'tekrar_sayisi' => $adet,
        ];
    }

    /**
     * Plandaki tüm değerlendirme satırlarını sistem verisiyle günceller.
     * Eşleşen ve en az 1 kayıt bulunan satırların tarih + tekrar_sayisi'ni
     * yazar; diğerlerine dokunmaz.
     *
     * @param  array<int, array<string, mixed>>  $degerlendirmeler
     * @return array{satirlar: array<int, array<string, mixed>>, doldurulan: int}
     */
    public static function planiDoldur(Firma $firma, int $yil, array $degerlendirmeler): array
    {
        $doldurulan = 0;

        foreach ($degerlendirmeler as $i => $d) {
            $sonuc = static::satirIcin($firma, $yil, (string) ($d['calisma'] ?? ''));

            if ($sonuc === null || ($sonuc['tekrar_sayisi'] ?? 0) === 0) {
                continue;
            }

            $degerlendirmeler[$i]['tarih'] = $sonuc['tarih'];
            $degerlendirmeler[$i]['tekrar_sayisi'] = $sonuc['tekrar_sayisi'];
            $doldurulan++;
        }

        return ['satirlar' => $degerlendirmeler, 'doldurulan' => $doldurulan];
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($metin)) ?? '';
    }
}
