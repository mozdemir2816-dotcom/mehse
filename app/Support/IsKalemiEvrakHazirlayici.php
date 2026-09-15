<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KkdMatrisi;
use App\Models\RiskDegerlendirmesi;
use App\Models\Talimat;
use App\Models\Tehlike;

/**
 * Firmanın seçili iş kalemlerine (bkz. isg.kkd_matris.is_kalemleri, alan
 * 'anahtar') göre KKD matrisi satırlarını, ilgili çalışma talimatlarını ve
 * (henüz risk değerlendirmesi yoksa) bir risk değerlendirmesi taslağını
 * önerir. Var olan kayıtların üstüne yazmaz — yalnız eksikleri ekler.
 * Eğitim için yeni bir kayıt oluşturmaz, bkz. Firma::isKalemiEgitimKonulari().
 */
class IsKalemiEvrakHazirlayici
{
    private const GRUP = 'İnşaat / Şantiye';

    /** @return array{kkd_eklenen:int, talimat_eklenen:int, risk_olusturuldu:bool, risk_atlandi_neden:?string, egitim_konu_sayisi:int} */
    public static function hazirla(Firma $firma): array
    {
        $anahtarlar = $firma->is_kalemleri ?? [];

        $maddeler = collect(config('isg.kkd_matris.is_kalemleri.'.self::GRUP, []))
            ->whereIn('anahtar', $anahtarlar)
            ->values();

        return [
            'kkd_eklenen' => self::kkdEkle($firma, $maddeler),
            'talimat_eklenen' => self::talimatEkle($firma, $maddeler),
            ...self::riskOlustur($firma, $maddeler),
            'egitim_konu_sayisi' => count($firma->isKalemiEgitimKonulari()),
        ];
    }

    private static function kkdEkle(Firma $firma, $maddeler): int
    {
        $matris = KkdMatrisi::firmaIcin($firma);
        $satirlar = $matris->satirlar ?? [];
        $mevcut = collect($satirlar)->pluck('is_kalemi')->all();

        $eklenen = 0;

        foreach ($maddeler as $m) {
            if (in_array($m['ad'], $mevcut, true)) {
                continue;
            }

            $satirlar[] = KkdMatrisiUretici::satirOlustur(self::GRUP, $m['ad']);
            $mevcut[] = $m['ad'];
            $eklenen++;
        }

        if ($eklenen > 0) {
            $matris->satirlar = $satirlar;
            $matris->save();
        }

        return $eklenen;
    }

    private static function talimatEkle(Firma $firma, $maddeler): int
    {
        $mevcutBasliklar = Talimat::where('firma_id', $firma->id)->pluck('baslik')->all();
        $kutuphane = collect(config('isg.talimat.sablonlar', []));

        $eklenen = 0;

        foreach ($maddeler as $m) {
            foreach ($m['talimat_basliklari'] ?? [] as $baslik) {
                if (in_array($baslik, $mevcutBasliklar, true)) {
                    continue;
                }

                $sablon = $kutuphane->firstWhere('baslik', $baslik);

                if (! $sablon) {
                    continue;
                }

                Talimat::create([
                    'firma_id' => $firma->id,
                    'baslik' => $sablon['baslik'],
                    'kategori' => $sablon['kategori'] ?? null,
                    'aciklama' => $sablon['aciklama'] ?? null,
                    'kkdler' => $sablon['kkdler'] ?? [],
                    'maddeler' => $sablon['maddeler'] ?? [],
                ]);

                $mevcutBasliklar[] = $baslik;
                $eklenen++;
            }
        }

        return $eklenen;
    }

    /** @return array{risk_olusturuldu:bool, risk_atlandi_neden:?string} */
    private static function riskOlustur(Firma $firma, $maddeler): array
    {
        if (RiskDegerlendirmesi::where('firma_id', $firma->id)->exists()) {
            return ['risk_olusturuldu' => false, 'risk_atlandi_neden' => 'Firmanın zaten bir risk değerlendirmesi var'];
        }

        $kategoriIsimleri = $maddeler->pluck('tehlike_kategorileri')->flatten()->filter()->unique()->values()->all();

        if (blank($kategoriIsimleri)) {
            return ['risk_olusturuldu' => false, 'risk_atlandi_neden' => null];
        }

        $tehlikeler = Tehlike::whereHas('kategori', fn ($q) => $q->whereIn('ad', $kategoriIsimleri))->get();

        if ($tehlikeler->isEmpty()) {
            return ['risk_olusturuldu' => false, 'risk_atlandi_neden' => 'Kütüphanede eşleşen tehlike bulunamadı'];
        }

        $riskMaddeleri = $tehlikeler->map(fn (Tehlike $t) => RiskKutuphanesi::maddeyeCevir($t))->all();

        RiskDegerlendirmesiOlusturucu::maddelerdenOlustur($firma, 'fine_kinney', $riskMaddeleri);

        return ['risk_olusturuldu' => true, 'risk_atlandi_neden' => null];
    }
}
