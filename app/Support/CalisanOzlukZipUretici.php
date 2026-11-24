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

        $hamTemp = tempnam(sys_get_temp_dir(), 'mehse_ozluk_');
        @unlink($hamTemp);
        $gecici = $hamTemp.'.zip';
        $zip = new ZipArchive;
        if ($zip->open($gecici, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Özlük ZIP dosyası oluşturulamadı.');
        }

        $roller = collect($calisan->rol_etiketleri ?? [])
            ->map(fn ($r) => Calisan::ROL_ETIKETLERI[$r] ?? $r)
            ->implode(', ');

        $satirlar = [
            'MEHSE İSG — ÇALIŞAN ÖZLÜK DOSYASI ÖZETİ',
            '========================================',
            'GİZLİLİK NOTU (6331 SK m.15/5 & 6698 KVKK): Bu dosya çalışana ait kişisel ve özel',
            'nitelikli sağlık verilerini içerebilir; yalnızca yetkili kişilerce muhafaza edilir.',
            '----------------------------------------',
            'İşyeri        : '.($calisan->firma?->unvan ?: '—'),
            'Ad Soyad      : '.$calisan->ad_soyad,
            'T.C. Kimlik   : '.($calisan->maskeliTc() ?: '—'),
            'Görev / Şube  : '.trim(($calisan->gorev ?: '—').' / '.($calisan->sube ?: '—')),
            'İşe Giriş     : '.($calisan->ise_giris?->format('d.m.Y') ?: '—'),
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
        $satirlar[] = '--- EK-2 MUAYENE TAKİP KAYITLARI ('.$calisan->muayeneFormlari->count().' · Tıbbi detaylar gizlidir — 6331 m.15/5) ---';
        foreach ($calisan->muayeneFormlari as $mf) {
            $satirlar[] = '• '.($mf->muayene_tarihi?->format('d.m.Y') ?: '—').' · '.($mf->muayeneTuruEtiketi()).' · Kayıtlı';
        }

        $satirlar[] = '';
        $satirlar[] = '--- KKD ZİMMETLERİ ('.$calisan->kkdZimmetleri->count().') ---';
        foreach ($calisan->kkdZimmetleri as $kz) {
            $satirlar[] = '• '.($kz->teslim_tarihi?->format('d.m.Y') ?: '—').' · '.$kz->tur.' ('.$kz->adet.' adet)';
        }

        $zip->addFromString('00_Ozluk_Ozeti.txt', implode("\r\n", $satirlar));

        // KVKK m.12 güvenliği: yalnızca ad_soyad doluysa ve TAM eşleşen kayıtlar dahil edilir (LIKE '%...%' yasak!)
        $tamAd = trim((string) $calisan->ad_soyad);
        $arsivler = $tamAd !== ''
            ? ArsivDosya::query()
                ->where('firma_id', $calisan->firma_id)
                ->whereRaw('LOWER(TRIM(kisi_adi)) = ?', [mb_strtolower($tamAd, 'UTF-8')])
                ->get()
            : collect();

        foreach ($arsivler as $idx => $a) {
            $guvenliAd = basename(str_replace('\\', '/', (string) ($a->dosya_adi ?: $a->dosya_yolu)));
            $guvenliAd = preg_replace('/[^\p{L}\p{N}._\-\s]/u', '_', $guvenliAd) ?: 'belge.pdf';
            if ($a->dosya_yolu && Storage::disk('local')->exists($a->dosya_yolu)) {
                $zip->addFile(Storage::disk('local')->path($a->dosya_yolu), sprintf('Belgeler/%02d_%s', $idx + 1, $guvenliAd));
            } elseif ($a->dosya_yolu && Storage::disk('public')->exists($a->dosya_yolu)) {
                $zip->addFile(Storage::disk('public')->path($a->dosya_yolu), sprintf('Belgeler/%02d_%s', $idx + 1, $guvenliAd));
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
