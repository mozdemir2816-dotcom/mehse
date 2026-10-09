<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\Talimat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Çalışma Talimatı PDF üretimi (dompdf) — kullanıcının inşaat talimatları
 * düzeninde (10.10.2026): her sayfada firma logosu | talimat adı | doküman
 * no / yayın / revizyon künyesi, altta sayfa no; içerik düz numaralı ya da
 * bölümlü (Amaç, Kapsam, KKD…), sonda taahhüt + TEBLİĞ EDEN / TEBELLÜĞ EDEN.
 */
class TalimatUretici
{
    public static function pdf(Talimat $talimat, bool $imzali = true): StreamedResponse
    {
        $talimat->loadMissing('firma.igu', 'firma.user');

        $pdf = Pdf::loadView('pdf.talimat', [
            'talimat' => $talimat,
            'firma' => $talimat->firma,
            'logo' => self::firmaLogosu($talimat->firma),
            'tebligEden' => self::tebligEden($talimat->firma),
            'imzali' => $imzali,
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($talimat, 'pdf'));
    }

    public static function dosyaAdi(Talimat $talimat, string $uzanti): string
    {
        return 'talimat-'.Str::slug($talimat->baslik).'.'.$uzanti;
    }

    /** Sol üst köşe: talimatın ait olduğu firmanın logosu (kullanıcı kararı); yoksa null → unvan yazılır. */
    public static function firmaLogosu(?Firma $firma): ?string
    {
        return $firma?->logo && is_file($yol = Storage::disk('public')->path($firma->logo)) ? $yol : null;
    }

    /** Talimatı tebliğ eden / teslim eden: firmanın İGU'su, atanmamışsa hesap sahibi. */
    public static function tebligEden(?Firma $firma): object
    {
        $igu = $firma?->igu;
        $sahip = $firma?->user;

        return (object) [
            'ad' => $igu?->ad_soyad ?: $sahip?->name,
            'unvan' => 'İş Güvenliği Uzmanı',
            'kase_gorseli' => $igu?->kase_gorseli ?: $sahip?->kase_gorseli,
            'imza_gorseli' => $igu?->imza_gorseli ?: $sahip?->imza_gorseli,
        ];
    }
}
