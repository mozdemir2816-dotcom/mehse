<?php

namespace App\Http\Controllers;

use App\Support\NaceGrubu;
use Illuminate\Http\Request;

/**
 * Giriş gerektirmeyen ücretsiz SEO İSG araçları (D26):
 * 1) Tehlike Sınıfı Sorgulama (NACE)
 * 2) Kaza Sıklık / Ağırlık / İnsidans Oranı Hesaplayıcı
 * 3) 6331 İGU / İşyeri Hekimi / DSP Aylık Süre Hesaplayıcı
 */
class UcretsizAraclarController extends Controller
{
    public function tehlikeSinifi(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sonuclar = [];

        if ($q !== '') {
            $naceListesi = (array) config('nace', []);
            $qKucuk = mb_strtolower($q, 'UTF-8');

            foreach ($naceListesi as $kod => $kayit) {
                $ad = is_array($kayit) ? (string) ($kayit['ad'] ?? $kayit['tanim'] ?? '') : (string) $kayit;
                $sinif = is_array($kayit) ? (string) ($kayit['sinif'] ?? $kayit['tehlike_sinifi'] ?? 'tehlikeli') : 'tehlikeli';
                if (str_contains(mb_strtolower((string) $kod, 'UTF-8'), $qKucuk) || str_contains(mb_strtolower($ad, 'UTF-8'), $qKucuk)) {
                    $sonuclar[] = ['kod' => (string) $kod, 'ad' => $ad, 'sinif' => $sinif];
                    if (count($sonuclar) >= 25) {
                        break;
                    }
                }
            }

            if ($sonuclar === [] && class_exists(NaceGrubu::class)) {
                $grup = NaceGrubu::bul($q);
                if ($grup) {
                    $sonuclar[] = ['kod' => $q, 'ad' => $grup, 'sinif' => 'tehlikeli'];
                }
            }
        }

        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'tehlike',
            'baslik' => 'NACE Kodu ile Tehlike Sınıfı Sorgulama (Ücretsiz)',
            'aciklama' => '6331 sayılı İSG Kanunu kapsamında NACE kodu veya faaliyet adıyla işyerinizin tehlike sınıfını anında sorgulayın.',
            'q' => $q,
            'sonuclar' => $sonuclar,
        ]);
    }

    public function kazaSiklikOrani()
    {
        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'kso',
            'baslik' => 'İş Kazası Sıklık ve Ağırlık Hızı Hesaplama (Ücretsiz)',
            'aciklama' => 'ILO standardına göre Kaza Sıklık Oranı (KSO), Kaza Ağırlık Oranı (KAO) ve Kaza İnsidans Oranını anında hesaplayın.',
            'q' => '',
            'sonuclar' => [],
        ]);
    }

    public function isgSureHesaplama()
    {
        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'sure',
            'baslik' => '6331 İGU, İşyeri Hekimi ve DSP Aylık Süre Hesaplama (Ücretsiz)',
            'aciklama' => 'Çalışan sayısı ve tehlike sınıfına göre aylık asgari İSG uzmanı, işyeri hekimi ve DSP hizmet sürelerini hesaplayın.',
            'q' => '',
            'sonuclar' => [],
        ]);
    }
}
