<?php

use App\Models\User;
use App\Support\KullaniciAyarlari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Kök adres doğrudan panele yönlensin (stok Laravel karşılama sayfası yerine).
Route::redirect('/', '/admin');

// Topbar tema seçicisi (filament.components.tema-secici) seçimi hesaba kaydeder;
// böylece tema her cihazda aynı gelir. Ayarlar sayfası aynı alanı yazar.
Route::post('/mehse/ayar/tema', function (Request $request) {
    $veri = $request->validate([
        'tema' => ['required', Rule::in(array_keys(KullaniciAyarlari::TEMALAR))],
    ]);

    /** @var User $user */
    $user = $request->user();
    KullaniciAyarlari::kaydet($user, ['tema' => $veri['tema']]);

    return response()->noContent();
})->middleware('auth')->name('mehse.ayar.tema');
