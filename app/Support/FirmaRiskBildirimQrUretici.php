<?php

namespace App\Support;

use App\Filament\Pages\SahaEkrani;
use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firma Bazlı "Sahadan Risk, Tehlike ve Ramak Kala Bildirim QR Posteri" (A4 PDF)
 * — isgpratik "Risk bildirim QR kodu" karşılığı. İşyeri girişine, ilan panosuna
 * veya üretim bölümlerine asılarak saha sorumlularının / çalışanların telefon
 * kamerasıyla QR kodu okutup doğrudan o firmanın Saha & Tehlike Bildirim ekranına
 * ulaşmasını sağlar.
 */
class FirmaRiskBildirimQrUretici
{
    public static function imzaKodu(Firma $firma): string
    {
        return substr(hash_hmac('sha256', 'mehse-risk-qr-'.$firma->id, (string) config('app.key')), 0, 20);
    }

    public static function imzaGecerliMi(Firma $firma, string $imza): bool
    {
        return hash_equals(static::imzaKodu($firma), $imza);
    }

    public static function bildirimAdresi(Firma $firma): string
    {
        return route('risk.bildir', [
            'firma' => $firma->id,
            'imza' => static::imzaKodu($firma),
        ]);
    }

    public static function qrDataUri(Firma $firma): string
    {
        return (new QRCode(new QROptions([
            'outputBase64' => true,
            'addQuietzone' => true,
        ])))->render(static::bildirimAdresi($firma));
    }

    public static function pdf(Firma $firma): StreamedResponse
    {
        $firma->loadMissing(['igu', 'isyeriHekimi']);

        $pdf = Pdf::loadView('pdf.firma-risk-bildirim-qr', [
            'firma' => $firma,
            'qrDataUri' => static::qrDataUri($firma),
            'bildirimUrl' => static::bildirimAdresi($firma),
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        $ad = 'risk-bildirim-qr-posteri-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
