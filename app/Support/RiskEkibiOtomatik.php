<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsgProfesyoneli;

/**
 * Risk Değerlendirme Ekibi toplu atama yazısı — İSG Risk Değerlendirmesi
 * Yönetmeliği md.6. Ekip üyeleri sistemden otomatik toplanır, yalnız destek
 * elemanı elle girilir:
 * - İş Güvenliği Uzmanı / İşyeri Hekimi: firmaya atanmış İSG profesyoneli
 * - Çalışan Temsilcisi: son "Çalışan Temsilcisi" atama yazısı, yoksa
 *   Çalışan Temsilcisi Seçimi'nde seçilen aday
 * - Bilgi Sahibi Çalışan(lar): son "Bilgi Sahibi Çalışan" atama yazısı
 * TC/görev boşsa firmanın çalışan kaydından (ad soyad eşleşmesiyle) tamamlanır.
 */
class RiskEkibiOtomatik
{
    /**
     * @return array<string, array<int, array{ad_soyad: string, tc: ?string, gorev: ?string, bas_uye: bool, ekip_gorevi: string}>>
     *         ekip görevi => üyeler (boş liste = sistemde kayıt yok)
     */
    public static function uyeler(Firma $firma): array
    {
        $calisanlar = $firma->calisanlar()->get(['ad_soyad', 'tc', 'gorev'])
            ->keyBy(fn ($c) => self::anahtar($c->ad_soyad));

        $tamamla = function (array $u, string $ekipGorevi) use ($calisanlar): array {
            $c = $calisanlar[self::anahtar($u['ad_soyad'] ?? '')] ?? null;

            return [
                'ad_soyad' => (string) ($u['ad_soyad'] ?? ''),
                'tc' => ($u['tc'] ?? null) ?: $c?->tc,
                'gorev' => ($u['gorev'] ?? null) ?: $c?->gorev,
                'bas_uye' => false,
                'ekip_gorevi' => $ekipGorevi,
            ];
        };

        $sonAtama = fn (string $rol): array => (array) ($firma->atamaYazilari()
            ->where('rol_anahtari', $rol)->latest('tarih')->latest('id')->first()?->uyeler ?? []);

        $profesyonel = fn (?IsgProfesyoneli $p, string $ekipGorevi): array => $p ? [[
            'ad_soyad' => $p->ad_soyad,
            'tc' => null,
            'gorev' => $p->unvan ?: $p->tipEtiketi(),
            'bas_uye' => false,
            'ekip_gorevi' => $ekipGorevi,
            'kase_gorseli' => $p->kase_gorseli,
            'imza_gorseli' => $p->imza_gorseli,
        ]] : [];

        $temsilci = $sonAtama('calisan_temsilcisi');
        if (! $temsilci && ($aday = $firma->calisanTemsilcisiSecimi?->secilenAday())) {
            $temsilci = [['ad_soyad' => $aday['ad_soyad'], 'gorev' => $aday['unvan'] ?? null]];
        }

        return [
            'İş Güvenliği Uzmanı' => $profesyonel($firma->igu, 'İş Güvenliği Uzmanı'),
            'İşyeri Hekimi' => $profesyonel($firma->isyeriHekimi, 'İşyeri Hekimi'),
            'Çalışan Temsilcisi' => collect($temsilci)->filter(fn ($u) => filled($u['ad_soyad'] ?? null))
                ->map(fn ($u) => $tamamla($u, 'Çalışan Temsilcisi'))->values()->all(),
            'Bilgi Sahibi Çalışan' => collect($sonAtama('bilgi_sahibi'))->filter(fn ($u) => filled($u['ad_soyad'] ?? null))
                ->map(fn ($u) => $tamamla($u, 'Bilgi Sahibi Çalışan'))->values()->all(),
        ];
    }

    /**
     * Risk değerlendirmesi PDF'inin imza sahipleri, firmanın atamalarından:
     * Çalışan Temsilcisi ve Bilgi Sahibi Çalışan(lar) yukarıdaki kaynaklardan,
     * Destek Elemanı son "Risk Değerlendirme Ekibi" yazısından. Değerlendirmenin
     * kendi ekip listesinde o rol elle girilmişse o kullanılır (RiskDegerlendirmesiUretici).
     *
     * @return array<int, array{ad: string, unvan: string}>
     */
    public static function imzaSahipleri(Firma $firma): array
    {
        $uyeler = self::uyeler($firma);
        $destek = collect((array) ($firma->atamaYazilari()->where('rol_anahtari', 'risk_degerlendirme_ekibi')
            ->latest('tarih')->latest('id')->first()?->uyeler ?? []))
            ->filter(fn ($u) => ($u['ekip_gorevi'] ?? null) === 'Destek Elemanı' && filled($u['ad_soyad'] ?? null))
            ->values()->all();

        return collect([
            ...array_slice($uyeler['Çalışan Temsilcisi'], 0, 1),
            ...$destek,
            ...$uyeler['Bilgi Sahibi Çalışan'],
        ])->map(fn (array $u) => ['ad' => $u['ad_soyad'], 'unvan' => $u['ekip_gorevi']])->all();
    }

    private static function anahtar(?string $ad): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $ad))));
    }
}
