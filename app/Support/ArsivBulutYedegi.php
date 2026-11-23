<?php

namespace App\Support;

use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Google Drive / OneDrive'a sürükle-bırak yedeklemeye hazır yapılandırılmış
 * Arşiv Toplu Bulut Yedeği ZIP üreticisi (D28).
 * Klasör yapısı: `/{Firma_Adi}/{Kategori}/{Dosya_Adi}` + `manifest.json`.
 */
class ArsivBulutYedegi
{
    public static function indir(User $user, ?int $firmaId = null): ?BinaryFileResponse
    {
        $firmalar = Firma::query()
            ->where('user_id', $user->id)
            ->where('aktif', true)
            ->when($firmaId, fn ($q) => $q->whereKey($firmaId))
            ->get()
            ->keyBy('id');

        if ($firmalar->isEmpty()) {
            return null;
        }

        $dosyalar = ArsivDosya::query()
            ->whereIn('firma_id', $firmalar->keys())
            ->where('aktif', true)
            ->get();

        $gecici = tempnam(sys_get_temp_dir(), 'mehse_yedek_').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($gecici, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $disk = Storage::disk('public');
        $manifest = [
            'olusturulma' => now()->timezone('Europe/Istanbul')->toIso8601String(),
            'uzman' => $user->name,
            'firma_sayisi' => $firmalar->count(),
            'dosya_sayisi' => 0,
            'kayitlar' => [],
        ];

        foreach ($dosyalar as $d) {
            $firma = $firmalar->get($d->firma_id);
            if (! $firma) {
                continue;
            }
            $firmaKlasor = Str::slug(Str::limit($firma->secimEtiketi(), 50, ''));
            $katKlasor = Str::slug((string) ($d->kategori ?: 'genel'));

            $manifest['kayitlar'][] = [
                'firma' => $firma->secimEtiketi(),
                'kategori' => $d->kategori,
                'ad' => $d->etiket(),
                'baslangic' => $d->baslangic_tarihi?->toDateString(),
                'bitis' => $d->gecerlilik_sonu?->toDateString(),
                'dosya_yolu' => $d->dosya_yolu,
            ];

            if ($d->dosya_yolu && $disk->exists($d->dosya_yolu)) {
                $ad = basename($d->dosya_yolu);
                $zip->addFile($disk->path($d->dosya_yolu), "{$firmaKlasor}/{$katKlasor}/{$d->id}_{$ad}");
                $manifest['dosya_sayisi']++;
            }
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $indirmeAdi = 'mehse-bulut-arsiv-yedegi-'.now()->format('Y-m-d').'.zip';

        return response()->download($gecici, $indirmeAdi)->deleteFileAfterSend(true);
    }
}
