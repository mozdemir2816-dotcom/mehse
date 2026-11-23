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

// Ziyaretçi kartı QR doğrulaması + giriş öncesi İSG brifing & bilgi testi kilidi (N1).
Route::get('/ziyaretci/{token}', function (string $token) {
    $z = Ziyaretci::query()->with('firma')->where('token', $token)->first();

    return response()->view('ziyaretci-dogrula', ['z' => $z], $z ? 200 : 404);
})->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('ziyaretci.dogrula');

Route::post('/ziyaretci/{token}/test', function (Request $request, string $token) {
    $z = Ziyaretci::query()->where('token', $token)->firstOrFail();
    $cevaplar = (array) $request->input('cevaplar', []);
    $sorular = Ziyaretci::BRIFING_SORULARI;
    $dogru = 0;

    foreach ($sorular as $i => $s) {
        if (($cevaplar[$i] ?? null) === $s['dogru']) {
            $dogru++;
        }
    }

    $puan = (int) round(($dogru / max(1, count($sorular))) * 100);
    if ($puan < 67 || ! $request->boolean('kvkk_onay')) {
        return back()->with('test_hata', "Test puanınız: %{$puan}. Giriş onayı için tüm kuralları onaylayıp en az 2/3 soruyu doğru yanıtlamalısınız.");
    }

    $z->update([
        'isg_bilgilendirme' => true,
        'egitim_tamamlandi_at' => now(),
        'test_puani' => $puan,
        'test_cevaplari' => $cevaplar,
    ]);

    return back()->with('test_ok', "Tebrikler! İSG giriş brifingi ve bilgi testi (%{$puan}) başarıyla tamamlandı. Kartınız kapıda girişe açıldı.");
})->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:20,1')->name('ziyaretci.test');

// Uzaktan eğitim belgesi QR doğrulaması — denetçi giriş yapmadan okutur; kişisel veri maskelenir.
Route::get('/belge-dogrula/{kod}', function (string $kod) {
    $atama = \App\Models\EgitimAtamasi::query()->with(['calisan.firma', 'paket'])->where('dogrulama_kodu', $kod)->first();

    return response()->view('belge-dogrula', ['a' => $atama], $atama ? 200 : 404);
})->where('kod', '[A-Za-z0-9]{16,32}')->middleware('throttle:30,1')->name('belge.dogrula');

// QR operatör ekranı (12.11.2026) — makinedeki QR: talimat + günlük kontrol, giriş gerektirmez (PIN isteğe bağlı).
Route::prefix('ekipman/{anahtar}')->where(['anahtar' => '[A-Za-z0-9]{32}'])->controller(\App\Http\Controllers\EkipmanOperatorController::class)->group(function () {
    Route::get('/', 'goster')->middleware('throttle:60,1')->name('ekipman.operator');
    Route::post('/pin', 'pin')->middleware('throttle:20,1')->name('ekipman.operator.pin');
    Route::post('/kontrol', 'kontrol')->middleware('throttle:10,1')->name('ekipman.operator.kontrol');
    Route::get('/talimat.pdf', 'talimatPdf')->middleware('throttle:20,1')->name('ekipman.operator.talimat');
});

// Canlı Çalışan İmzası (Tablette / QR İmzalat — C15): giriş gerektirmeyen imza pad + QR doğrulama + mühürlü PDF.
Route::get('/imza/{token}', [\App\Http\Controllers\CanliImzaController::class, 'goster'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('canli-imza.form');
Route::post('/imza/{token}', [\App\Http\Controllers\CanliImzaController::class, 'kaydet'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:20,1')->name('canli-imza.kaydet');
Route::get('/imza-dogrula/{kod}', [\App\Http\Controllers\CanliImzaController::class, 'dogrula'])
    ->where('kod', '[A-Za-z0-9]{12,32}')->middleware('throttle:60,1')->name('canli-imza.dogrula');
Route::get('/mehse/canli-imza/{id}/pdf', [\App\Http\Controllers\CanliImzaController::class, 'pdf'])
    ->whereNumber('id')->middleware('auth')->name('canli-imza.pdf');

// Çalışanlar için giriş gerektirmeyen QR kod ile Saha Risk / Ramak Kala Bildirimi (C16).
Route::get('/risk-bildir/{firma}/{imza}', [\App\Http\Controllers\QrRiskBildirimController::class, 'goster'])
    ->whereNumber('firma')->where('imza', '[a-f0-9]{20}')->middleware('throttle:60,1')->name('risk.bildir');
Route::post('/risk-bildir/{firma}/{imza}', [\App\Http\Controllers\QrRiskBildirimController::class, 'kaydet'])
    ->whereNumber('firma')->where('imza', '[a-f0-9]{20}')->middleware('throttle:10,1')->name('risk.bildir.kaydet');

// Taşeron Self-Servis Evrak & Başvuru Portalı — giriş gerektirmeden taşeron evrak yükler (K2).
Route::get('/taseron-basvuru/{token}', [\App\Http\Controllers\TaseronBasvuruController::class, 'goster'])
    ->where('token', '[A-Za-z0-9]{20,48}')->middleware('throttle:60,1')->name('taseron.basvuru');
Route::post('/taseron-basvuru/{token}', [\App\Http\Controllers\TaseronBasvuruController::class, 'kaydet'])
    ->where('token', '[A-Za-z0-9]{20,48}')->middleware('throttle:20,1')->name('taseron.basvuru.kaydet');

// Müfettiş / Denetçi için süreli, salt-okunur ve erişim loglu teftiş ekranı (C21).
Route::get('/teftis/{token}', [\App\Http\Controllers\MufettisTeftisController::class, 'goster'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('mufettis.teftis');

// Canlı (Senkron) Eğitim Oturumu sınav ve >=70 puan QR sertifika (E5).
Route::get('/canli-egitim/{token}', [\App\Http\Controllers\CanliEgitimController::class, 'goster'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('canli-egitim.form');
Route::post('/canli-egitim/{token}', [\App\Http\Controllers\CanliEgitimController::class, 'sinavGonder'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:20,1')->name('canli-egitim.gonder');
Route::get('/canli-egitim/{token}/sertifika/{kod}', [\App\Http\Controllers\CanliEgitimController::class, 'sertifika'])
    ->where('token', '[A-Za-z0-9]{20,64}')->where('kod', '[A-Za-z0-9]{8,32}')->middleware('throttle:60,1')->name('canli-egitim.sertifika');

// OSGB opsiyonel canlı saha konumu paylaşımı (opt-in heartbeat — E9).
Route::post('/mehse/konum-bildir', function (Request $request) {
    $veri = $request->validate([
        'aktif' => ['required', 'boolean'],
        'enlem' => ['nullable', 'numeric', 'between:-90,90'],
        'boylam' => ['nullable', 'numeric', 'between:-180,180'],
    ]);

    /** @var User $user */
    $user = $request->user();
    KullaniciAyarlari::kaydet($user, [
        'canli_konum' => [
            'aktif' => (bool) $veri['aktif'],
            'enlem' => $veri['aktif'] ? ($veri['enlem'] ?? null) : null,
            'boylam' => $veri['aktif'] ? ($veri['boylam'] ?? null) : null,
            'guncelleme' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i'),
        ],
    ]);

    return response()->json(['ok' => true]);
})->middleware(['auth', 'throttle:30,1'])->name('mehse.konum.bildir');

// Kök adres = herkese açık tanıtım sayfası (29.10.2026); üye olmayanlar teklif isteyebilir,
// panel /admin'de. Teklif formu kayıt açar (TeklifTalebi), sahip hesap panelde görür.
Route::view('/', 'tanitim')->name('tanitim');
// url() alt klasörde (localhost/mehse) de doğru adresi üretir; düz '/admin' klasör adını düşürüyordu.
Route::get('/tanitim', fn () => redirect(url('/'), 301));
Route::view('/kvkk', 'pages.kvkk')->name('kvkk');
Route::view('/cerez-politikasi', 'pages.cerez-politikasi')->name('cerez');
Route::view('/bilgi-guvenligi', 'pages.bilgi-guvenligi')->name('bilgi-guvenligi');
Route::view('/android', 'pages.android')->name('android');
Route::view('/fiyatlandirma', 'pages.fiyatlandirma')->name('fiyatlandirma');
Route::get('/demo', [\App\Http\Controllers\UcretsizAraclarController::class, 'canliDemo'])->name('demo');
Route::get('/araclar/tehlike-sinifi-sorgulama', [\App\Http\Controllers\UcretsizAraclarController::class, 'tehlikeSinifi'])->name('araclar.tehlike');
Route::get('/araclar/kaza-siklik-orani', [\App\Http\Controllers\UcretsizAraclarController::class, 'kazaSiklikOrani'])->name('araclar.kso');
Route::get('/araclar/isg-sure-hesaplama', [\App\Http\Controllers\UcretsizAraclarController::class, 'isgSureHesaplama'])->name('araclar.sure');
Route::get('/araclar/ipc-ceza-hesaplama', [\App\Http\Controllers\UcretsizAraclarController::class, 'ipcCezaHesaplama'])->name('araclar.ipc');
Route::get('/araclar/ilkyardimci-destek-elemani-hesaplama', [\App\Http\Controllers\UcretsizAraclarController::class, 'ilkyardimciDestekHesaplama'])->name('araclar.ekip');
Route::get('/araclar/fine-kinney-risk-hesaplama', [\App\Http\Controllers\UcretsizAraclarController::class, 'fineKinneyRiskHesaplama'])->name('araclar.risk');
Route::get('/sektor/{slug}', [\App\Http\Controllers\SektorController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('sektor.show');
// Arama motorları için site haritası (public/robots.txt bunu gösterir). lastmod = sayfa şablonunun son değişikliği.
Route::get('/sitemap.xml', function () {
    $tarih = fn (string $gorunum): string => date('Y-m-d', filemtime(resource_path("views/{$gorunum}.blade.php")));

    return response()->view('sitemap', [
        'sayfalar' => [
            ['adres' => 'https://mehse.com/', 'lastmod' => $tarih('tanitim'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['adres' => 'https://mehse.com/fiyatlandirma', 'lastmod' => $tarih('pages/fiyatlandirma'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['adres' => 'https://mehse.com/demo', 'lastmod' => $tarih('pages/demo'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['adres' => 'https://mehse.com/araclar/tehlike-sinifi-sorgulama', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/araclar/isg-sure-hesaplama', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/araclar/kaza-siklik-orani', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/araclar/ipc-ceza-hesaplama', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/araclar/ilkyardimci-destek-elemani-hesaplama', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/araclar/fine-kinney-risk-hesaplama', 'lastmod' => $tarih('pages/ucretsiz-araclar'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['adres' => 'https://mehse.com/kvkk', 'lastmod' => $tarih('yasal/kvkk'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['adres' => 'https://mehse.com/cerez-politikasi', 'lastmod' => $tarih('pages/cerez-politikasi'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['adres' => 'https://mehse.com/bilgi-guvenligi', 'lastmod' => $tarih('pages/bilgi-guvenligi'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['adres' => 'https://mehse.com/android', 'lastmod' => $tarih('pages/android'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            // Sektör sayfaları (config/sektorler.php); içerik config'te olduğundan lastmod = config dosyası tarihi.
            ...collect(array_keys(config('sektorler', [])))->map(fn (string $slug) => [
                'adres' => 'https://mehse.com/sektor/'.$slug,
                'lastmod' => date('Y-m-d', max(filemtime(config_path('sektorler.php')), filemtime(resource_path('views/pages/sektor_detay.blade.php')))),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ])->all(),
        ],
    ])->header('Content-Type', 'application/xml; charset=utf-8');
})->name('sitemap');
// İşverene WhatsApp ile gönderilen rapor PDF'i — yalnız imzalı, süreli bağlantıyla (App\Support\IsverenWhatsApp).
Route::get('/paylasim/{tur}/{id}', \App\Http\Controllers\PaylasimPdfController::class)
    ->whereIn('tur', array_keys(\App\Support\IsverenWhatsApp::TURLER))->whereNumber('id')
    ->middleware(['signed', 'throttle:30,1'])->name('paylasim.pdf');

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

// Üst bar "Hızlı Notlarım" çekmecesi (users.ayarlar['notlarim']).
Route::middleware('auth')->prefix('/mehse/notlar')->group(function () {
    Route::get('/', function (Request $request) {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'notlar' => \App\Support\UzmanNotlari::liste($user),
            'firmalar' => \App\Models\Firma::where('user_id', $user->id)->where('aktif', true)->orderBy('unvan')->get(['id', 'unvan']),
        ]);
    })->name('mehse.notlar.liste');

    Route::post('/', function (Request $request) {
        $veri = $request->validate([
            'metin' => ['required', 'string', 'max:500'],
            'etiket' => ['nullable', 'string', Rule::in(array_keys(\App\Support\UzmanNotlari::ETIKETLER))],
            'firma_id' => ['nullable', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'notlar' => \App\Support\UzmanNotlari::ekle($user, $veri['metin'], $veri['etiket'] ?? 'genel', $veri['firma_id'] ?? null),
        ]);
    })->name('mehse.notlar.ekle');

    Route::post('/{id}/tamamla', function (string $id, Request $request) {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['notlar' => \App\Support\UzmanNotlari::tamamlaToggle($user, $id)]);
    })->name('mehse.notlar.tamamla');

    Route::delete('/{id}', function (string $id, Request $request) {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['notlar' => \App\Support\UzmanNotlari::sil($user, $id)]);
    })->name('mehse.notlar.sil');
});

// Sağ alt yüzen İSG Asistanı — önce canlı portföy verisini (K3), değilse 6331 mevzuat danışmanını yanıtlar.
Route::post('/mehse/mevzuat-sor', function (Request $request) {
    $veri = $request->validate(['soru' => ['required', 'string', 'max:500']]);
    $user = $request->user();

    if ($user instanceof User) {
        $canli = \App\Support\CanliPortfoyAsistani::sor($user, $veri['soru']);
        if ($canli !== null) {
            return response()->json($canli);
        }
    }

    return response()->json(\App\Support\GeminiMevzuatDanismani::sor($veri['soru']));
})->middleware(['auth', 'throttle:30,1'])->name('mehse.mevzuat.sor');

// Paylaşımlı sunucuda public_html/storage sembolik bağlantısı eksik olduğunda public diskindeki
// saha/DÖF fotoğrafları ve görsellerin 404 vermemesi için oturum + kiracı korumalı güvenli akış.
Route::get('/storage/{yol}', function (string $yol) {
    $temiz = ltrim($yol, '/');
    abort_if(
        $temiz === ''
        || str_contains($temiz, '..')
        || str_contains($temiz, "\0")
        || preg_match('#(^|/)\.#', $temiz),
        404,
    );

    $uzanti = strtolower(pathinfo($temiz, PATHINFO_EXTENSION));
    abort_unless(in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf'], true), 404);

    // Kiracı (multi-tenant) sahiplik doğrulaması: hassas firma/kullanıcı klasörleri yalnız yetkili hesaba açılır.
    $webUser = auth('web')->user();
    $isyeriFirma = auth('isyeri')->user();
    $calisan = auth('calisan')->user();
    $sahip = $webUser instanceof User && $webUser->sahipMi();

    $firmaErisimliMi = function (int $firmaId) use ($webUser, $isyeriFirma, $calisan, $sahip): bool {
        if ($sahip) {
            return true;
        }
        if ($webUser instanceof User) {
            return $webUser->erisilebilirFirmaIdleri()->map(fn ($id) => (int) $id)->contains($firmaId);
        }
        if ($isyeriFirma instanceof \App\Models\Firma) {
            return (int) $isyeriFirma->id === $firmaId;
        }
        if ($calisan instanceof \App\Models\Calisan) {
            return (int) $calisan->firma_id === $firmaId;
        }

        return false;
    };

    if (! $sahip) {
        // Firma ID bazlı klasörler: arsiv/{firmaId}/..., sds/{firmaId}/..., taseron/{firmaId}/..., pkd/{firmaId}/..., periyodik-kontrol/{firmaId}/...
        if (preg_match('#^(arsiv|sds|taseron|pkd|periyodik-kontrol)/(\d+)/#', $temiz, $m)) {
            abort_unless($firmaErisimliMi((int) $m[2]), 403);
        }

        // Kullanıcı ID bazlı klasörler: arsiv/gecici/{userId}/..., toolbox-konusmalari/{userId}/..., afis/{userId}/...
        if (preg_match('#^(arsiv/gecici|toolbox-konusmalari|afis)/(\d+)/#', $temiz, $m)) {
            abort_unless($webUser instanceof User && (int) $webUser->id === (int) $m[2], 403);
        }

        // Yalnız yetkili panel kullanıcılarına (çalışan/işyeri portalına kapalı) özel hassas klasörler
        if (preg_match('#^(uzman-imza|isveren-imza|taseron-basvuru|canli-imzalar|isg-katip)/#', $temiz)) {
            abort_unless($webUser instanceof User, 403);

            if (str_starts_with($temiz, 'uzman-imza/') && $webUser->imza_yolu !== $temiz && $webUser->kase_yolu !== $temiz) {
                $profSahip = \App\Models\IsgProfesyoneli::where('user_id', $webUser->id)
                    ->where(fn ($q) => $q->where('imza_yolu', $temiz)->orWhere('kase_yolu', $temiz))
                    ->exists();
                abort_unless($profSahip, 403);
            }
        }
    }

    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    abort_unless($disk->exists($temiz), 404);

    $basliklar = [
        'Cache-Control' => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
    ];

    // SVG inline script/XSS vektörünü tamamen etkisizleştir.
    if ($uzanti === 'svg') {
        $basliklar['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
        $basliklar['Content-Disposition'] = 'attachment; filename="'.basename($temiz).'"';
    }

    return response()->file($disk->path($temiz), $basliklar);
})->where('yol', '.*')->middleware('auth:web,isyeri,calisan')->name('mehse.storage.dosya');
