<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * "Excel Yükle" sayfasının iki aşaması: dosyayı inceleyip eşleme önizlemesi çıkarmak (`analiz`) ve
 * kullanıcının onayladığı / düzelttiği eşlemeyle risk değerlendirmesi olarak kaydetmek (`yukle`).
 * Hastane formu önizlemesiz, olduğu gibi alınır; diğer düzenler esnek okuyucuyla eşlenir.
 */
class RiskExcelYukleyici
{
    public const YONTEMLER = [
        'otomatik' => 'Otomatik (dosyadan anla)',
        'fine_kinney' => 'Fine-Kinney',
        'matris_5x5' => '5x5 L Matris',
    ];

    /**
     * @return array<string, mixed> 'tur' => 'hastane' | 'standart' (+ ilgili alanlar)
     */
    public static function analiz(string $yol, string $dosyaAdi): array
    {
        $girdi = ['dosya' => $dosyaAdi, 'yol' => $yol, 'birim' => static::birimAdi($dosyaAdi)];

        $hastane = HastaneRiskIceAktarici::oku($yol);

        if ($hastane) {
            return $girdi + [
                'tur' => 'hastane',
                'sayfa_sayisi' => count($hastane),
                'madde' => array_sum(array_map(fn ($s) => count($s['maddeler']), $hastane)),
            ];
        }

        $sayfalar = RiskDegerlendirmesiExcelOkuyucu::incele($yol);

        return $girdi + [
            'tur' => 'standart',
            'yontem' => 'otomatik',
            'mukerrer_sil' => true,
            'sayfalar' => array_map(fn ($s) => $s + ['kullan' => $s['onerilen']], $sayfalar),
        ];
    }

    /**
     * @param  array<string, mixed>  $girdi  analiz() çıktısı + kullanıcının ekrandaki düzeltmeleri
     * @return array{eklenen: int, madde: int, notlar: list<string>}
     */
    public static function yukle(Firma $firma, array $girdi): array
    {
        return ($girdi['tur'] ?? '') === 'hastane'
            ? static::hastaneYukle($firma, $girdi)
            : static::standartYukle($firma, $girdi);
    }

    /** Eşleme ayarı: sayfa adı => [kullan, veri_baslangic, sutunlar(sütun => alan)]. */
    public static function duzeltme(array $girdi): array
    {
        $duzeltme = [];

        foreach ($girdi['sayfalar'] ?? [] as $s) {
            $duzeltme[$s['ad']] = [
                'kullan' => (bool) ($s['kullan'] ?? false),
                'veri_baslangic' => (int) ($s['veri_baslangic'] ?? 0),
                'sutunlar' => array_filter((array) ($s['sutunlar'] ?? []), fn ($alan) => $alan !== '' && $alan !== null),
            ];
        }

        return $duzeltme;
    }

    /** @return array{eklenen: int, madde: int, notlar: list<string>} */
    private static function standartYukle(Firma $firma, array $girdi): array
    {
        $ad = $girdi['dosya'];
        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($girdi['yol'], static::duzeltme($girdi));

        if (! $sonuc['adaylar']) {
            return ['eklenen' => 0, 'madde' => 0, 'notlar' => ["{$ad}: ".($sonuc['hatalar'][0] ?? 'risk maddesi bulunamadı')]];
        }

        $notlar = array_map(fn ($u) => "{$ad}: {$u}", $sonuc['hatalar']);
        $birim = $girdi['birim'];
        $secilen = $girdi['yontem'] ?? 'otomatik';
        $frekansli = fn ($grup) => collect($grup)->contains(fn ($a) => ($a['frekans'] ?? null) !== null);

        // Otomatik yöntemde sayfalar farklı yöntemdeyse (5x5 + eski Fine-Kinney sayfası) karışmasınlar:
        // sayfa başına ayrı değerlendirme. Yöntem elle seçildiyse tüm sayfalar tek değerlendirme olur.
        $gruplar = collect($sonuc['adaylar'])->groupBy(fn ($a) => $a['sayfa'] ?? '');
        $karisik = $secilen === 'otomatik' && $gruplar->map(fn ($g) => $frekansli($g))->unique()->count() > 1;

        $bolunmus = $karisik
            ? $gruplar->mapWithKeys(fn ($g, $sayfa) => [$birim.' — '.$sayfa => $g->all()])
            : collect([$birim => $sonuc['adaylar']]);

        $eklenen = $madde = 0;

        foreach ($bolunmus as $proje => $adaylar) {
            if ($girdi['mukerrer_sil'] ?? false) {
                $temiz = array_values(RiskMaddeMukerrer::temizle($adaylar));

                if (count($temiz) < count($adaylar)) {
                    $notlar[] = "{$proje}: ".(count($adaylar) - count($temiz)).' mükerrer madde yazılmadı';
                }

                $adaylar = $temiz;
            }

            $yontem = $secilen === 'otomatik' ? ($frekansli($adaylar) ? 'fine_kinney' : 'matris_5x5') : $secilen;
            $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($firma, $yontem, $adaylar);
            $rd->update(['proje_adi' => $proje]);
            $eklenen++;
            $madde += count($adaylar);
        }

        return ['eklenen' => $eklenen, 'madde' => $madde, 'notlar' => $notlar];
    }

    /** @return array{eklenen: int, madde: int, notlar: list<string>} */
    private static function hastaneYukle(Firma $firma, array $girdi): array
    {
        $sayfalar = HastaneRiskIceAktarici::oku($girdi['yol']);
        $birim = $girdi['birim'];
        $eklenen = $madde = 0;
        $notlar = [];

        File::ensureDirectoryExists(storage_path('app/hastane-sablonlari'));

        foreach ($sayfalar as $sayfa) {
            $adSayfa = count($sayfalar) > 1 ? $birim.' — '.$sayfa['sayfa'] : $birim;

            if (RiskDegerlendirmesi::where('firma_id', $firma->id)->where('cikti_sablonu', 'hastane')->where('proje_adi', $adSayfa)->exists()) {
                $notlar[] = "{$adSayfa}: zaten var, atlandı";

                continue;
            }

            $sablon = Str::uuid().'.xlsx';
            File::copy($girdi['yol'], storage_path('app/hastane-sablonlari/'.$sablon));
            HastaneRiskIceAktarici::kaydet($firma, $sayfa, $adSayfa, $sablon);
            $eklenen++;
            $madde += count($sayfa['maddeler']);
        }

        return ['eklenen' => $eklenen, 'madde' => $madde, 'notlar' => $notlar];
    }

    /** "12- KİMYASAL MADDELER.xlsx" → "KİMYASAL MADDELER" */
    private static function birimAdi(string $dosyaAdi): string
    {
        return trim(preg_replace('/^\d+\s*-\s*/u', '', pathinfo($dosyaAdi, PATHINFO_FILENAME)));
    }
}
