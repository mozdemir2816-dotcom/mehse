<?php

namespace App\Http\Controllers;

use App\Mail\YeniTeklifTalebi;
use App\Models\TeklifTalebi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/** Herkese açık tanıtım sayfasının teklif formu. */
class TeklifTalebiController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Gizli "web" alanını yalnız botlar doldurur; sessizce başarılı gibi dön.
        if ($request->filled('web')) {
            return redirect(url('/').'#teklif')->with('teklif_gonderildi', true);
        }

        $veri = $request->validate([
            'firma' => ['required', 'string', 'max:150'],
            'kisi' => ['required', 'string', 'max:100'],
            'telefon' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\s\-]{10,30}$/'],
            'calisan' => ['required', 'integer', 'min:1', 'max:100000'],
            'sinif' => ['required', Rule::in(TeklifTalebi::TEHLIKE_SINIFLARI)],
            'kvkk' => ['accepted'],
        ], [
            'telefon.regex' => 'Telefon numarasını rakamlarla yazın (ör. 0506 000 00 00).',
            'kvkk.accepted' => 'Devam etmek için onay kutusunu işaretleyin.',
        ], [
            'firma' => 'firma adı', 'kisi' => 'iletişim kişisi', 'calisan' => 'çalışan sayısı', 'sinif' => 'tehlike sınıfı',
        ]);

        $talep = TeklifTalebi::create([
            'firma_adi' => $veri['firma'],
            'kisi' => $veri['kisi'],
            'telefon' => $veri['telefon'],
            'calisan_sayisi' => $veri['calisan'],
            'tehlike_sinifi' => $veri['sinif'],
            'ip' => $request->ip(),
            'tarayici' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $this->bildir($talep);

        return redirect(url('/').'#teklif')->with('teklif_gonderildi', true);
    }

    /** E-posta gitmese de talep kayıtlıdır; ziyaretçi hata görmez, sorun log'a yazılır. */
    private function bildir(TeklifTalebi $talep): void
    {
        $alici = config('isg.teklif_bildirim_eposta');

        if (! $alici) {
            return;
        }

        try {
            Mail::to($alici)->send(new YeniTeklifTalebi($talep));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
