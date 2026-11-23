<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mehse İSG Android Uygulaması (APK) İndir</title>
    <meta name="description" content="Mehse İSG Android uygulamasını (APK) telefonunuza indirin: sahada fotoğraflı bulgu/DÖF, GPS ziyaret kanıtı, QR canlı imza, sesli komut ve PDF indirme.">
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f8fafc; color:#0f172a; line-height:1.6; }
        .ust { background:#0A345A; color:#fff; padding:.9rem 1.25rem; }
        .kap { max-width:860px; margin:0 auto; padding:2rem 1.25rem 4rem; }
        .hero { background:linear-gradient(135deg,#0A345A,#1e40af); color:#fff; border-radius:18px; padding:2rem 1.75rem; display:flex; align-items:center; justify-content:space-between; gap:1.5rem; flex-wrap:wrap; }
        .kart { background:#fff; border:1px solid #cbd5e1; border-radius:14px; padding:1.35rem; margin-top:1.15rem; }
        .btn { display:inline-block; padding:.75rem 1.35rem; border-radius:10px; background:#16a34a; color:#fff; font-weight:800; text-decoration:none; font-size:.95rem; }
        .btn-acik { background:#fff; color:#0A345A; }
        .izgara { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.85rem; margin-top:1rem; }
        .oz { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:.95rem; font-size:.86rem; }
    </style>
</head>
<body>
<header class="ust">
    <div style="max-width:860px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-weight:800;font-size:1.1rem">MEHSE İSG</a>
        <nav style="display:flex;gap:1rem;align-items:center;font-size:.86rem;flex-wrap:wrap">
            <a href="{{ url('/') }}" style="color:#cbd5e1;text-decoration:none">Ana Sayfa</a>
            <a href="{{ route('demo') }}" style="color:#93c5fd;text-decoration:none;font-weight:700">Canlı Demo</a>
            <a href="{{ url('/admin') }}" style="color:#fff;text-decoration:none;font-weight:700">Panele Git →</a>
        </nav>
    </div>
</header>

<main class="kap">
    @php
        $yerelApkVar = file_exists(public_path('indir/mehse-isg.apk'));
        $indirmeLinki = $yerelApkVar
            ? asset('indir/mehse-isg.apk')
            : 'https://github.com/mozdemir2816-dotcom/mehse/releases/download/android-latest/mehse-isg.apk';
    @endphp
    <div class="hero">
        <div style="flex:1;min-width:260px">
            <span style="display:inline-block;padding:.2rem .65rem;border-radius:999px;background:rgb(255 255 255 / .18);font-size:.76rem;font-weight:700;margin-bottom:.6rem">Android 7.0+ · v1.0.1 · com.mehse.isg</span>
            <h1 style="margin:0 0 .5rem;font-size:1.7rem">Mehse İSG Android Uygulaması</h1>
            <p style="margin:0 0 1.2rem;opacity:.92;font-size:.93rem">Sahada kamerayla bulgu &amp; DÖF fotoğrafı çekin, GPS ile 4'lü ziyaret kanıtı alın, sesle sayfa açın ve PDF/Excel raporları doğrudan telefonunuza indirin.</p>
            <div style="display:flex;gap:.65rem;flex-wrap:wrap">
                <a href="{{ $indirmeLinki }}" class="btn">📥 Mehse İSG APK İndir (v1.0.1)</a>
                <a href="{{ url('/admin') }}" class="btn btn-acik">Web Panelden Aç</a>
            </div>
        </div>
        <img src="{{ asset('images/pwa/ikon-192.png') }}" alt="Mehse İSG Android" style="width:110px;height:110px;border-radius:24px;box-shadow:0 12px 30px rgb(0 0 0 / .28)">
    </div>

    <div class="kart">
        <h2 style="margin:0 0 .35rem;font-size:1.1rem">Android Uygulamasında Neler Var?</h2>
        <div class="izgara">
            <div class="oz"><strong>📷 Yerel Kamera &amp; Çoklu Fotoğraf</strong><br>Saha Bulguları, DÖF ve Saha Denetiminde doğrudan kamera ile fotoğraf çekip yükleyin.</div>
            <div class="oz"><strong>📍 GPS &amp; Harita Rota</strong><br>4'lü ziyaret kanıtı GPS doğrulaması ve Google Maps çoklu durak rota başlatma.</div>
            <div class="oz"><strong>📄 Doğrudan PDF / Excel / ZIP</strong><br>Risk değerlendirmesi, sertifika, protokol defteri ve özlük ZIP dosyaları telefonun İndirilenler klasörüne iner.</div>
            <div class="oz"><strong>⚡ Uygulama Kısayolları</strong><br>Uygulama ikonuna uzun basarak Hızlı Bulgu, Bugün (Ziyaret) ve Firmalar ekranına anında geçin.</div>
            <div class="oz"><strong>📲 WhatsApp &amp; Telefon</strong><br>İşveren WhatsApp hatırlatmaları ve aramalar doğrudan cihaz uygulamalarında açılır.</div>
            <div class="oz"><strong>🔐 Kalıcı Oturum</strong><br>Giriş oturumunuz saklanır; sahada her açılışta tekrar şifre yazmazsınız.</div>
        </div>
    </div>

    <div class="kart">
        <h2 style="margin:0 0 .45rem;font-size:1.05rem">Kurulum Adımları (30 Saniye)</h2>
        <ol style="margin:0;padding-left:1.2rem;font-size:.9rem;color:#334155">
            <li>Yukarıdaki <strong>Mehse İSG APK İndir</strong> butonuna dokunun ve <code>mehse-isg.apk</code> dosyasını indirin.</li>
            <li>İndirilen dosyaya dokunun; Android ilk kez sorarsa <strong>"Bu kaynaktan izin ver / Bilinmeyen uygulamaları yükle"</strong> seçeneğini onaylayın.</li>
            <li><strong>Yükle</strong> butonuna dokunun — Mehse İSG ikonu ana ekranınıza gelecektir.</li>
        </ol>
        <p style="margin:.85rem 0 0;font-size:.82rem;color:#64748b">
            Alternatif (APK yüklemeden): Android Chrome'da <a href="{{ url('/admin') }}" style="color:#2563eb;font-weight:700">mehse.com/admin</a> adresini açıp sağ üst menüden <strong>"Ana ekrana ekle / Uygulamayı yükle"</strong> seçeneğini de kullanabilirsiniz (tam ekran PWA).
        </p>
    </div>
</main>
</body>
</html>
