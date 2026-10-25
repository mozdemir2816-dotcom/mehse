<?php

namespace App\Support;

/**
 * Saha Denetimi "Fine-Kinney Saha Gözetimi" bölümü — kullanıcının "İSG Saha Gözetimi ve Risk
 * Değerlendirme Raporu (Fine-Kinney)" HTML aracındaki 40 sektör × 20 madde (25.10.2026).
 * Veri resources/data/saha-fine-kinney-kutuphanesi.json'dadır. Her madde: kategori, ifade,
 * varsayılan olasılık/frekans/şiddet ve önerilen DÖF (oneri).
 *
 * Şiddet, sistemdeki ölçeğe (config isg.risk_fine_kinney.siddet — 100 yok) çevrilerek
 * aktarıldı: kaynaktaki 40 (tek ölüm) → 30, 100 (felaket) → 40.
 *
 * Bu liste denetçinin (İSG uzmanı) kendi saha gözetimi içindir; çalışanın doldurduğu
 * ekipman kontrolü ayrıdır (HaftalikEkipmanKontrolUretici).
 */
class SahaFineKinneyKutuphanesi
{
    /** Denetim maddelerinde bu önekle kodlanır: FK1, FK2 … (nokta yok → Livewire dizi anahtarı güvenli). */
    public const KOD_ONEKI = 'FK';

    /**
     * NACE kodu öneki (yalnız rakamlar) → sektör anahtarı; en uzun eşleşen önek kazanır.
     * Firma seçilince sektör buradan otomatik seçilir, kullanıcı değiştirebilir.
     */
    private const NACE_ESLEME = [
        '01' => 'tarim', '02' => 'tarim', '03' => 'tarim',
        '05' => 'maden', '06' => 'petrokimya', '07' => 'maden', '08' => 'maden', '09' => 'maden',
        '10' => 'gida', '11' => 'gida', '12' => 'gida',
        '13' => 'tekstil', '14' => 'tekstil', '15' => 'deri', '16' => 'ahsap',
        '17' => 'kagit', '1721' => 'ambalaj', '18' => 'kagit',
        '19' => 'petrokimya', '20' => 'kimya', '21' => 'ilac', '22' => 'plastik', '23' => 'cimento',
        '24' => 'demir_celik', '25' => 'metal', '26' => 'elektrik_elektronik', '27' => 'elektrik_elektronik',
        '28' => 'metal', '29' => 'otomotiv', '30' => 'otomotiv', '301' => 'tersane', '303' => 'savunma_havacilik',
        '304' => 'savunma_havacilik', '31' => 'ahsap', '33' => 'metal', '3315' => 'tersane', '3316' => 'savunma_havacilik',
        '35' => 'enerji', '36' => 'su_kanalizasyon', '37' => 'su_kanalizasyon', '38' => 'atik', '39' => 'atik',
        '41' => 'insaat', '42' => 'insaat', '43' => 'insaat',
        '45' => 'oto_servis', '46' => 'lojistik', '47' => 'perakende', '473' => 'akaryakit',
        '49' => 'lojistik', '50' => 'liman', '51' => 'havalimani_apron', '52' => 'lojistik',
        '5222' => 'liman', '5223' => 'havalimani_apron', '53' => 'lojistik',
        '55' => 'turizm', '56' => 'restoran',
        '58' => 'ofis', '59' => 'ofis', '60' => 'ofis', '61' => 'telekom', '62' => 'ofis', '63' => 'ofis',
        '64' => 'ofis', '65' => 'ofis', '66' => 'ofis', '68' => 'ofis', '69' => 'ofis', '70' => 'ofis',
        '71' => 'ofis', '72' => 'ofis', '73' => 'ofis', '74' => 'ofis', '78' => 'ofis', '82' => 'ofis', '84' => 'ofis',
        '80' => 'guvenlik_temizlik', '81' => 'guvenlik_temizlik',
        '85' => 'egitim', '86' => 'saglik', '87' => 'saglik', '88' => 'saglik', '9601' => 'camasirhane',
    ];

    /** @var array<int, array{anahtar: string, ad: string, tehlike_sinifi: string, maddeler: array<int, array<string, mixed>>}>|null */
    private static ?array $veri = null;

    /** @return array<int, array{anahtar: string, ad: string, tehlike_sinifi: string, maddeler: array<int, array<string, mixed>>}> */
    public static function tumu(): array
    {
        if (self::$veri === null) {
            $yol = resource_path('data/saha-fine-kinney-kutuphanesi.json');
            self::$veri = is_file($yol) ? (array) json_decode((string) file_get_contents($yol), true) : [];
        }

        return self::$veri;
    }

    /** @return array<string, string> anahtar => ad (açılır liste için) */
    public static function sektorler(): array
    {
        return collect(self::tumu())->mapWithKeys(fn (array $s) => [$s['anahtar'] => $s['ad']])->all();
    }

    public static function sektorAdi(?string $anahtar): ?string
    {
        return collect(self::tumu())->firstWhere('anahtar', $anahtar)['ad'] ?? null;
    }

    /** Firmanın NACE kodundan sektör anahtarı ("41.00.01" → insaat); eşleşme yoksa null. */
    public static function naceSektoru(?string $nace): ?string
    {
        $rakamlar = preg_replace('/\D/', '', (string) $nace);

        for ($uzunluk = min(4, strlen($rakamlar)); $uzunluk >= 2; $uzunluk--) {
            if (isset(self::NACE_ESLEME[substr($rakamlar, 0, $uzunluk)])) {
                return self::NACE_ESLEME[substr($rakamlar, 0, $uzunluk)];
            }
        }

        return null;
    }

    /**
     * Saha Denetimi madde yapısında liste; 'fk' varsayılan Fine-Kinney puanlarını taşır.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function maddeler(?string $anahtar): array
    {
        $sektor = collect(self::tumu())->firstWhere('anahtar', $anahtar);

        if (! $sektor) {
            return [];
        }

        return collect($sektor['maddeler'])->map(fn (array $m, int $i) => [
            'kod' => self::KOD_ONEKI.($i + 1),
            'ifade' => $m['ifade'],
            'kritik' => false,
            'uygulanamaz_izni' => true,
            'fk_kategori' => $m['kategori'],
            'oneri' => $m['oneri'],
            'fk' => ['olasilik' => $m['olasilik'], 'frekans' => $m['frekans'], 'siddet' => $m['siddet']],
        ])->all();
    }

    /** @return array{ad: string, maddeler: array<int, array<string, mixed>>}|null */
    public static function kategori(?string $anahtar): ?array
    {
        $ad = self::sektorAdi($anahtar);

        return $ad ? ['ad' => 'Fine-Kinney Saha Gözetimi — '.$ad, 'maddeler' => self::maddeler($anahtar)] : null;
    }

    /**
     * Uygunsuz maddenin Fine-Kinney puanı ve bandı; puan, denetimin 3'lü risk seviyesine
     * de çevrilir (≥200 yüksek, 70-199 orta, altı düşük).
     *
     * @return array{puan: int|float, duzey: string, renk: string, eylem: string, seviye: ?string}
     */
    public static function puanla(mixed $olasilik, mixed $frekans, mixed $siddet): array
    {
        $sonuc = RiskSkorlama::hesapla('fine_kinney', RiskSkorlama::sayi($olasilik), RiskSkorlama::sayi($siddet), RiskSkorlama::sayi($frekans));
        $puan = $sonuc['duzey'] === '—' ? null : $sonuc['puan'];

        return [...$sonuc, 'seviye' => match (true) {
            $puan === null => null,
            $puan >= 200 => 'yuksek',
            $puan >= 70 => 'orta',
            default => 'dusuk',
        }];
    }
}
