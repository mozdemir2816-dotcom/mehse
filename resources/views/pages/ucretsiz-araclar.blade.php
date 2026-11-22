<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $baslik }} | Mehse İSG</title>
    <meta name="description" content="{{ $aciklama }}">
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f8fafc; color:#0f172a; }
        .ust { background:#0f172a; color:#fff; padding:.9rem 1.25rem; }
        .kap { max-width:960px; margin:0 auto; padding:2rem 1.25rem 4rem; }
        .sekmeler { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .sekme { padding:.55rem 1rem; border-radius:999px; border:1px solid #cbd5e1; background:#fff; color:#334155; text-decoration:none; font-size:.86rem; font-weight:700; }
        .sekme.aktif { background:#2563eb; color:#fff; border-color:#2563eb; }
        .kart { background:#fff; border:1px solid #cbd5e1; border-radius:16px; padding:1.5rem; margin-bottom:1.25rem; }
        label { display:block; font-size:.82rem; font-weight:700; margin:.65rem 0 .25rem; }
        input, select { width:100%; padding:.6rem .8rem; border:1px solid #cbd5e1; border-radius:10px; font-size:.95rem; box-sizing:border-box; }
        .izgara { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:.85rem; }
        .sonuc { border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; padding:1rem; text-align:center; }
        .sonuc b { display:block; font-size:1.5rem; color:#1d4ed8; margin-top:.2rem; }
        .btn { display:inline-block; padding:.65rem 1.2rem; border:none; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; cursor:pointer; text-decoration:none; }
        table { width:100%; border-collapse:collapse; font-size:.86rem; margin-top:1rem; }
        th, td { text-align:left; padding:.55rem; border-bottom:1px solid #e2e8f0; }
    </style>
</head>
<body>
<header class="ust">
    <div style="max-width:960px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#fff;text-decoration:none;font-weight:800;font-size:1.1rem">MEHSE İSG</a>
        <nav style="display:flex;gap:1rem;align-items:center;font-size:.86rem;flex-wrap:wrap">
            <a href="{{ url('/') }}" style="color:#cbd5e1;text-decoration:none">Ana Sayfa</a>
            <a href="{{ route('fiyatlandirma') }}" style="color:#cbd5e1;text-decoration:none">Fiyatlandırma</a>
            <a href="{{ url('/admin/register') }}" class="btn" style="background:#16a34a;padding:.45rem .85rem">7 Gün Ücretsiz Dene</a>
        </nav>
    </div>
</header>

<main class="kap">
    <div class="sekmeler">
        <a href="{{ route('araclar.tehlike') }}" class="sekme {{ $aktifArac === 'tehlike' ? 'aktif' : '' }}">🔍 Tehlike Sınıfı Sorgulama</a>
        <a href="{{ route('araclar.sure') }}" class="sekme {{ $aktifArac === 'sure' ? 'aktif' : '' }}">⏱ 6331 İSG Süre Hesaplama</a>
        <a href="{{ route('araclar.kso') }}" class="sekme {{ $aktifArac === 'kso' ? 'aktif' : '' }}">📊 Kaza Sıklık &amp; Ağırlık Hızı</a>
    </div>

    <div class="kart">
        <h1 style="margin:0 0 .35rem;font-size:1.45rem">{{ $baslik }}</h1>
        <p style="margin:0 0 1.2rem;color:#475569;font-size:.92rem">{{ $aciklama }}</p>

        @if ($aktifArac === 'tehlike')
            <form method="GET" action="{{ route('araclar.tehlike') }}" style="display:flex;gap:.5rem;flex-wrap:wrap">
                <input type="search" name="q" value="{{ $q }}" placeholder="NACE kodu (örn. 25.62) veya faaliyet adı (örn. metal, otomotiv, lojistik)..." style="flex:1;min-width:240px">
                <button type="submit" class="btn">Sorgula</button>
            </form>
            @if ($q !== '')
                @if (count($sonuclar))
                    <table>
                        <thead><tr><th>NACE Kodu</th><th>Faaliyet Tanımı</th><th>Tehlike Sınıfı</th></tr></thead>
                        <tbody>
                            @foreach ($sonuclar as $s)
                                <tr>
                                    <td><strong>{{ $s['kod'] }}</strong></td>
                                    <td>{{ $s['ad'] }}</td>
                                    <td><strong>{{ strtoupper(str_replace('_', ' ', $s['sinif'])) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="margin-top:1rem;color:#64748b;font-size:.88rem">"{{ $q }}" için eşleşen NACE kaydı bulunamadı. Farklı bir anahtar kelime deneyin veya ücretsiz panelde tüm SGK Sicil No çözümleyicisini kullanın.</p>
                @endif
            @endif
        @elseif ($aktifArac === 'sure')
            <div class="izgara">
                <div>
                    <label for="s-sinif">Tehlike Sınıfı</label>
                    <select id="s-sinif" onchange="hesaplaSure()">
                        <option value="az">Az Tehlikeli (10 dk İGU / 5 dk İH)</option>
                        <option value="tehlikeli" selected>Tehlikeli (20 dk İGU / 10 dk İH)</option>
                        <option value="cok">Çok Tehlikeli (40 dk İGU / 15 dk İH)</option>
                    </select>
                </div>
                <div>
                    <label for="s-calisan">Çalışan Sayısı</label>
                    <input id="s-calisan" type="number" min="1" value="35" oninput="hesaplaSure()">
                </div>
            </div>
            <div class="izgara" style="margin-top:1.1rem">
                <div class="sonuc"><span>Aylık İGU Süresi</span><b id="out-igu">—</b></div>
                <div class="sonuc"><span>Aylık İşyeri Hekimi</span><b id="out-ih">—</b></div>
                <div class="sonuc"><span>Aylık DSP Süresi</span><b id="out-dsp">—</b></div>
            </div>
            <script>
                function fmtDk(dk) { const s = Math.floor(dk / 60), k = dk % 60; return dk + ' dk (' + s + ' sa ' + k + ' dk)'; }
                function hesaplaSure() {
                    const sinif = document.getElementById('s-sinif').value;
                    const n = Math.max(0, parseInt(document.getElementById('s-calisan').value || '0', 10));
                    const igu = n * (sinif === 'cok' ? 40 : (sinif === 'tehlikeli' ? 20 : 10));
                    const ih = n * (sinif === 'cok' ? 15 : (sinif === 'tehlikeli' ? 10 : 5));
                    const dsp = (sinif === 'cok' && n >= 10) ? n * (n < 50 ? 10 : (n < 250 ? 15 : 20)) : 0;
                    document.getElementById('out-igu').textContent = fmtDk(igu);
                    document.getElementById('out-ih').textContent = fmtDk(ih);
                    document.getElementById('out-dsp').textContent = dsp > 0 ? fmtDk(dsp) : 'Zorunlu değil';
                }
                hesaplaSure();
            </script>
        @else
            <div class="izgara">
                <div><label for="k-kaza">Toplam İş Kazası Sayısı</label><input id="k-kaza" type="number" min="0" value="3" oninput="hesaplaKso()"></div>
                <div><label for="k-gun">Toplam Kayıp İş Günü</label><input id="k-gun" type="number" min="0" value="45" oninput="hesaplaKso()"></div>
                <div><label for="k-calisan">Ortalama Çalışan Sayısı</label><input id="k-calisan" type="number" min="1" value="100" oninput="hesaplaKso()"></div>
                <div><label for="k-saat">Toplam Çalışılan Saat (P)</label><input id="k-saat" type="number" min="1" value="270000" oninput="hesaplaKso()"></div>
            </div>
            <div class="izgara" style="margin-top:1.1rem">
                <div class="sonuc"><span>Kaza Sıklık Oranı (KSO)</span><b id="out-kso">—</b></div>
                <div class="sonuc"><span>Kaza Ağırlık Oranı (KAO)</span><b id="out-kao">—</b></div>
                <div class="sonuc"><span>Kaza İnsidans Oranı (1000 kişi)</span><b id="out-ins">—</b></div>
            </div>
            <script>
                function hesaplaKso() {
                    const kaza = parseFloat(document.getElementById('k-kaza').value || '0');
                    const gun = parseFloat(document.getElementById('k-gun').value || '0');
                    const calisan = Math.max(1, parseFloat(document.getElementById('k-calisan').value || '1'));
                    const saat = Math.max(1, parseFloat(document.getElementById('k-saat').value || '1'));
                    document.getElementById('out-kso').textContent = ((kaza / saat) * 1000000).toFixed(2);
                    document.getElementById('out-kao').textContent = ((gun / saat) * 1000).toFixed(2);
                    document.getElementById('out-ins').textContent = ((kaza / calisan) * 1000).toFixed(1);
                }
                hesaplaKso();
            </script>
        @endif
    </div>

    <div class="kart" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;border:none;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <div>
            <strong style="font-size:1.08rem;display:block">Tüm İSG Evraklarınızı PDF, QR İmza ve Yapay Zekâ ile Tek Panelde Yönetin</strong>
            <span style="opacity:.9;font-size:.88rem">Kredi kartı gerekmeden 7 gün ücretsiz deneme hesabınızı hemen açın.</span>
        </div>
        <a href="{{ url('/admin/register') }}" class="btn" style="background:#fff;color:#1e3a8a">7 Gün Ücretsiz Başla →</a>
    </div>
</main>
</body>
</html>
