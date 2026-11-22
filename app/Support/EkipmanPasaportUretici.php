<?php

namespace App\Support;

use App\Models\IsEkipmani;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Makine / İş Ekipmanı üzerine asılabilir QR Emniyet Pasaportu PDF üreticisi (C23).
 * Operatör QR kodu okutarak doğrudan `/ekipman/{anahtar}` ekranına (talimat + günlük kontrol) ulaşır.
 */
class EkipmanPasaportUretici
{
    public static function ekipnetNoGecerliMi(?string $no): bool
    {
        $temiz = strtoupper(trim((string) $no));
        if ($temiz === '') {
            return false;
        }

        return (bool) preg_match('/^(K\d{6,12}|\d{7,14}|EKP-\d{4,12})$/i', $temiz);
    }

    public static function pdf(IsEkipmani $ekipman, ?string $ekipnetNo = null): StreamedResponse
    {
        $ekipman->loadMissing('firma');

        if (blank($ekipman->qr_anahtar)) {
            $ekipman->forceFill(['qr_anahtar' => Str::random(32)])->save();
        }

        $operatorUrl = route('ekipman.operator', ['anahtar' => $ekipman->qr_anahtar]);
        $qrUri = (new QRCode(new QROptions([
            'outputBase64' => true,
            'addQuietzone' => true,
        ])))->render($operatorUrl);

        $pdf = Pdf::loadView('pdf.ekipman-emniyet-pasaportu', [
            'ekipman' => $ekipman,
            'firma' => $ekipman->firma,
            'ekipnetNo' => trim((string) ($ekipnetNo ?? '')),
            'operatorUrl' => $operatorUrl,
            'qrUri' => $qrUri,
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a5', 'portrait');

        $ad = 'emniyet-pasaportu-'.Str::slug($ekipman->ekipman_adi ?: 'ekipman').'-'.$ekipman->id.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
