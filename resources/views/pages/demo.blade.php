<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Canlı OSGB Demo Turu | Mehse İSG</title>
    <meta name="description" content="Mehse İSG panelini kayıt olmadan inceleyin: OSGB Kadro Panosu, Sanal Müfettiş 6331 İPC Simülatörü, Kapalı Devre DÖF, Canlı QR İmza ve 4'lü Ziyaret Kanıtı.">
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f1f5f9; color:#0f172a; }
        .ust { background:#0f172a; color:#fff; padding:.85rem 1.25rem; position:sticky; top:0; z-index:20; }
        .kap { max-width:1120px; margin:0 auto; padding:1.5rem 1.25rem 4rem; }
        .banner { background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; font-size:.86rem; }
        .kpi { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:.85rem; margin-bottom:1.1rem; }
        .kart { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.1rem 1.2rem; box-shadow:0 1px 2px rgb(15 23 42 / .04); margin-bottom:1rem; }
        .sekmeler { display:flex; gap:.45rem; flex-wrap:wrap; margin-bottom:1rem; }
        .sekme { padding:.5rem .95rem; border-radius:999px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:.84rem; font-weight:700; cursor:pointer; }
        .sekme.aktif { background:#2563eb; color:#fff; border-color:#2563eb; }
        .gizli { display:none; }
        .btn { display:inline-block; padding:.55rem 1.05rem; border-radius:10px; background:#16a34a; color:#fff; font-weight:700; text-decoration:none; font-size:.85rem; }
        table { width:100%; border-collapse:collapse; font-size:.83rem; }
        th, td { text-align:left; padding:.55rem .5rem; border-bottom:1px solid #e2e8f0; }
        .rozet { display:inline-block; padding:.1rem .5rem; border-radius:999px; font-size:.72rem; font-weight:700; }
    </style>
</head>
<body>
<header class="ust">
    <div style="max-width:1120px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:.6rem">
            <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-weight:800;font-size:1.08rem">MEHSE İSG</a>
            <span class="rozet" style="background:#2563eb;color:#fff">CANLI DEMO OSGB (SALT-OKUNUR)</span>
        </div>
        <nav style="display:flex;gap:.9rem;align-items:center;font-size:.84rem;flex-wrap:wrap">
            <a href="{{ route('araclar.tehlike') }}" style="color:#cbd5e1;text-decoration:none">Ücretsiz Araçlar</a>
            <a href="{{ route('fiyatlandirma') }}" style="color:#cbd5e1;text-decoration:none">Fiyatlandırma</a>
            <a href="{{ url('/admin/register') }}" class="btn">7 Gün Ücretsiz Hesap Aç →</a>
        </nav>
    </div>
</header>

<main class="kap">
    <div class="banner">
        <div><strong>Örnek OSGB Çalışma Alanı (Anadolu Güven OSGB · 42 Aktif İşyeri · 1.280 Çalışan)</strong> — Gerçek veri içermez; tüm modülleri kayıt olmadan inceleyebilirsiniz.</div>
        <a href="{{ url('/admin/register') }}" class="btn" style="background:#2563eb">Kendi OSGB'ni 2 Dakikada Kur</a>
    </div>

    <div class="kpi">
        <div class="kart" style="margin:0;border-left:4px solid #2563eb"><div style="font-size:.75rem;color:#64748b;font-weight:600">Portföy Uyum Skoru</div><div style="font-size:1.6rem;font-weight:800;color:#1d4ed8">%88</div><div style="font-size:.73rem;color:#64748b">37 tam uyumlu · 5 aksiyon bekleyen</div></div>
        <div class="kart" style="margin:0;border-left:4px solid #059669"><div style="font-size:.75rem;color:#64748b;font-weight:600">Gereken İGU Kadrosu (FTE)</div><div style="font-size:1.6rem;font-weight:800;color:#059669">4,35 kişi</div><div style="font-size:.73rem;color:#64748b">46.980 dk/ay yasal süre</div></div>
        <div class="kart" style="margin:0;border-left:4px solid #dc2626"><div style="font-size:.75rem;color:#64748b;font-weight:600">Sanal Müfettiş 6331 Riski</div><div style="font-size:1.6rem;font-weight:800;color:#dc2626">194.200 ₺</div><div style="font-size:.73rem;color:#64748b">3 firmada kritik eksik tespit edildi</div></div>
        <div class="kart" style="margin:0;border-left:4px solid #d97706"><div style="font-size:.75rem;color:#64748b;font-weight:600">Onay Bekleyen DÖF Kanıtı</div><div style="font-size:1.6rem;font-weight:800;color:#b45309">6 kayıt</div><div style="font-size:.73rem;color:#64748b">İşveren portalından yüklendi</div></div>
    </div>

    <div class="sekmeler">
        <button type="button" class="sekme aktif" onclick="demoSekme('kadro', this)">👥 OSGB Kadro &amp; Karne</button>
        <button type="button" class="sekme" onclick="demoSekme('dof', this)">🔒 Kapalı Devre DÖF</button>
        <button type="button" class="sekme" onclick="demoSekme('mufettis', this)">⚖️ Sanal Müfettiş (İPC)</button>
        <button type="button" class="sekme" onclick="demoSekme('saha', this)">📍 4'lü Ziyaret Kanıtı &amp; QR</button>
    </div>

    <section id="panel-kadro" class="kart">
        <h2 style="margin:0 0 .3rem;font-size:1.05rem">OSGB Kadro Doluluk &amp; Aylık Uzman Karnesi (0–100)</h2>
        <p style="margin:0 0 .9rem;font-size:.82rem;color:#64748b">Belge sınıfı uyuşmazlığı, tam süreli kilit ve ihmal edilen firma takibi tek ekranda.</p>
        <table>
            <thead><tr><th>İSG Profesyoneli</th><th>Firma</th><th>Doluluk</th><th>Aylık Karne</th><th>Durum / Erken Uyarı</th></tr></thead>
            <tbody>
                <tr><td><strong>Mehmet Kaya</strong> (A Sınıfı İGU)</td><td>14</td><td><strong style="color:#059669">%76</strong> (137 sa)</td><td><strong style="color:#059669">94 / 100</strong></td><td><span class="rozet" style="background:#dcfce7;color:#15803d">✓ Sorun yok</span></td></tr>
                <tr><td><strong>Ayşe Demir</strong> (B Sınıfı İGU)</td><td>12</td><td><strong style="color:#d97706">%92</strong> (166 sa)</td><td><strong style="color:#d97706">78 / 100</strong></td><td><span class="rozet" style="background:#fef3c7;color:#92400e">⚠ 1 gecikmiş DÖF · Sınırda kapasite</span></td></tr>
                <tr><td><strong>Dr. Hasan Yılmaz</strong> (İşyeri Hekimi)</td><td>1</td><td><strong style="color:#2563eb">%100</strong> (180 sa)</td><td><strong style="color:#059669">96 / 100</strong></td><td><span class="rozet" style="background:#eff6ff;color:#1d4ed8">🔒 Tam Süreli Kilitli (Marmara Çelik A.Ş.)</span></td></tr>
                <tr><td><strong>Selin Arslan</strong> (C Sınıfı İGU)</td><td>9</td><td><strong style="color:#dc2626">%108</strong> (194 sa)</td><td><strong style="color:#dc2626">61 / 100</strong></td><td><span class="rozet" style="background:#fee2e2;color:#b91c1c">🚨 Kapasite aşımı + 1 Tehlikeli firmada sınıf uyuşmazlığı</span></td></tr>
            </tbody>
        </table>
    </section>

    <section id="panel-dof" class="kart gizli">
        <h2 style="margin:0 0 .3rem;font-size:1.05rem">ISO 45001 Kapalı Devre DÖF &amp; İşveren Kanıt Onayı</h2>
        <p style="margin:0 0 .9rem;font-size:.82rem;color:#64748b">Kontrol hiyerarşisi, kapanış sonrası artık risk skoru ve 1–5 etkinlik doğrulaması zorunludur.</p>
        <table>
            <thead><tr><th>No</th><th>Firma &amp; Uygunsuzluk</th><th>Kontrol Hiyerarşisi</th><th>İlk → Artık Risk</th><th>Durum</th></tr></thead>
            <tbody>
                <tr><td><strong>SB-2026-0142</strong></td><td><strong>Marmara Çelik</strong> · Yüksekte çalışma yaşam hattı ankrajı eksik</td><td><span class="rozet" style="background:#2563eb;color:#fff">Mühendislik</span></td><td>20 → <strong>4</strong> (★ 5/5)</td><td><span class="rozet" style="background:#fef3c7;color:#92400e">📩 İşveren Kanıtı Onay Bekliyor</span></td></tr>
                <tr><td><strong>SB-2026-0138</strong></td><td><strong>Ege Lojistik</strong> · Forklift geri görüş sesli ikazı arızalı</td><td><span class="rozet" style="background:#047857;color:#fff">Eliminasyon</span></td><td>15 → <strong>3</strong> (★ 5/5)</td><td><span class="rozet" style="background:#dcfce7;color:#15803d">✓ Uzman Onaylı Kapandı</span></td></tr>
                <tr><td><strong>SB-2026-0145</strong></td><td><strong>Anadolu Gıda</strong> · Kimyasal deposu göz duşu önü kapalı</td><td><span class="rozet" style="background:#b45309;color:#fff">İdari</span></td><td>12 → <em>Bekliyor</em></td><td><span class="rozet" style="background:#fee2e2;color:#b91c1c">Açık (2 gün kaldı)</span></td></tr>
            </tbody>
        </table>
    </section>

    <section id="panel-mufettis" class="kart gizli">
        <h2 style="margin:0 0 .3rem;font-size:1.05rem">Sanal Müfettiş — 6331 Sayılı Kanun İPC Risk Simülasyonu</h2>
        <p style="margin:0 0 .9rem;font-size:.82rem;color:#64748b">Örnek: Çok Tehlikeli · 64 Çalışan (3,0× kademeli çarpan). "Uygun Değil" çıkan periyodik kontrol tamamlanmış sayılmaz.</p>
        <table>
            <thead><tr><th>Madde</th><th>Yükümlülük</th><th>Durum</th><th>Tahmini 2026 İPC Riski</th></tr></thead>
            <tbody>
                <tr><td>RISK-001</td><td>Risk Değerlendirmesi (v2 İmzalı &amp; Kilitli)</td><td><span class="rozet" style="background:#dcfce7;color:#15803d">✓ Uyumlu</span></td><td>0 ₺</td></tr>
                <tr><td>EQP-001</td><td>Periyodik Kontrol (1 vinç sonucu: <strong>UYGUN DEĞİL</strong>)</td><td><span class="rozet" style="background:#fee2e2;color:#b91c1c">✕ Uygunsuz</span></td><td><strong>106.386 ₺ – 319.158 ₺</strong></td></tr>
                <tr><td>EDU-001</td><td>İşe Giriş Temel İSG Eğitimi (4 yeni çalışan eksik)</td><td><span class="rozet" style="background:#fee2e2;color:#b91c1c">✕ Eksik</span></td><td><strong>86.160 ₺</strong></td></tr>
            </tbody>
        </table>
    </section>

    <section id="panel-saha" class="kart gizli">
        <h2 style="margin:0 0 .3rem;font-size:1.05rem">4'lü Saha Ziyaret Kanıtı, Canlı QR İmza ve Sağlık/Eğitim Süiti</h2>
        <p style="margin:0 0 .9rem;font-size:.82rem;color:#64748b">Mehse; tek platformda İSG Uzmanı + İşyeri Hekimi (EK-1/EK-2) + RG 02.04.2026 Uzaktan &amp; Canlı Eğitim + PTW + KKD + OSGB Cari/Hakediş sunar.</p>
        <div class="kpi" style="margin-bottom:0">
            <div class="kart" style="margin:0"><strong>📍 4'lü Ziyaret Kanıtı</strong><p style="font-size:.8rem;color:#475569;margin:.35rem 0 0">QR Check-in + GPS koordinat + dakika ölçümü + Onaylı Defter bağlantısı ile aylık eksik saha dakikası takibi.</p></div>
            <div class="kart" style="margin:0"><strong>✍️ Tablette / QR Canlı İmza</strong><p style="font-size:.8rem;color:#475569;margin:.35rem 0 0">Çalışanlar kendi telefonundan QR ile eğitim, KKD zimmet ve kurul tutanaklarını doğrulama kodlu mühürlü PDF olarak imzalar.</p></div>
            <div class="kart" style="margin:0"><strong>🩺 Hekim &amp; Eğitim LMS</strong><p style="font-size:.8rem;color:#475569;margin:.35rem 0 0">EK-2 muayene, işe dönüş, meslek hastalığı ve senkron canlı QR sınavla ≥70 puana anında QR sertifika.</p></div>
        </div>
    </section>
</main>
<script>
    function demoSekme(ad, btn) {
        document.querySelectorAll('section[id^="panel-"]').forEach(el => el.classList.add('gizli'));
        document.getElementById('panel-' + ad).classList.remove('gizli');
        document.querySelectorAll('.sekme').forEach(b => b.classList.remove('aktif'));
        btn.classList.add('aktif');
    }
</script>
</body>
</html>
