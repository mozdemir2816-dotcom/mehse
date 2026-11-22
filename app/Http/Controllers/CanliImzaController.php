<?php

namespace App\Http\Controllers;

use App\Models\CanliImza;
use App\Support\CanliImzaYonetici;
use Illuminate\Http\Request;

class CanliImzaController extends Controller
{
    public function goster(string $token)
    {
        $imza = CanliImza::query()->with('firma')->where('imza_token', $token)->firstOrFail();

        return view('canli-imza-pad', [
            'imza' => $imza,
            'dogrulamaUrl' => $imza->dogrulama_kodu ? CanliImzaYonetici::dogrulamaAdresi($imza) : null,
        ]);
    }

    public function kaydet(string $token, Request $request)
    {
        $imza = CanliImza::query()->with('firma')->where('imza_token', $token)->firstOrFail();

        if ($imza->imzalandiMi()) {
            return redirect()->route('canli-imza.form', ['token' => $token])
                ->with('durum', 'Bu belge daha önce imzalanmış.');
        }

        $veri = $request->validate([
            'calisan_adi' => ['required', 'string', 'min:3', 'max:160'],
            'calisan_tc' => ['nullable', 'string', 'max:14'],
            'imza_veri' => ['required', 'string'],
            'onay' => ['accepted'],
        ], [
            'calisan_adi.required' => 'Lütfen adınızı ve soyadınızı yazın.',
            'imza_veri.required' => 'Lütfen imza alanına parmağınızla veya kalemle imza atın.',
            'onay.accepted' => 'Belgeyi okuyup anladığınızı onaylamanız gerekir.',
        ]);

        try {
            CanliImzaYonetici::imzaKaydet(
                $imza,
                $veri['calisan_adi'],
                $veri['calisan_tc'] ?? null,
                $veri['imza_veri'],
                $request,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['imza_veri' => $e->getMessage()])->withInput();
        }

        return redirect()->route('canli-imza.form', ['token' => $token])
            ->with('durum', 'İmzanız başarıyla kaydedildi ve doğrulama mührü oluşturuldu.');
    }

    public function dogrula(string $kod)
    {
        $imza = CanliImza::query()->with('firma')->where('dogrulama_kodu', $kod)->first();

        return response()->view('canli-imza-dogrula', ['imza' => $imza], $imza ? 200 : 404);
    }

    public function pdf(int $id, Request $request)
    {
        $imza = CanliImza::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return CanliImzaYonetici::muhurluPdf($imza);
    }
}
