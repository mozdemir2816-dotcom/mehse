<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiyatlandırma ve Paketler | Mehse İSG</title>
    <meta name="description" content="Bireysel İSG uzmanları ve OSGB'ler için şeffaf fiyatlandırma. Kredi kartı gerektirmeden 7 gün ücretsiz deneyin.">
    <link rel="stylesheet" href="{{ asset('css/tanitim.css') }}">
    <style>
        .fiyat-kap { max-width: 1120px; margin: 0 auto; padding: 3rem 1.25rem 4rem; }
        .fiyat-baslik { text-align: center; max-width: 680px; margin: 0 auto 2.5rem; }
        .fiyat-baslik h1 { font-size: clamp(1.9rem, 3.6vw, 2.7rem); margin: .5rem 0; color: #0f172a; }
        .fiyat-izgara { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; }
        .paket { background: #fff; border: 1px solid #cbd5e1; border-radius: 18px; padding: 1.65rem; display: flex; flex-direction: column; gap: .9rem; position: relative; }
        .paket.populer { border: 2px solid #2563eb; box-shadow: 0 20px 40px -24px rgb(37 99 235 / .45); }
        .paket-rozet { position: absolute; top: -12px; right: 18px; background: #2563eb; color: #fff; font-size: .72rem; font-weight: 800; padding: .25rem .7rem; border-radius: 999px; }
        .tutar { font-size: 2rem; font-weight: 800; color: #0f172a; }
        .tutar small { font-size: .85rem; font-weight: 500; color: #64748b; }
        .ozellikler { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: .5rem; font-size: .88rem; color: #334155; flex: 1; }
        .ozellikler li::before { content: "✓ "; color: #16a34a; font-weight: 800; }
        .cta-btn { display: block; text-align: center; padding: .8rem 1rem; border-radius: 12px; font-weight: 700; text-decoration: none; }
        .cta-mavi { background: #2563eb; color: #fff; }
        .cta-gri { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; }
    </style>
</head>
<body style="background:#f8fafc">
<header style="background:#0f172a;color:#fff;padding:.9rem 1.25rem">
    <div style="max-width:1120px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-weight:800;font-size:1.1rem">MEHSE İSG</a>
        <nav style="display:flex;gap:1rem;align-items:center;font-size:.88rem;flex-wrap:wrap">
            <a href="{{ url('/') }}" style="color:#cbd5e1;text-decoration:none">Ana Sayfa</a>
            <a href="{{ route('araclar.tehlike') }}" style="color:#cbd5e1;text-decoration:none">Ücretsiz İSG Araçları</a>
            <a href="{{ url('/admin/login') }}" style="color:#fff;text-decoration:none;font-weight:700">Üye Girişi</a>
            <a href="{{ url('/admin/register') }}" style="background:#16a34a;color:#fff;padding:.45rem .9rem;border-radius:8px;text-decoration:none;font-weight:700">7 Gün Ücretsiz Başla</a>
        </nav>
    </div>
</header>

<main class="fiyat-kap">
    <div class="fiyat-baslik">
        <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:.76rem;font-weight:800;padding:.3rem .8rem;border-radius:999px">KREDİ KARTI İSTENMEZ · OTOMATİK ÇEKİM YOK</span>
        <h1>Şeffaf, Sürprizsiz İSG Yazılım Paketleri</h1>
        <p style="color:#475569;font-size:.98rem">7 gün boyunca tüm modülleri kredi kartı girmeden test edin. Yıllık ödemelerde 2 ay hediye ve 12 ay fiyat garantisi.</p>
    </div>

    <div class="fiyat-izgara">
        <article class="paket">
            <div>
                <h2 style="margin:0;font-size:1.25rem">Ücretsiz Başlangıç</h2>
                <p style="color:#64748b;font-size:.86rem;margin:.3rem 0 0">Sistemi risksiz test etmek isteyen uzmanlar için</p>
            </div>
            <div class="tutar">0 ₺ <small>/ 7 gün tüm modüller</small></div>
            <ul class="ozellikler">
                <li>Kredi kartı gerekmez, anında aktif</li>
                <li>Risk Değerlendirme (5x5 &amp; Fine-Kinney + AI)</li>
                <li>Saha Bulguları, DÖF &amp; QR Risk Bildirimi</li>
                <li>Eğitim Katılım, KKD, İş İzni, EK-2 &amp; EK-1</li>
                <li>Acil Durum Planı, 2D/3D Kroki &amp; ISO 7010 Lejant</li>
            </ul>
            <a href="{{ url('/admin/register') }}" class="cta-btn cta-gri">7 Gün Ücretsiz Başla</a>
        </article>

        <article class="paket populer">
            <span class="paket-rozet">EN POPÜLER</span>
            <div>
                <h2 style="margin:0;font-size:1.25rem">Bireysel Uzman</h2>
                <p style="color:#64748b;font-size:.86rem;margin:.3rem 0 0">Kendi portföyünü yöneten İGU ve İşyeri Hekimleri</p>
            </div>
            <div class="tutar">289 ₺ <small>/ ay (KDV dahil · Yıllık 2.890 ₺)</small></div>
            <ul class="ozellikler">
                <li>Sınırsız firma, çalışan ve filigransız PDF/Word/ZIP</li>
                <li>Tablette / QR Canlı İmza &amp; QR Doğrulamalı PDF</li>
                <li>SGK İşe Giriş PDF'inden Otomatik Çalışan Aktarımı</li>
                <li>Harita Tabanlı Ziyaret Rota Planlama</li>
                <li>RG 02.04.2026 Uyumlu Uzaktan Eğitim Portalı</li>
                <li>Müfettiş Salt-Okunur Teftiş Linki &amp; QR</li>
                <li>WhatsApp + SMS Bildirim Kısayolları</li>
            </ul>
            <a href="{{ url('/admin/register') }}" class="cta-btn cta-mavi">Uzman Paketini 7 Gün Ücretsiz Dene</a>
        </article>

        <article class="paket">
            <div>
                <h2 style="margin:0;font-size:1.25rem">OSGB Kurumsal</h2>
                <p style="color:#64748b;font-size:.86rem;margin:.3rem 0 0">Çoklu uzman, şube ve müşteri portalı yöneten OSGB'ler</p>
            </div>
            <div class="tutar">399 ₺ <small>/ ay (KDV dahil · Yıllık 3.990 ₺)</small></div>
            <ul class="ozellikler">
                <li>Uzman paketindeki tüm modüller sınırsız</li>
                <li>OSGB Cari, Tahsilat, PDF Ekstre &amp; Personel Hakediş</li>
                <li>Çoklu Şube Yönetimi &amp; Rol Bazlı Ekip Yetkilendirme</li>
                <li>İşveren / Müşteri Self-Servis Portalı (`/isyeri`)</li>
                <li>İSG-KATİP Robot &amp; Toplu Sözleşme Yönetimi</li>
                <li>Arşiv Toplu Bulut Yedeği (Drive / OneDrive uyumlu ZIP)</li>
                <li>OSGB'lere özel 90 gün deneme başvurusu</li>
            </ul>
            <a href="{{ url('/admin/osgb-basvuru') }}" class="cta-btn cta-gri">OSGB Kurumsal Başvuru</a>
        </article>
    </div>

    <div style="text-align:center;margin-top:2.5rem;color:#475569;font-size:.9rem">
        Sorularınız mı var? <a href="mailto:info@mehse.com" style="color:#2563eb;font-weight:700">info@mehse.com</a> veya
        <a href="tel:+905064754855" style="color:#2563eb;font-weight:700">0506 475 48 55</a> üzerinden bize ulaşın.
    </div>
</main>
</body>
</html>
