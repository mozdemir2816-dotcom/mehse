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
            <a href="{{ route('demo') }}" style="color:#93c5fd;text-decoration:none;font-weight:700">Canlı Demo</a>
            <a href="{{ route('fiyatlandirma') }}" style="color:#cbd5e1;text-decoration:none">Fiyatlandırma</a>
            <a href="{{ url('/admin/register') }}" class="btn" style="background:#16a34a;padding:.45rem .85rem">7 Gün Ücretsiz Dene</a>
        </nav>
    </div>
</header>

<main class="kap">
    <div class="sekmeler">
        <a href="{{ route('araclar.tehlike') }}" class="sekme {{ $aktifArac === 'tehlike' ? 'aktif' : '' }}">🔍 Tehlike Sınıfı</a>
        <a href="{{ route('araclar.sure') }}" class="sekme {{ $aktifArac === 'sure' ? 'aktif' : '' }}">⏱ İSG Süre</a>
        <a href="{{ route('araclar.kso') }}" class="sekme {{ $aktifArac === 'kso' ? 'aktif' : '' }}">📊 Kaza Oranları</a>
        <a href="{{ route('araclar.ipc') }}" class="sekme {{ $aktifArac === 'ipc' ? 'aktif' : '' }}">⚖️ 2026 İPC Ceza</a>
        <a href="{{ route('araclar.ekip') }}" class="sekme {{ $aktifArac === 'ekip' ? 'aktif' : '' }}">🧯 İlkyardım &amp; Ekip</a>
        <a href="{{ route('araclar.risk') }}" class="sekme {{ $aktifArac === 'risk' ? 'aktif' : '' }}">🎯 Fine-Kinney / 5×5</a>
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
                                    <td><strong>{{ ['az_tehlikeli' => 'Az Tehlikeli', 'tehlikeli' => 'Tehlikeli', 'cok_tehlikeli' => 'Çok Tehlikeli'][$s['sinif']] ?? strtoupper(str_replace('_', ' ', $s['sinif'])) }}</strong></td>
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
                    const dspMuaf = sinif === 'cok' && n >= 750;
                    const dsp = (sinif === 'cok' && n >= 10 && !dspMuaf) ? n * (n < 50 ? 10 : (n < 250 ? 15 : 20)) : 0;
                    document.getElementById('out-igu').textContent = fmtDk(igu);
                    document.getElementById('out-ih').textContent = fmtDk(ih);
                    document.getElementById('out-dsp').textContent = dspMuaf ? 'Zorunlu değil (750+ tam süreli İH · m.19/3)' : (dsp > 0 ? fmtDk(dsp) : 'Zorunlu değil');
                }
                hesaplaSure();
            </script>
        @elseif ($aktifArac === 'kso')
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
        @elseif ($aktifArac === 'ipc')
            <div class="izgara">
                <div>
                    <label for="ipc-sinif">Tehlike Sınıfı</label>
                    <select id="ipc-sinif" onchange="hesaplaIpc()">
                        <option value="az">Az Tehlikeli</option>
                        <option value="tehlikeli" selected>Tehlikeli</option>
                        <option value="cok">Çok Tehlikeli</option>
                    </select>
                </div>
                <div>
                    <label for="ipc-calisan">Çalışan Sayısı</label>
                    <input id="ipc-calisan" type="number" min="1" value="30" oninput="hesaplaIpc()">
                </div>
            </div>
            <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.5rem;font-size:.86rem">
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="88663" data-aylik="1" checked onchange="hesaplaIpc()" style="width:auto"> İGU Görevlendirmemek (md.26/1-b · her ay)</label>
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="88663" data-aylik="1" checked onchange="hesaplaIpc()" style="width:auto"> İşyeri Hekimi Görevlendirmemek (md.26/1-b · her ay)</label>
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="53197" data-aylik="1" checked onchange="hesaplaIpc()" style="width:auto"> Risk Değerlendirmesi Yapmamak (md.26/1-ç · devam eden her ay)</label>
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="17731" data-aylik="1" onchange="hesaplaIpc()" style="width:auto"> Acil Durum Planı / Tatbikat Eksik (md.26/1-d · her ay)</label>
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="35462" data-aylik="0" onchange="hesaplaIpc()" style="width:auto"> Periyodik Kontrol Eksik / Uygunsuz</label>
                <label style="display:flex;gap:.45rem;align-items:center;margin:0"><input type="checkbox" class="ipc-kalem" data-taban="17731" data-aylik="0" onchange="hesaplaIpc()" style="width:auto"> Çalışan Temsilcisi Atamamak (md.26/1-i)</label>
            </div>
            <div class="izgara" style="margin-top:1.1rem">
                <div class="sonuc"><span>6331 m.26/2 Çarpanı</span><b id="out-ipc-carpan">—</b></div>
                <div class="sonuc" style="border-color:#fca5a5;background:#fef2f2"><span>Tahmini Ceza Tutarı (m.26/2 Çarpanlı · Tek Ay / Tespit)</span><b id="out-ipc-tutar" style="color:#b91c1c">—</b></div>
            </div>
            <p style="font-size:.78rem;color:#64748b;margin:.6rem 0 0">Not: 6331 SK md.26/2 çarpanı (1× – 3×) taban tutara yalnızca bir kez uygulanır. Devam eden aykırılıklarda ilgili kalemler her ay tekrarlanır; güncel ÇSGB yeniden değerleme tablosunu esas alınız.</p>
            <script>
                function hesaplaIpc() {
                    const s = document.getElementById('ipc-sinif').value;
                    const n = Math.max(1, parseInt(document.getElementById('ipc-calisan').value || '1', 10));
                    let c = 1.0;
                    if (n < 10) c = s === 'cok' ? 1.5 : (s === 'tehlikeli' ? 1.25 : 1.0);
                    else if (n < 50) c = s === 'cok' ? 2.0 : (s === 'tehlikeli' ? 1.5 : 1.0);
                    else c = s === 'cok' ? 3.0 : (s === 'tehlikeli' ? 2.0 : 1.5);
                    let toplam = 0;
                    document.querySelectorAll('.ipc-kalem:checked').forEach(el => {
                        toplam += Math.round(parseFloat(el.dataset.taban) * c);
                    });
                    document.getElementById('out-ipc-carpan').textContent = c.toFixed(2) + '×';
                    document.getElementById('out-ipc-tutar').textContent = toplam.toLocaleString('tr-TR') + ' ₺';
                }
                hesaplaIpc();
            </script>
        @elseif ($aktifArac === 'ekip')
            <div class="izgara">
                <div>
                    <label for="ek-sinif">Tehlike Sınıfı</label>
                    <select id="ek-sinif" onchange="hesaplaEkip()">
                        <option value="az">Az Tehlikeli (Destek: 1/50 · İlkyardım: 1/20)</option>
                        <option value="tehlikeli" selected>Tehlikeli (Destek: 1/40 · İlkyardım: 1/15)</option>
                        <option value="cok">Çok Tehlikeli (Destek: 1/30 · İlkyardım: 1/10)</option>
                    </select>
                </div>
                <div>
                    <label for="ek-calisan">Çalışan Sayısı</label>
                    <input id="ek-calisan" type="number" min="1" value="45" oninput="hesaplaEkip()">
                </div>
            </div>
            <div class="izgara" style="margin-top:1.1rem">
                <div class="sonuc"><span>Söndürme Ekibi (Min.)</span><b id="out-ek-son">—</b></div>
                <div class="sonuc"><span>Kurtarma &amp; Koruma (Her biri)</span><b id="out-ek-kur">—</b></div>
                <div class="sonuc"><span>Sertifikalı İlkyardımcı</span><b id="out-ek-ilk">—</b></div>
                <div class="sonuc"><span>Çalışan Temsilcisi (6331 m.20)</span><b id="out-ek-tem">—</b></div>
            </div>
            <p id="out-ek-not" style="font-size:.78rem;color:#64748b;margin:.6rem 0 0"></p>
            <script>
                function hesaplaEkip() {
                    const s = document.getElementById('ek-sinif').value;
                    const n = Math.max(1, parseInt(document.getElementById('ek-calisan').value || '1', 10));
                    const destekOran = s === 'cok' ? 30 : (s === 'tehlikeli' ? 40 : 50);
                    const ilkOran = s === 'cok' ? 10 : (s === 'tehlikeli' ? 15 : 20);
                    const ondanAz = n < 10;
                    const destek = Math.max(1, Math.ceil(n / destekOran));
                    const ilk = Math.max(1, Math.ceil(n / ilkOran));
                    const tem = n < 2 ? 0 : (n <= 50 ? 1 : (n <= 100 ? 2 : (n <= 500 ? 3 : (n <= 1000 ? 4 : (n <= 2000 ? 5 : 6)))));
                    document.getElementById('out-ek-son').textContent = ondanAz ? 'Toplam 1 destek elemanı' : (destek + ' kişi');
                    document.getElementById('out-ek-kur').textContent = ondanAz ? '(Söndürme+Kurtarma+Koruma ortak)' : (destek + ' kişi');
                    document.getElementById('out-ek-ilk').textContent = ilk + ' kişi';
                    document.getElementById('out-ek-tem').textContent = tem === 0 ? 'Zorunlu değil (<2 çalışan)' : (tem + ' kişi');
                    document.getElementById('out-ek-not').textContent = ondanAz
                        ? 'İşyerlerinde Acil Durumlar Hk. Yönetmelik m.11/4: 10\'dan az çalışanı olan işyerlerinde söndürme, kurtarma ve koruma ekiplerinin tamamı için en az 1 destek elemanı yeterlidir.'
                        : '';
                }
                hesaplaEkip();
            </script>
        @else
            <div class="izgara">
                <div>
                    <label for="rk-yontem">Değerlendirme Yöntemi</label>
                    <select id="rk-yontem" onchange="yontemDegisti()">
                        <option value="fk" selected>Fine-Kinney (O × F × Ş)</option>
                        <option value="m5">5×5 L-Tipi Matris (O × Ş)</option>
                    </select>
                </div>
                <div>
                    <label for="rk-o">Olasılık</label>
                    <select id="rk-o" onchange="hesaplaRisk()"></select>
                </div>
                <div id="rk-f-kutu">
                    <label for="rk-f">Frekans (Maruziyet)</label>
                    <select id="rk-f" onchange="hesaplaRisk()">
                        <option value="0.5">0.5 (Çok Nadir)</option>
                        <option value="1">1 (Yılda Birkaç Kez)</option>
                        <option value="2">2 (Ayda Bir)</option>
                        <option value="3" selected>3 (Haftada Bir)</option>
                        <option value="6">6 (Günde Bir)</option>
                        <option value="10">10 (Sürekli)</option>
                    </select>
                </div>
                <div>
                    <label for="rk-s">Şiddet</label>
                    <select id="rk-s" onchange="hesaplaRisk()"></select>
                </div>
            </div>
            <div class="izgara" style="margin-top:1.1rem">
                <div class="sonuc"><span>Risk Skoru</span><b id="out-rk-skor">—</b></div>
                <div class="sonuc"><span>Risk Sınıfı &amp; Aksiyon</span><b id="out-rk-seviye" style="font-size:1.05rem">—</b></div>
            </div>
            <script>
                const FK_O = [['0.2','0.2 (Pratikte İmkânsız)'],['0.5','0.5 (Zayıf İhtimal)'],['1','1 (Düşük)'],['3','3 (Mümkün)'],['6','6 (Yüksek)'],['10','10 (Çok Yüksek)']];
                const FK_S = [['1','1 (Hafif / İlkyardım)'],['3','3 (Tıbbi Müdahale)'],['7','7 (Ciddi Yaralanma)'],['15','15 (Ölüm / Ağır Maluliyet)'],['40','40 (Birden Fazla Ölüm)'],['100','100 (Felaket)']];
                const M5_O = [['1','1 (Çok Küçük)'],['2','2 (Küçük)'],['3','3 (Orta)'],['4','4 (Yüksek)'],['5','5 (Çok Yüksek)']];
                const M5_S = [['1','1 (Çok Hafif)'],['2','2 (Hafif)'],['3','3 (Orta)'],['4','4 (Ciddi)'],['5','5 (Çok Ciddi / Ölüm)']];
                function doldurSecenek(id, liste, secili) {
                    const el = document.getElementById(id);
                    el.innerHTML = liste.map(([v, ad]) => `<option value="${v}" ${v === secili ? 'selected' : ''}>${ad}</option>`).join('');
                }
                function yontemDegisti() {
                    const y = document.getElementById('rk-yontem').value;
                    if (y === 'm5') {
                        doldurSecenek('rk-o', M5_O, '3');
                        doldurSecenek('rk-s', M5_S, '4');
                    } else {
                        doldurSecenek('rk-o', FK_O, '3');
                        doldurSecenek('rk-s', FK_S, '15');
                    }
                    hesaplaRisk();
                }
                function hesaplaRisk() {
                    const y = document.getElementById('rk-yontem').value;
                    document.getElementById('rk-f-kutu').style.display = y === 'fk' ? 'block' : 'none';
                    const o = parseFloat(document.getElementById('rk-o').value);
                    const f = parseFloat(document.getElementById('rk-f').value);
                    const s = parseFloat(document.getElementById('rk-s').value);
                    if (y === 'm5') {
                        const skor = o * s;
                        const sev = skor >= 16 ? 'Çok Yüksek (İşi Durdur)' : (skor >= 10 ? 'Yüksek (Acil Önlem)' : (skor >= 5 ? 'Orta (Planlı Önlem)' : 'Düşük (Kabul Edilebilir)'));
                        document.getElementById('out-rk-skor').textContent = skor + ' / 25';
                        document.getElementById('out-rk-seviye').textContent = sev;
                    } else {
                        const skor = +(o * f * s).toFixed(1);
                        const sev = skor > 400 ? 'Tolerans Gösterilemez (>400 · İşi Durdur)' : (skor > 200 ? 'Esaslı Risk (200–400 · Acil)' : (skor > 70 ? 'Önemli Risk (70–200 · Kısa Vadeli)' : (skor > 20 ? 'Olası Risk (20–70 · Gözetim)' : 'Önemsiz Risk (≤20)')));
                        document.getElementById('out-rk-skor').textContent = skor;
                        document.getElementById('out-rk-seviye').textContent = sev;
                    }
                }
                yontemDegisti();
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
