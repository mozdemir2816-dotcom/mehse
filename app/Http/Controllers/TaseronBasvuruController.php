<?php

namespace App\Http\Controllers;

use App\Models\TaseronBasvurusu;
use Illuminate\Http\Request;

/**
 * Giriş gerektirmeyen Taşeron Self-Servis Evrak & Başvuru Portalı (K2).
 * OSGB/işveren TaseronYonetimi sayfasından link/QR üretir; taşeron bilgilerini
 * ve İSG evraklarını yükler; uzman tek tıkla onaylayıp taşeron kaydına aktarır.
 */
class TaseronBasvuruController extends Controller
{
    public function goster(string $token)
    {
        $basvuru = TaseronBasvurusu::with('firma')->where('token', $token)->firstOrFail();

        return view('pages.taseron-basvuru', [
            'basvuru' => $basvuru,
            'belgeTurleri' => config('isg.taseron.belge_turleri', []),
        ]);
    }

    public function kaydet(Request $request, string $token)
    {
        $basvuru = TaseronBasvurusu::with('firma')->where('token', $token)->firstOrFail();

        if ($basvuru->durum === 'onaylandi') {
            return back()->with('bilgi', 'Bu başvuru zaten onaylanmıştır.');
        }

        $veri = $request->validate([
            'unvan' => ['required', 'string', 'max:200'],
            'vergi_no' => ['nullable', 'string', 'max:30'],
            'faaliyet' => ['required', 'string', 'max:200'],
            'yetkili_ad' => ['required', 'string', 'max:140'],
            'telefon' => ['required', 'string', 'max:40'],
            'eposta' => ['nullable', 'email', 'max:160'],
            'belge_turu' => ['nullable', 'string', 'max:60'],
            'belgeler.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $mevcutBelgeler = (array) ($basvuru->yuklenen_belgeler ?? []);
        $seciliTur = ($veri['belge_turu'] ?? null) ?: 'sgk_bildirgesi';

        if ($request->hasFile('belgeler')) {
            foreach ($request->file('belgeler') as $dosya) {
                if (! $dosya || ! $dosya->isValid()) {
                    continue;
                }
                $yol = $dosya->store('taseron-basvuru', 'public');
                $mevcutBelgeler[] = [
                    'tur' => $seciliTur,
                    'dosya' => $yol,
                    'ad' => $dosya->getClientOriginalName(),
                    'tarih' => now()->toDateString(),
                ];
            }
        }

        $basvuru->update([
            'unvan' => trim($veri['unvan']),
            'vergi_no' => trim((string) ($veri['vergi_no'] ?? '')) ?: null,
            'faaliyet' => trim($veri['faaliyet']),
            'yetkili_ad' => trim($veri['yetkili_ad']),
            'telefon' => trim($veri['telefon']),
            'eposta' => trim((string) ($veri['eposta'] ?? '')) ?: null,
            'yuklenen_belgeler' => $mevcutBelgeler,
            'durum' => 'bekliyor',
            'ret_nedeni' => null,
            'basvuru_at' => now(),
        ]);

        return back()->with('tamam', 'Başvurunuz ve evraklarınız başarıyla alındı. İSG uzmanının incelemesinin ardından bilgilendirileceksiniz.');
    }
}
