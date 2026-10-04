<?php

use App\Models\SahaAnalizi;
use App\Models\User;
use App\Models\Ziyaretci;
use App\Support\KullaniciAyarlari;
use App\Support\SahaAnaliziUretici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Ziyaretçi kartı QR doğrulaması — güvenlik görevlisi giriş yapmadan okutur.
Route::get('/ziyaretci/{token}', function (string $token) {
    $z = Ziyaretci::query()->with('firma')->where('token', $token)->first();

    return response()->view('ziyaretci-dogrula', ['z' => $z], $z ? 200 : 404);
})->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('ziyaretci.dogrula');

// Kök adres doğrudan panele yönlensin (stok Laravel karşılama sayfası yerine).
Route::redirect('/', '/admin');

// Yıllık Planlar tek sayfadan üç ayrı sayfaya bölündü (01.10.2026) — eski yer imleri.
Route::redirect('/admin/yillik-planlar', '/admin/yillik-calisma-plani');

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

// Saha Gözlem Raporu PDF'i — telefonda "PDF'i Paylaş" (Web Share API ile dosya olarak
// WhatsApp'a gönderilir). Yalnız raporun sahibi uzman indirebilir.
Route::get('/mehse/saha-gozlem/{rapor}/pdf', function (int $rapor, Request $request) {
    $s = SahaAnalizi::query()
        ->whereHas('firma', fn ($q) => $q->where('user_id', $request->user()->id))
        ->findOrFail($rapor);

    return SahaAnaliziUretici::pdf($s);
})->middleware('auth')->name('mehse.saha-gozlem.pdf');
