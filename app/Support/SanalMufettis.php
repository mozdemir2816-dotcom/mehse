<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sanal Müfettiş — 6331 Sayılı Kanun İdari Para Cezası (İPC) Risk Simülatörü (E1).
 * Firma bazında 10+ temel yasal yükümlülüğü denetler; 0–100 uyum puanı, 6331 m.26
 * uyarınca tehlike sınıfı ve çalışan sayısına ölçeklenmiş tahmini TL ceza aralığı
 * ve madde bazlı aksiyon listesi (PDF dahil) üretir.
 */
class SanalMufettis
{
    /**
     * 6331 m.26 kademeli İPC çarpanı (çalışan sayısı & tehlike sınıfı).
     * 10'dan az: Az=1.0, Tehlikeli=1.25, Çok=1.5
     * 10–49: Az=1.0, Tehlikeli=1.5, Çok=2.0
     * 50+: Az=1.5, Tehlikeli=2.0, Çok=3.0
     */
    public static function ipcCarpani(Firma $firma): float
    {
        $n = max(1, (int) $firma->calisan_sayisi);
        $sinif = (string) ($firma->tehlike_sinifi ?: 'tehlikeli');

        if ($n < 10) {
            return match ($sinif) {
                'cok_tehlikeli' => 1.5,
                'tehlikeli' => 1.25,
                default => 1.0,
            };
        }

        if ($n < 50) {
            return match ($sinif) {
                'cok_tehlikeli' => 2.0,
                'tehlikeli' => 1.5,
                default => 1.0,
            };
        }

        return match ($sinif) {
            'cok_tehlikeli' => 3.0,
            'tehlikeli' => 2.0,
            default => 1.5,
        };
    }

    /**
     * @return array{
     *   puan: int,
     *   seviye: string,
     *   renk: string,
     *   carpan: float,
     *   min_ceza_tl: int,
     *   max_ceza_tl: int,
     *   eksik_sayisi: int,
     *   toplam_kriter: int,
     *   bulgular: array<int, array{kod: string, baslik: string, dayanak: string, agirlik: int, durum: string, min_tl: int, max_tl: int, aksiyon: string}>
     * }
     */
    public static function denetle(Firma $firma): array
    {
        $carpan = static::ipcCarpani($firma);
        $calisan = max(1, (int) $firma->calisan_sayisi);
        $checklist = collect(PortfoyKarne::firmaChecklistDetay($firma))->keyBy('anahtar');

        // 2026 baz İPC yaklaşık alt/üst tutarları (TL) — çalışan sayısı/devamlılık etkisini alt-üst aralık gösterir
        $kurallar = [
            ['kod' => 'ORG-001', 'anahtar' => 'igu_atamasi', 'baslik' => 'İş Güvenliği Uzmanı Görevlendirmesi', 'dayanak' => '6331 SK md.6 · md.26/1-b', 'agirlik' => 14, 'baz_min' => 88663, 'baz_max' => 177326, 'aksiyon' => 'İSG-KATİP üzerinden geçerli İGU atamasını tamamlayın.'],
            ['kod' => 'ORG-002', 'anahtar' => 'hekim_atamasi', 'baslik' => 'İşyeri Hekimi Görevlendirmesi', 'dayanak' => '6331 SK md.6 · md.26/1-b', 'agirlik' => 12, 'baz_min' => 88663, 'baz_max' => 177326, 'aksiyon' => 'İSG-KATİP üzerinden işyeri hekimi atamasını tanımlayın.'],
            ['kod' => 'RISK-001', 'anahtar' => 'risk_degerlendirmesi', 'baslik' => 'Risk Değerlendirmesi', 'dayanak' => '6331 SK md.10 · md.26/1-ç', 'agirlik' => 15, 'baz_min' => 53197, 'baz_max' => 159591, 'aksiyon' => 'Tehlike sınıfına uygun güncel Risk Değerlendirmesi hazırlayıp onaylayın.'],
            ['kod' => 'EME-001', 'anahtar' => 'acil_durum_plani', 'baslik' => 'Acil Durum Eylem Planı & Kroki', 'dayanak' => '6331 SK md.11 · md.26/1-d', 'agirlik' => 10, 'baz_min' => 17731, 'baz_max' => 53193, 'aksiyon' => 'ADEP, tahliye krokisi ve destek ekiplerini oluşturun.'],
            ['kod' => 'DRL-001', 'anahtar' => 'acil_durum_tatbikat', 'baslik' => 'Yıllık Acil Durum Tatbikatı', 'dayanak' => 'Acil Durumlar Yön. md.13 · 6331 md.26/1-d', 'agirlik' => 7, 'baz_min' => 17731, 'baz_max' => 35462, 'aksiyon' => 'Yılda en az 1 tatbikat yapıp fotoğraflı tutanak düzenleyin.'],
            ['kod' => 'EDU-001', 'anahtar' => 'egitim_katilim_formu', 'baslik' => 'Çalışan Temel İSG Eğitimleri', 'dayanak' => '6331 SK md.17 · md.26/1-ğ', 'agirlik' => 12, 'baz_min' => 7180 * min($calisan, 5), 'baz_max' => 7180 * min($calisan, 30), 'aksiyon' => 'Tüm çalışanlara temel İSG eğitimi verip katılım tutanağı ve sertifika düzenleyin.'],
            ['kod' => 'HEA-001', 'anahtar' => 'saglik_raporu', 'baslik' => 'İşe Giriş / Periyodik Sağlık Gözetimi (EK-2)', 'dayanak' => '6331 SK md.15 · md.26/1-f', 'agirlik' => 10, 'baz_min' => 17731, 'baz_max' => 17731 * min($calisan, 10), 'aksiyon' => 'İşyeri hekimi tarafından EK-2 sağlık gözetimlerini tamamlayın.'],
            ['kod' => 'EQP-001', 'anahtar' => 'periyodik_kontrol_raporu', 'baslik' => 'İş Ekipmanları Periyodik Kontrolleri', 'dayanak' => '6331 SK md.4 · Ekipman Yön. · md.26/1-a', 'agirlik' => 8, 'baz_min' => 35462, 'baz_max' => 106386, 'aksiyon' => 'EKİPNET yetkili kişilere periyodik kontrolleri yaptırıp raporları yükleyin.'],
            ['kod' => 'LOG-001', 'anahtar' => 'onayli_defter_nushalari', 'baslik' => 'Onaylı Tespit ve Öneri Defteri', 'dayanak' => 'İSG Hizmetleri Yön. md.7 · 6331 md.26/1-b', 'agirlik' => 5, 'baz_min' => 26597, 'baz_max' => 53194, 'aksiyon' => 'Güncel onaylı defter nüshalarını düzenli olarak imzalatıp arşivleyin.'],
            ['kod' => 'REP-001', 'anahtar' => 'calisan_temsilcisi', 'baslik' => 'Çalışan Temsilcisi Seçimi / Ataması', 'dayanak' => '6331 SK md.20 · md.26/1-i', 'agirlik' => 4, 'baz_min' => 17731, 'baz_max' => 35462, 'aksiyon' => 'Seçim veya atama tutanağıyla çalışan temsilcisini görevlendirin.'],
            ['kod' => 'BRD-001', 'anahtar' => 'isg_kurulu', 'baslik' => 'İSG Kurulu (50+ Çalışan)', 'dayanak' => '6331 SK md.22 · md.26/1-j', 'agirlik' => 3, 'baz_min' => 35462, 'baz_max' => 70924, 'aksiyon' => 'Zorunlu üyelerle İSG Kurulu oluşturup periyodik toplantıları yapın.'],
        ];

        $bulgular = [];
        $kazanilan = 0;
        $toplamAgirlik = 0;
        $minCeza = 0;
        $maxCeza = 0;
        $eksikSayisi = 0;

        foreach ($kurallar as $k) {
            $kayit = $checklist->get($k['anahtar']);
            $muaf = (bool) ($kayit['muaf'] ?? false);
            if ($muaf) {
                continue;
            }

            $tamam = (bool) ($kayit['tamam'] ?? false);
            $toplamAgirlik += $k['agirlik'];

            if ($tamam) {
                $kazanilan += $k['agirlik'];
                $bulgular[] = [
                    'kod' => $k['kod'],
                    'baslik' => $k['baslik'],
                    'dayanak' => $k['dayanak'],
                    'agirlik' => $k['agirlik'],
                    'durum' => 'uyumlu',
                    'min_tl' => 0,
                    'max_tl' => 0,
                    'aksiyon' => 'Mevcut ve uyumlu — yenileme tarihini takip edin.',
                ];
            } else {
                $eksikSayisi++;
                $mn = (int) round($k['baz_min'] * $carpan);
                $mx = (int) round($k['baz_max'] * $carpan);
                $minCeza += $mn;
                $maxCeza += $mx;

                $bulgular[] = [
                    'kod' => $k['kod'],
                    'baslik' => $k['baslik'],
                    'dayanak' => $k['dayanak'],
                    'agirlik' => $k['agirlik'],
                    'durum' => 'eksik',
                    'min_tl' => $mn,
                    'max_tl' => $mx,
                    'aksiyon' => $k['aksiyon'],
                ];
            }
        }

        $puan = $toplamAgirlik > 0 ? (int) round(($kazanilan / $toplamAgirlik) * 100) : 100;
        [$seviye, $renk] = match (true) {
            $puan >= 85 => ['Yüksek Uyum (Denetime Hazır)', '#15803d'],
            $puan >= 60 => ['Orta Risk (Eksikler Tamamlanmalı)', '#b45309'],
            default => ['Yüksek Ceza Riski (Acil Müdahale)', '#dc2626'],
        };

        return [
            'puan' => $puan,
            'seviye' => $seviye,
            'renk' => $renk,
            'carpan' => $carpan,
            'min_ceza_tl' => $minCeza,
            'max_ceza_tl' => $maxCeza,
            'eksik_sayisi' => $eksikSayisi,
            'toplam_kriter' => count($bulgular),
            'bulgular' => $bulgular,
        ];
    }

    public static function pdf(Firma $firma): StreamedResponse
    {
        $rapor = static::denetle($firma);
        $pdf = Pdf::loadView('pdf.sanal-mufettis', [
            'firma' => $firma,
            'rapor' => $rapor,
            'tarih' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i'),
        ])->setPaper('a4');

        $ad = 'sanal-mufettis-ipc-analizi-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
