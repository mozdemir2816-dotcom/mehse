<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KkdMatrisi;
use Illuminate\Support\Str;

/**
 * Çalışanın görevine göre KKD önerisi (30.10.2026): firmanın KKD Seçim Matrisi'nde
 * görev adına en çok uyan iş kalemi satırının "Zorunlu" sütunları önerilir.
 * Eşleşme yoksa öneri boş döner (zimmet formu elle seçime devam eder).
 */
class KkdGorevOnerisi
{
    /**
     * @return array{is_kalemi: string, kalemler: array<string, string>} sütun anahtarı => KKD adı (standart ekli etiketten)
     */
    public static function oner(Firma $firma, ?string $gorev): array
    {
        $bos = ['is_kalemi' => '', 'kalemler' => []];
        $gorevSozcukleri = static::sozcukler($gorev);

        if ($gorevSozcukleri === []) {
            return $bos;
        }

        $en = null;
        $enPuan = 0;

        foreach (KkdMatrisi::firmaIcin($firma)->satirlar ?? [] as $satir) {
            $puan = count(array_intersect($gorevSozcukleri, static::sozcukler($satir['is_kalemi'] ?? '')));

            if ($puan > $enPuan) {
                $en = $satir;
                $enPuan = $puan;
            }
        }

        if (! $en) {
            return $bos;
        }

        $etiketler = config('isg.kkd_matris.sutunlar');
        $kalemler = [];

        foreach ($etiketler as $sutun => $etiket) {
            if (($en[$sutun] ?? null) === 'zorunlu' && $sutun !== 'diger') {
                $kalemler[$sutun] = $etiket;
            }
        }

        return ['is_kalemi' => $en['is_kalemi'], 'kalemler' => $kalemler];
    }

    /** "Koruyucu Gözlük / Siperlik (EN 166)" → "Koruyucu Gözlük / Siperlik" (zimmet "KKD Türü" alanı için). */
    public static function turAdi(string $etiket): string
    {
        return trim(preg_replace('/\s*\(.*\)\s*$/u', '', $etiket));
    }

    /** @return array<int, string> */
    private static function sozcukler(?string $metin): array
    {
        $sade = Str::of(Str::ascii(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], (string) $metin))))->toString();

        return collect(preg_split('/[^a-z0-9]+/', $sade) ?: [])
            ->filter(fn (string $s) => strlen($s) >= 4)
            // "kaynakci" ↔ "kaynak" gibi ekli biçimler için kök (ilk 5 harf) karşılaştırılır
            ->map(fn (string $s) => substr($s, 0, 5))
            ->unique()->values()->all();
    }
}
