<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bilgi Güvenliği, KVKK ve Veri Koruma Politikası | Mehse İSG</title>
    <meta name="description" content="Mehse İSG veri güvenliği mimarisi: KVKK uyumlu kişisel/sağlık verisi izolasyonu, iki adımlı doğrulama (MFA), denetim kayıtları (Audit Log) ve düzenli yedekleme.">
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f8fafc; color:#0f172a; line-height:1.6; }
        .ust { background:#0f172a; color:#fff; padding:.9rem 1.25rem; }
        .kap { max-width:960px; margin:0 auto; padding:2rem 1.25rem 4rem; }
        .izgara { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1rem; margin:1.25rem 0; }
        .kart { background:#fff; border:1px solid #cbd5e1; border-radius:14px; padding:1.25rem; }
        .kart h3 { margin:0 0 .4rem; font-size:1.02rem; color:#1e3a8a; }
        .kart p { margin:0; font-size:.87rem; color:#475569; }
        .btn { display:inline-block; padding:.6rem 1.15rem; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; text-decoration:none; font-size:.88rem; }
    </style>
</head>
<body>
<header class="ust">
    <div style="max-width:960px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-weight:800;font-size:1.1rem">MEHSE İSG</a>
        <nav style="display:flex;gap:1rem;align-items:center;font-size:.86rem;flex-wrap:wrap">
            <a href="{{ url('/') }}" style="color:#cbd5e1;text-decoration:none">Ana Sayfa</a>
            <a href="{{ route('demo') }}" style="color:#93c5fd;text-decoration:none;font-weight:700">Canlı Demo</a>
            <a href="{{ route('fiyatlandirma') }}" style="color:#cbd5e1;text-decoration:none">Fiyatlandırma</a>
            <a href="{{ url('/admin/register') }}" class="btn" style="background:#16a34a;padding:.45rem .85rem">7 Gün Ücretsiz Dene</a>
        </nav>
    </div>
</header>

<main class="kap">
    <h1 style="margin:0 0 .5rem;font-size:1.7rem">Bilgi Güvenliği, KVKK ve Veri Koruma Mimarisi</h1>
    <p style="margin:0 0 1.4rem;color:#475569;font-size:.95rem">
        Mehse İSG; OSGB'ler, İSG profesyonelleri ve kurumsal işletmelerin kişisel ve özel nitelikli sağlık verilerini 6698 sayılı KVKK ve modern bilgi güvenliği standartlarına uygun biçimde korur.
    </p>

    <div class="izgara">
        <div class="kart">
            <h3>🔒 Rol &amp; Firma Bazlı Veri İzolasyonu</h3>
            <p>Her OSGB, uzman, işyeri hesabı ve çalışan portalı kendi yetki sınırında izole çalışır. T.C. kimlik numaraları listelerde otomatik maskelenir (<code>123******90</code>).</p>
        </div>
        <div class="kart">
            <h3>🛡️ İki Adımlı Doğrulama (2FA / MFA)</h3>
            <p>Tüm yönetici ve uzman hesaplarında TOTP tabanlı iki adımlı doğrulama, kurtarma kodları ve aktif oturumları uzaktan sonlandırma (tüm cihazlardan çıkış) yerleşiktir.</p>
        </div>
        <div class="kart">
            <h3>📋 Denetim Kayıtları (Audit Log)</h3>
            <p>Firma, risk değerlendirmesi, DÖF, periyodik kontrol ve teftiş paylaşımı işlemleri kullanıcı, zaman damgası ve IP bilgisiyle değiştirilemez denetim günlüğüne işlenir.</p>
        </div>
        <div class="kart">
            <h3>🔏 Değişmez Sürüm Kilidi &amp; KVKK Blur</h3>
            <p>İmzalanmış risk değerlendirmeleri kilitlenerek geriye dönük sessiz değişiklik engellenir; saha fotoğrafları için yerleşik KVKK yüz/plaka bulanıklaştırma aracı sunulur.</p>
        </div>
        <div class="kart">
            <h3>☁️ Düzenli Yedekleme &amp; Bulut Arşiv</h3>
            <p>Veriler SSL/TLS şifreli aktarımla saklanır; kullanıcılar istedikleri an tek tıkla klasörlü arşiv ve özlük <code>.zip</code> yedeklerini indirebilirler.</p>
        </div>
        <div class="kart">
            <h3>🩺 Sağlık Verisi Gizliliği</h3>
            <p>İşyeri hekimi ve revir/muayene kayıtları genel özetlerde yalnızca sayı olarak gösterilir; tıbbi detaylar yetkisiz kullanıcı ve işveren görünümüne kapatılır.</p>
        </div>
    </div>

    <div class="kart" style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <div>
            <strong>Hukuki Belgeler &amp; İletişim</strong>
            <div style="font-size:.85rem;color:#475569;margin-top:.2rem">
                <a href="{{ route('kvkk') }}" style="color:#2563eb">KVKK Aydınlatma Metni</a> ·
                <a href="{{ route('cerez') }}" style="color:#2563eb">Çerez Politikası</a> ·
                Güvenlik bildirimleri: <a href="mailto:info@mehse.com" style="color:#2563eb">info@mehse.com</a>
            </div>
        </div>
        <a href="{{ route('demo') }}" class="btn">Canlı Demo'yu İncele →</a>
    </div>
</main>
</body>
</html>
