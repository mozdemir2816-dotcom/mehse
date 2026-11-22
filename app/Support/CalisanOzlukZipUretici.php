<?php

namespace App\Support;

use App\Models\ArsivDosya;
use App\Models\Calisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Tek çalışan için tüm İSG özlük bilgilerini ve kayıtlı evraklarını tek `.zip`
 * paketi olarak dışa aktarır (N3).
 */
class CalisanOzlukZipUretici
{
    public static function indir(Calisan $calisan): StreamedResponse
    {
        $calisan->loadMissing(['firma', 'egitimKayitlari', 'muayeneFormlari', 'kkdZimmetleri']);

        $gecici = tempnam(sys_get_temp_dir(), 'mehse_ozluk_').'.zip';
        $zip = new ZipArchive;
        $zip->open($gecici, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $roller = collect($calisan->rol_etiketleri ?? [])
            ->map(fn ($r) => Calisan::ROL_ETIKETLERI[$r] ?? $r)
            ->implode(', ');

        $satirlar = [
            'MEHSE İSG — ÇALIŞAN ÖZLÜK DOSYASI ÖZETİ',
            '========================================',
            'İşyeri        : '.($calisan->firma?->unvan ?: '—'),
            'Ad Soyad      : '.$calisan->ad_soyad,
            'T.C. Kimlik   : '.($calisan->maskeliTc() ?: '—'),
            'Görev / Şube  : '.trim(($calisan->gorev ?: '—').' / '.($calisan->sube ?: '—')),
            'İşe Giriş     : '.($calisan->ise_giris?->format('d.m.Y') ?: '—'),
            'Kan Grubu     : '.($calisan->kan_grubu ?: '—'),
            'İSG Rolleri   : '.($roller ?: '—'),
            'MYK Belge No  : '.($calisan->myk_belge_no ?: '—'),
            '',
            '--- MESLEKİ BELGE GEÇERLİLİKLERİ ---',
        ];

        $belgeler = $calisan->meslekiBelgeler();
        if ($belgeler) {
            foreach ($belgeler as $b) {
                $satirlar[] = sprintf('• %s: %s (%d gün)', $b['ad'], $b['tarih'], $b['kalan_gun']);
            }
        } else {
            $satirlar[] = '• Tanımlı mesleki belge vadesi yok.';
        }

        $satirlar[] = '';
        $satirlar[] = '--- İSG EĞİTİMLERİ ('.$calisan->egitimKayitlari->count().') ---';
        foreach ($calisan->egitimKayitlari as $eg) {
            $satirlar[] = '• '.($eg->tarih?->format('d.m.Y') ?: '—').' · '.($eg->tur ?: 'İSG Eğitimi');
        }

        $satirlar[] = '';
        $satirlar[] = '--- EK-2 MUAYENE KAYITLARI ('.$calisan->muayeneFormlari->count().') ---';
        foreach ($calisan->muayeneFormlari as $mf) {
            $satirlar[] = '• '.($mf->muayene_tarihi?->format('d.m.Y') ?: '—').' · '.($mf->sonuc ?: 'Kayıtlı');
        }

        $satirlar[] = '';
        $satirlar[] = '--- KKD ZİMMETLERİ ('.$calisan->kkdZimmetleri->count().') ---';
        foreach ($calisan->kkdZimmetleri as $kz) {
            $satirlar[] = '• '.($kz->teslim_tarihi?->format('d.m.Y') ?: '—').' · '.$kz->tur.' ('.$kz->adet.' adet)';
        }

        $zip->addFromString('00_Ozluk_Ozeti.txt', implode("\r\n", $satirlar));

        // Arşivde bu çalışanın adıyla kayıtlı dosyalar varsa pakete dahil et
        $arsivler = ArsivDosya::query()
            ->where('firma_id', $calisan->firma_id)
            ->where('kisi_adi', 'like', '%'.$calisan->ad_soyad.'%')
            ->get();

        foreach ($arsivler as $idx => $a) {
            if ($a->dosya_yolu && Storage::disk('local')->exists($a->dosya_yolu)) {
                $zip->addFile(Storage::disk('local')->path($a->dosya_yolu), sprintf('Belgeler/%02d_%s', $idx + 1, $a->dosya_adi ?: basename($a->dosya_yolu)));
            } elseif ($a->dosya_yolu && Storage::disk('public')->exists($a->dosya_yolu)) {
                $zip->addFile(Storage::disk('public')->path($a->dosya_yolu), sprintf('Belgeler/%02d_%s', $idx + 1, $a->dosya_adi ?: basename($a->dosya_yolu)));
            }
        }

        $zip->close();

        $dosyaAdi = 'ozluk-'.Str::slug($calisan->ad_soyad ?: 'calisan').'-'.now()->format('Ymd').'.zip';

        return response()->streamDownload(function () use ($gecici): void {
            readfile($gecici);
            @unlink($gecici);
        }, $dosyaAdi, ['Content-Type' => 'application/zip']);
    }
}
