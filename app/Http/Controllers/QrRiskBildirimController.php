<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Support\FirmaRiskBildirimQrUretici;
use Illuminate\Http\Request;

/**
 * Çalışanlar için giriş gerektirmeyen QR kod ile Saha Risk / Ramak Kala Bildirimi (C16).
 * Bildirimler doğrudan ilgili firmanın Saha Bulguları havuzuna (`kaynak = 'qr'`) düşer.
 */
class QrRiskBildirimController extends Controller
{
    public function goster(int $firmaId, string $imza)
    {
        $firma = Firma::withoutGlobalScopes()->where('aktif', true)->findOrFail($firmaId);
        abort_unless(FirmaRiskBildirimQrUretici::imzaGecerliMi($firma, $imza), 403);

        return view('qr-risk-bildir', [
            'firma' => $firma,
            'imza' => $imza,
        ]);
    }

    public function kaydet(int $firmaId, string $imza, Request $request)
    {
        $firma = Firma::withoutGlobalScopes()->where('aktif', true)->findOrFail($firmaId);
        abort_unless(FirmaRiskBildirimQrUretici::imzaGecerliMi($firma, $imza), 403);

        $veri = $request->validate([
            'tur' => ['required', 'in:tehlike,ramak_kala'],
            'bolum' => ['required', 'string', 'max:120'],
            'uygunsuzluk' => ['required', 'string', 'min:8', 'max:1500'],
            'aciliyet' => ['required', 'in:dusuk,orta,yuksek,kritik'],
            'bildiren' => ['nullable', 'string', 'max:120'],
            'isimsiz' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ], [
            'bolum.required' => 'Lütfen bölüm veya çalışma alanını yazın.',
            'uygunsuzluk.required' => 'Lütfen gözlemlediğiniz tehlikeyi veya ramak kala olayını açıklayın.',
            'uygunsuzluk.min' => 'Açıklama en az 8 karakter olmalıdır.',
        ]);

        $fotolar = [];
        if ($request->hasFile('foto')) {
            $fotolar[] = $request->file('foto')->store('saha-bulgulari/qr', 'public');
        }

        $puan = match ($veri['aciliyet']) {
            'kritik' => [5, 5],
            'yuksek' => [4, 4],
            'orta' => [3, 3],
            default => [2, 2],
        };

        $turEtiket = $veri['tur'] === 'ramak_kala' ? '[QR Ramak Kala]' : '[QR Risk Bildirimi]';
        $bildirenNot = empty($veri['isimsiz']) && filled($veri['bildiren'] ?? null)
            ? ' (Bildiren: '.trim($veri['bildiren']).')'
            : ' (İsimsiz bildirim)';

        $bulgu = SahaBulgusu::create([
            'firma_id' => $firma->id,
            'bolum' => trim($veri['bolum']),
            'kategori' => $veri['tur'] === 'ramak_kala' ? 'Ramak Kala' : 'Çalışan QR Bildirimi',
            'uygunsuzluk' => $turEtiket.' '.trim($veri['uygunsuzluk']).$bildirenNot,
            'olasilik' => $puan[0],
            'siddet' => $puan[1],
            'oncelik' => $veri['aciliyet'],
            'durum' => 'acik',
            'kaynak' => 'qr',
            'fotograflar' => $fotolar,
        ]);

        return redirect()
            ->route('risk.bildir', ['firma' => $firma->id, 'imza' => $imza])
            ->with('basarili', 'Bildiriminiz İSG birimine iletildi. Takip No: '.$bulgu->bulgu_no);
    }
}
