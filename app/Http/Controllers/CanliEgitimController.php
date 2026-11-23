<?php

namespace App\Http\Controllers;

use App\Models\CanliEgitimOturumu;
use App\Support\CanliEgitimYonetici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Canlı (Senkron) Eğitim Oturumu sınav ve ≥70 puana otomatik QR sertifika denetleyicisi (E5).
 */
class CanliEgitimController extends Controller
{
    public function goster(string $token)
    {
        $oturum = CanliEgitimOturumu::query()->with('firma')->where('katilim_token', $token)->where('aktif', true)->firstOrFail();

        return view('canli-egitim-sinav', [
            'oturum' => $oturum,
            'sonuc' => session('canli_egitim_sonuc'),
        ]);
    }

    public function sinavGonder(string $token, Request $request)
    {
        $oturum = CanliEgitimOturumu::query()->with('firma')->where('katilim_token', $token)->where('aktif', true)->firstOrFail();

        $veri = $request->validate([
            'ad_soyad' => ['required', 'string', 'min:3', 'max:160'],
            'tc' => ['nullable', 'string', 'max:14'],
            'cevaplar' => ['required', 'array'],
        ]);

        $sorular = (array) ($oturum->sorular ?? []);
        $toplam = max(1, count($sorular));
        $dogruSayisi = 0;

        foreach ($sorular as $i => $s) {
            if (isset($veri['cevaplar'][$i]) && (int) $veri['cevaplar'][$i] === (int) ($s['dogru'] ?? 0)) {
                $dogruSayisi++;
            }
        }

        $puan = (int) round(($dogruSayisi / $toplam) * 100);
        $gecti = $puan >= (int) $oturum->gecme_notu;
        $kod = strtoupper(Str::random(14));

        $kayit = [
            'ad_soyad' => trim($veri['ad_soyad']),
            'tc' => $veri['tc'] ? substr(preg_replace('/\D/', '', $veri['tc']), 0, 11) : null,
            'puan' => $puan,
            'dogru' => $dogruSayisi,
            'toplam' => $toplam,
            'gecti' => $gecti,
            'kod' => $kod,
            'tarih' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i'),
        ];

        // Eşzamanlı gönderimlerde JSON sonuclar yarış koşulunu (race condition) engelle.
        DB::transaction(function () use ($oturum, $kayit): void {
            $kilitli = CanliEgitimOturumu::query()->lockForUpdate()->findOrFail($oturum->id);
            $sonuclar = array_values((array) ($kilitli->sonuclar ?? []));
            $sonuclar[] = $kayit;
            $kilitli->forceFill(['sonuclar' => $sonuclar])->save();
        });

        return redirect()
            ->route('canli-egitim.form', ['token' => $token])
            ->with('canli_egitim_sonuc', $kayit);
    }

    public function sertifika(string $token, string $kod)
    {
        $oturum = CanliEgitimOturumu::query()->with(['firma', 'user'])->where('katilim_token', $token)->firstOrFail();
        $sonuc = collect($oturum->sonuclar ?? [])->firstWhere('kod', $kod);
        abort_unless($sonuc && ! empty($sonuc['gecti']), 404);

        return CanliEgitimYonetici::sertifikaPdf($oturum, $sonuc);
    }
}
