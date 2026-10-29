<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\TehlikeKategorisi;

/**
 * Bir firmanın risk analizi için kütüphanede ne eksik? (28.10.2026)
 * Risk kütüphanesi NACE kodundan sektörel riskleri, ekipman tipinden ekipman risklerini alır;
 * bunlardan biri kütüphanede yoksa sessizce atlanmak yerine burada listelenir — İSG uzmanı
 * kütüphaneye ekleyebilsin diye.
 */
class KutuphaneEksikleri
{
    /**
     * @return list<array{tur: string, ad: string, mesaj: string}> tur: 'NACE' | 'Ekipman'
     */
    public static function firma(Firma $firma, ?string $yontem = null): array
    {
        return [...static::nace($firma, $yontem), ...static::ekipman($firma)];
    }

    /** @return list<array{tur: string, ad: string, mesaj: string}> */
    public static function nace(Firma $firma, ?string $yontem = null): array
    {
        $kod = trim((string) $firma->nace_kodu);

        if ($kod === '') {
            return [['tur' => 'NACE', 'ad' => '—', 'mesaj' => 'UYARI: Firmanın NACE kodu girilmemiş! Sektörel riskler eklenemedi.']];
        }

        $grup = NaceGrubu::bul($kod);

        if ($grup === null) {
            return [['tur' => 'NACE', 'ad' => $kod, 'mesaj' => "UYARI: {$kod} NACE kodu kütüphanede bulunamadı! Sektörel riskler eklenemedi."]];
        }

        $var = TehlikeKategorisi::query()
            ->whereJsonContains('nace_gruplari', $grup)
            ->when(filled($yontem), fn ($q) => $q->where('yontem', $yontem))
            ->whereHas('tehlikeler')
            ->exists();

        if (! $var) {
            $ad = config('risk_nace.gruplar.'.$grup.'.ad', $grup);

            return [['tur' => 'NACE', 'ad' => $kod, 'mesaj' => "UYARI: {$kod} ({$ad}) için sektörel risk kütüphanede tanımlı değil! Sektörel riskler eklenemedi."]];
        }

        return [];
    }

    /** @return list<array{tur: string, ad: string, mesaj: string}> */
    public static function ekipman(Firma $firma): array
    {
        $eksik = [];
        $gorulen = [];

        foreach (EkipmanKatalogu::ekipmanlar($firma) as $e) {
            $tip = trim((string) $e->tip);
            $anahtar = $tip !== '' ? 'tip:'.EkipmanKatalogu::norm($tip) : 'ad:'.EkipmanKatalogu::norm($e->ekipman_adi);

            if (isset($gorulen[$anahtar])) {
                continue;
            }
            $gorulen[$anahtar] = true;

            if ($tip === '') {
                $eksik[] = ['tur' => 'Ekipman', 'ad' => (string) $e->ekipman_adi, 'mesaj' => "UYARI: '{$e->ekipman_adi}' için ekipman tipi seçilmemiş! Bu ekipmana ait riskler pas geçildi."];

                continue;
            }

            if (blank(EkipmanKatalogu::tanim($tip)['risk_kategorileri'] ?? [])) {
                $eksik[] = ['tur' => 'Ekipman', 'ad' => $tip, 'mesaj' => "UYARI: '{$tip}' kütüphanede tanımlı değil! Bu ekipmana ait riskler pas geçildi."];
            }
        }

        return $eksik;
    }
}
