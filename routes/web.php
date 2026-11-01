<?php

use App\Http\Controllers\TeklifTalebiController;
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

// Kök adres = herkese açık tanıtım sayfası (29.10.2026); üye olmayanlar teklif isteyebilir,
// panel /admin'de. Teklif formu kayıt açar (TeklifTalebi), sahip hesap panelde görür.
Route::view('/', 'tanitim')->name('tanitim');
// url() alt klasörde (localhost/mehse) de doğru adresi üretir; düz '/admin' klasör adını düşürüyordu.
Route::get('/tanitim', fn () => redirect(url('/'), 301));
Route::view('/kvkk', 'pages.kvkk')->name('kvkk');
Route::view('/cerez-politikasi', 'pages.cerez-politikasi')->name('cerez');
// Arama motorları için site haritası (public/robots.txt bunu gösterir). lastmod = sayfa şablonunun son değişikliği.
Route::get('/sitemap.xml', function () {
    $tarih = fn (string $gorunum): string => date('Y-m-d', filemtime(resource_path("views/{$gorunum}.blade.php")));

    return response()->view('sitemap', [
        'sayfalar' => [
            ['adres' => 'https://mehse.com/', 'lastmod' => $tarih('tanitim'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['adres' => 'https://mehse.com/kvkk', 'lastmod' => $tarih('yasal/kvkk'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['adres' => 'https://mehse.com/cerez-politikasi', 'lastmod' => $tarih('pages/cerez-politikasi'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ],
    ])->header('Content-Type', 'application/xml; charset=utf-8');
})->name('sitemap');
Route::post('/teklif-talebi', TeklifTalebiController::class)->middleware('throttle:5,60')->name('tanitim.teklif');

// Yıllık Planlar tek sayfadan üç ayrı sayfaya bölündü (01.10.2026) — eski yer imleri.
Route::redirect('/admin/yillik-planlar', '/admin/yillik-calisma-plani');

// "Saha Hızlı İşlem" (iş izni kapatma, ramak kala) Ziyaret Modu'nun içine alındı
// — saha kontrolleri 5. aşama, 08.10.2026. Eski bağlantı firma seçimiyle yönlenir.
Route::get('/admin/saha-hizli-islem', fn (Request $request) => redirect('/admin/ziyaret'.($request->integer('firma') ? '?firma='.$request->integer('firma') : '')));

// Profilim: uzmanın kaşe / imza görseli. Önizleme /storage bağlantısına (hosting'de eksik olabilir) ve APP_URL'e
// bağlı kalmasın diye dosya doğrudan akıtılır; yalnız oturum açmış kullanıcı kendi görselini görür.
Route::get('/mehse/ayar/uzman-gorsel/{tur}', function (Request $request, string $tur) {
    abort_unless(in_array($tur, ['kase', 'imza'], true), 404);

    $yol = $request->user()?->{$tur.'_gorseli'};
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    abort_unless($yol && $disk->exists($yol), 404);

    return response()->file($disk->path($yol), ['Cache-Control' => 'private, max-age=300']);
})->middleware('auth')->where('tur', 'kase|imza')->name('mehse.uzman-gorsel');

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
