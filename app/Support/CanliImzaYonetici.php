<?php

namespace App\Support;

use App\Models\CanliImza;
use App\Models\Firma;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tablette / Telefonda / QR ile Canlı Çalışan İmzası yöneticisi (C15).
 * 8 belge türünde canlı imza oturumu açar, PNG olarak kaydeder ve
 * QR doğrulamalı mühürlü PDF üretir.
 */
class CanliImzaYonetici
{
    public static function oturumOlustur(
        User $user,
        Firma $firma,
        string $belgeTuru,
        ?int $belgeId = null,
        ?string $baslik = null,
        ?string $calisanAdi = null,
        ?string $calisanTc = null,
    ): CanliImza {
        return CanliImza::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'belge_turu' => array_key_exists($belgeTuru, CanliImza::BELGE_TURLERI) ? $belgeTuru : 'egitim_katilim',
            'belge_id' => $belgeId,
            'belge_baslik' => trim((string) $baslik) ?: (CanliImza::BELGE_TURLERI[$belgeTuru] ?? 'İSG Belgesi'),
            'imza_token' => Str::random(40),
            'calisan_adi' => $calisanAdi ? trim($calisanAdi) : null,
            'calisan_tc' => $calisanTc ? preg_replace('/\D/', '', $calisanTc) : null,
        ]);
    }

    public static function imzaAdresi(CanliImza $imza): string
    {
        return route('canli-imza.form', ['token' => $imza->imza_token]);
    }

    public static function dogrulamaAdresi(CanliImza $imza): string
    {
        return route('canli-imza.dogrula', ['kod' => $imza->dogrulama_kodu]);
    }

    public static function qrDataUri(string $url): string
    {
        return (new QRCode(new QROptions([
            'outputBase64' => true,
            'addQuietzone' => true,
        ])))->render($url);
    }

    public static function imzaKaydet(
        CanliImza $imza,
        string $calisanAdi,
        ?string $calisanTc,
        string $dataUrl,
        Request $request,
    ): CanliImza {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=\s]+)$#', $dataUrl, $m)) {
            throw new \InvalidArgumentException('Geçerli bir PNG imza görseli gönderilmedi.');
        }

        $ham = base64_decode(preg_replace('/\s+/', '', $m[1]), true);
        if ($ham === false || strlen($ham) < 200 || strlen($ham) > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('İmza verisi geçersiz veya çok büyük.');
        }

        $dosyaAdi = 'canli-imzalar/imza-'.$imza->id.'-'.Str::random(10).'.png';
        Storage::disk('public')->put($dosyaAdi, $ham);

        $imza->forceFill([
            'calisan_adi' => trim($calisanAdi),
            'calisan_tc' => $calisanTc ? substr(preg_replace('/\D/', '', $calisanTc), 0, 11) : $imza->calisan_tc,
            'imza_gorseli' => $dosyaAdi,
            'imzalandi_at' => now(),
            'dogrulama_kodu' => $imza->dogrulama_kodu ?: strtoupper(Str::random(18)),
            'ip_adresi' => $request->ip(),
            'cihaz_bilgisi' => Str::limit((string) $request->userAgent(), 250, ''),
        ])->save();

        return $imza->refresh();
    }

    public static function muhurluPdf(CanliImza $imza): StreamedResponse
    {
        $imza->loadMissing(['firma', 'user']);
        $disk = Storage::disk('public');
        $imzaBase64 = ($imza->imza_gorseli && $disk->exists($imza->imza_gorseli))
            ? 'data:image/png;base64,'.base64_encode($disk->get($imza->imza_gorseli))
            : null;

        $qrUri = $imza->dogrulama_kodu ? static::qrDataUri(static::dogrulamaAdresi($imza)) : null;

        $pdf = Pdf::loadView('pdf.canli-imza-muhurlu', [
            'imza' => $imza,
            'imzaBase64' => $imzaBase64,
            'qrUri' => $qrUri,
            'dogrulamaUrl' => $imza->dogrulama_kodu ? static::dogrulamaAdresi($imza) : null,
        ])->setPaper('a4');

        $dosya = 'muhurlu-imza-'.Str::slug($imza->calisan_adi ?: 'belge').'-'.$imza->id.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $dosya);
    }
}
