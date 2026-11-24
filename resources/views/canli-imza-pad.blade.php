<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Canlı İmza — {{ $imza->belge_baslik }} | Mehse İSG</title>
    <style>
        :root { --primary:#1d4ed8; --bg:#f1f5f9; --card:#fff; --text:#0f172a; --muted:#475569; --border:#cbd5e1; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:var(--bg); color:var(--text); padding:1rem; }
        .kap { max-width:640px; margin:0 auto; background:var(--card); border-radius:18px; border:1px solid var(--border); padding:1.25rem; box-shadow:0 12px 30px -18px rgb(15 23 42/.35); }
        .ust { border-bottom:1px solid #e2e8f0; padding-bottom:.85rem; margin-bottom:1rem; }
        .rozet { display:inline-block; font-size:.72rem; font-weight:700; padding:.2rem .6rem; border-radius:999px; background:#dbeafe; color:#1e40af; }
        h1 { font-size:1.2rem; margin:.4rem 0 .2rem; }
        .firma { font-size:.86rem; color:var(--muted); margin:0; }
        label { display:block; font-size:.82rem; font-weight:700; margin:.75rem 0 .3rem; }
        input[type=text] { width:100%; padding:.65rem .8rem; border:1px solid var(--border); border-radius:10px; font-size:1rem; }
        .pad-sar { border:2px dashed #94a3b8; border-radius:14px; background:#f8fafc; position:relative; touch-action:none; overflow:hidden; }
        canvas { display:block; width:100%; height:220px; cursor:crosshair; }
        .pad-arac { display:flex; justify-content:space-between; align-items:center; margin-top:.45rem; font-size:.8rem; color:var(--muted); }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; padding:.75rem 1.15rem; border-radius:12px; font-weight:700; font-size:.95rem; border:none; cursor:pointer; text-decoration:none; }
        .btn-birincil { background:var(--primary); color:#fff; width:100%; margin-top:1rem; }
        .btn-gri { background:#e2e8f0; color:#334155; padding:.45rem .8rem; font-size:.8rem; }
        .onay { display:flex; gap:.55rem; align-items:flex-start; margin-top:.85rem; font-size:.84rem; line-height:1.4; }
        .onay input { width:1.2rem; height:1.2rem; margin-top:.1rem; flex-shrink:0; }
        .bilgi { padding:.85rem 1rem; border-radius:12px; background:#dcfce7; color:#166534; font-size:.9rem; font-weight:600; margin-bottom:1rem; }
        .hata { padding:.75rem 1rem; border-radius:12px; background:#fee2e2; color:#991b1b; font-size:.85rem; margin-bottom:1rem; }
        .kod { font-family:ui-monospace,Consolas,monospace; font-size:.92rem; background:#f1f5f9; padding:.35rem .65rem; border-radius:8px; display:inline-block; }
    </style>
</head>
<body>
<div class="kap">
    <div class="ust">
        <span class="rozet">{{ $imza->belgeTuruEtiketi() }}</span>
        <h1>{{ $imza->belge_baslik }}</h1>
        <p class="firma">{{ $imza->firma?->unvan }}</p>
    </div>

    @if (session('durum'))
        <div class="bilgi">✓ {{ session('durum') }}</div>
    @endif

    @if ($errors->any())
        <div class="hata">{{ $errors->first() }}</div>
    @endif

    @if ($imza->imzalandiMi())
        <div style="text-align:center;padding:1rem 0">
            <div style="font-size:2.4rem">✅</div>
            <h2 style="font-size:1.1rem;margin:.3rem 0">Belge İmzalandı</h2>
            <p style="font-size:.88rem;color:var(--muted)">
                <strong>{{ $imza->calisan_adi }}</strong> · {{ $imza->imzalandi_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}
            </p>
            @if ($imza->dogrulama_kodu)
                <p style="font-size:.8rem;color:var(--muted)">Doğrulama Kodu: <span class="kod">{{ $imza->dogrulama_kodu }}</span></p>
                <a href="{{ $dogrulamaUrl }}" class="btn btn-gri">Doğrulama Kaydını Gör</a>
            @endif
        </div>
    @else
        <form method="POST" action="{{ route('canli-imza.kaydet', ['token' => $imza->imza_token]) }}" id="imza-form">
            @csrf
            <label for="calisan_adi">Ad Soyad *</label>
            <input id="calisan_adi" name="calisan_adi" type="text" required value="{{ old('calisan_adi', $imza->calisan_adi) }}" placeholder="Adınız ve Soyadınız">

            <label for="calisan_tc">T.C. Kimlik No (Opsiyonel)</label>
            <input id="calisan_tc" name="calisan_tc" type="text" inputmode="numeric" maxlength="11" value="{{ old('calisan_tc', $imza->calisan_tc) }}" placeholder="11 haneli T.C. No">

            <label>İmza Alanı *</label>
            <div class="pad-sar">
                <canvas id="imza-canvas"></canvas>
            </div>
            <div class="pad-arac">
                <span>Parmağınızla veya kalemle yukarıdaki alana imzanızı çizin.</span>
                <button type="button" class="btn btn-gri" id="temizle-btn">Temizle</button>
            </div>
            <input type="hidden" name="imza_veri" id="imza-veri">

            <label class="onay">
                <input type="checkbox" name="onay" value="1" required>
                <span>Yukarıda belirtilen İSG belgesini/tutanak içeriğini okuduğumu, anladığımı, 6698 sayılı KVKK kapsamında iş sağlığı ve güvenliği kayıtlarının tutulması amacıyla işlenmesine bilgilendirildiğimi ve basit elektronik onay/imza verdiğimi (5070 sayılı Kanun kapsamında nitelikli e-imza olmadığını bilerek) beyan ederim.</span>
            </label>

            <button type="submit" class="btn btn-birincil" id="gonder-btn">✍ İmzamı Onayla ve Kaydet</button>
            <p id="cevrimdisi-uyari" style="display:none;font-size:.8rem;color:#b45309;margin-top:.6rem;text-align:center"></p>
        </form>

        <script>
            (function () {
                const canvas = document.getElementById('imza-canvas');
                const ctx = canvas.getContext('2d');
                let ciziyor = false, dolu = false;

                function boyutla() {
                    const r = canvas.getBoundingClientRect();
                    const dpr = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = r.width * dpr;
                    canvas.height = r.height * dpr;
                    ctx.scale(dpr, dpr);
                    ctx.lineWidth = 2.6;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#0f172a';
                }
                boyutla();
                window.addEventListener('resize', () => { if (!dolu) boyutla(); });

                function nokta(e) {
                    const r = canvas.getBoundingClientRect();
                    const p = e.touches ? e.touches[0] : e;
                    return { x: p.clientX - r.left, y: p.clientY - r.top };
                }

                function basla(e) { e.preventDefault(); ciziyor = true; dolu = true; const p = nokta(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
                function hareket(e) { if (!ciziyor) return; e.preventDefault(); const p = nokta(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }
                function bitir() { ciziyor = false; }

                canvas.addEventListener('mousedown', basla);
                canvas.addEventListener('mousemove', hareket);
                window.addEventListener('mouseup', bitir);
                canvas.addEventListener('touchstart', basla, { passive: false });
                canvas.addEventListener('touchmove', hareket, { passive: false });
                window.addEventListener('touchend', bitir);

                document.getElementById('temizle-btn').addEventListener('click', () => {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    dolu = false;
                });

                document.getElementById('imza-form').addEventListener('submit', function (e) {
                    if (!dolu) {
                        e.preventDefault();
                        alert('Lütfen imza alanına imzanızı çizin.');
                        return;
                    }
                    const dataUrl = canvas.toDataURL('image/png');
                    document.getElementById('imza-veri').value = dataUrl;
                    if (!navigator.onLine) {
                        e.preventDefault();
                        try {
                            // KVKK veri minimizasyonu: T.C. Kimlik No tarayıcı localStorage'ında saklanmaz.
                            localStorage.setItem('mehse_offline_imza_{{ $imza->imza_token }}', JSON.stringify({
                                ad: document.getElementById('calisan_adi').value,
                                img: dataUrl,
                            }));
                            const u = document.getElementById('cevrimdisi-uyari');
                            u.style.display = 'block';
                            u.textContent = 'İnternet bağlantısı yok — imzanız bu cihaza güvenle yedeklendi; bağlantı gelince tekrar "İmzamı Onayla"ya dokunun.';
                        } catch (_) {}
                    }
                });
            })();
        </script>
    @endif
</div>
</body>
</html>
