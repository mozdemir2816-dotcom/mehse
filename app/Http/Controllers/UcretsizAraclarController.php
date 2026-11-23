<?php

namespace App\Http\Controllers;

use App\Models\NaceKodu;
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
            if ($tam = NaceKodu::bul($q)) {
                $sonuclar[] = [
                    'kod' => (string) $tam->kod,
                    'ad' => (string) ($tam->tanim ?: $tam->sektor_adi),
                    'sinif' => (string) $tam->tehlike_sinifi,
                ];
            } else {
                $bulunanlar = preg_match('/^\d{2}\.?\d{2}$/', $q)
                    ? NaceKodu::nace4IleBul($q)
                    : NaceKodu::faaliyetAra($q);

                foreach ($bulunanlar->take(25) as $k) {
                    $sonuclar[] = [
                        'kod' => (string) $k->kod,
                        'ad' => (string) ($k->tanim ?: $k->sektor_adi),
                        'sinif' => (string) $k->tehlike_sinifi,
                    ];
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

    public function ipcCezaHesaplama()
    {
        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'ipc',
            'baslik' => '2026 İSG İdari Para Cezası (6331 m.26) Hesaplayıcı (Ücretsiz)',
            'aciklama' => 'Tehlike sınıfı ve çalışan sayısına göre 6331 sayılı Kanun m.26 kademeli İPC çarpanı ile tahmini ceza riskinizi anında hesaplayın.',
            'q' => '',
            'sonuclar' => [],
        ]);
    }

    public function ilkyardimciDestekHesaplama()
    {
        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'ekip',
            'baslik' => 'İlkyardımcı & Acil Durum Destek Elemanı Hesaplayıcı (Ücretsiz)',
            'aciklama' => 'Acil Durumlar Hakkında Yönetmelik ve İlkyardım Yönetmeliğine göre zorunlu söndürme, kurtarma, koruma ve sertifikalı ilkyardımcı sayısını hesaplayın.',
            'q' => '',
            'sonuclar' => [],
        ]);
    }

    public function fineKinneyRiskHesaplama()
    {
        return view('pages.ucretsiz-araclar', [
            'aktifArac' => 'risk',
            'baslik' => 'Fine-Kinney & 5×5 Matris Risk Skoru Hesaplayıcı (Ücretsiz)',
            'aciklama' => 'Fine-Kinney (Olasılık × Frekans × Şiddet) ve 5×5 L-Tipi Matris risk puanınızı, risk sınıfınızı ve yasal aksiyon önceliğinizi anında görün.',
            'q' => '',
            'sonuclar' => [],
        ]);
    }

    /** Kayıtsız, salt-okunur canlı OSGB Demo Turu (F9). */
    public function canliDemo()
    {
        return view('pages.demo');
    }
}
