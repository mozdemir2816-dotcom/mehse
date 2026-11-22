<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Saha Risk &amp; Ramak Kala Bildirimi — {{ $firma->unvan }}</title>
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f1f5f9; color:#0f172a; padding:1rem; }
        .kart { max-width:560px; margin:0 auto; background:#fff; border:1px solid #cbd5e1; border-radius:18px; padding:1.25rem; box-shadow:0 12px 30px -18px rgb(15 23 42/.35); }
        .ust { border-bottom:1px solid #e2e8f0; padding-bottom:.8rem; margin-bottom:1rem; }
        .rozet { display:inline-block; font-size:.72rem; font-weight:700; padding:.2rem .6rem; border-radius:999px; background:#fef3c7; color:#92400e; }
        h1 { font-size:1.2rem; margin:.4rem 0 .2rem; }
        label { display:block; font-size:.82rem; font-weight:700; margin:.75rem 0 .3rem; }
        input[type=text], select, textarea { width:100%; padding:.65rem .8rem; border:1px solid #cbd5e1; border-radius:10px; font-size:.95rem; font-family:inherit; }
        .tur-izgara { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
        .tur-sec { display:flex; align-items:center; gap:.45rem; border:1px solid #cbd5e1; border-radius:10px; padding:.6rem .75rem; cursor:pointer; font-size:.86rem; font-weight:600; margin:0; }
        .btn { width:100%; margin-top:1rem; padding:.8rem; border:none; border-radius:12px; background:#dc2626; color:#fff; font-weight:800; font-size:.98rem; cursor:pointer; }
        .ok { padding:.85rem 1rem; border-radius:12px; background:#dcfce7; color:#166534; font-weight:700; margin-bottom:1rem; }
        .err { padding:.75rem 1rem; border-radius:12px; background:#fee2e2; color:#991b1b; font-size:.85rem; margin-bottom:1rem; }
    </style>
</head>
<body>
<div class="kart">
    <div class="ust">
        <span class="rozet">📱 ANLIK SAHA BİLDİRİMİ (GİRİŞ GEREKTİRMEZ)</span>
        <h1>Tehlike / Ramak Kala Bildir</h1>
        <div style="font-size:.86rem;color:#475569">{{ $firma->unvan }}</div>
    </div>

    @if (session('basarili'))
        <div class="ok">✅ {{ session('basarili') }}</div>
    @endif

    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('risk.bildir.kaydet', ['firma' => $firma->id, 'imza' => $imza]) }}" enctype="multipart/form-data">
        @csrf
        <label>Bildirim Türü *</label>
        <div class="tur-izgara">
            <label class="tur-sec"><input type="radio" name="tur" value="tehlike" @checked(old('tur', 'tehlike') === 'tehlike')> ⚠ Tehlike / Risk</label>
            <label class="tur-sec"><input type="radio" name="tur" value="ramak_kala" @checked(old('tur') === 'ramak_kala')> ⚡ Ramak Kala</label>
        </div>

        <label for="bolum">Bölüm / Konum *</label>
        <input id="bolum" name="bolum" type="text" required value="{{ old('bolum') }}" placeholder="Örn: Pres Hattı, Depo, Yükleme Rampası">

        <label for="uygunsuzluk">Ne Gözlemlediniz? *</label>
        <textarea id="uygunsuzluk" name="uygunsuzluk" rows="4" required placeholder="Tehlikeyi veya ramak kala olayını kısaca anlatın...">{{ old('uygunsuzluk') }}</textarea>

        <label for="aciliyet">Aciliyet Derecesi</label>
        <select id="aciliyet" name="aciliyet">
            <option value="kritik" @selected(old('aciliyet') === 'kritik')>🔴 Acil / Hayati Tehlike</option>
            <option value="yuksek" @selected(old('aciliyet', 'yuksek') === 'yuksek')>🟠 Yüksek Öncelik</option>
            <option value="orta" @selected(old('aciliyet') === 'orta')>🟡 Orta</option>
            <option value="dusuk" @selected(old('aciliyet') === 'dusuk')>🟢 Düşük / Öneri</option>
        </select>

        <label for="foto">Fotoğraf Ekle (Opsiyonel)</label>
        <input id="foto" name="foto" type="file" accept="image/*" capture="environment">

        <label for="bildiren">Ad Soyad (Opsiyonel)</label>
        <input id="bildiren" name="bildiren" type="text" value="{{ old('bildiren') }}" placeholder="İsimsiz bildirmek için boş bırakabilirsiniz">

        <label style="display:flex;align-items:center;gap:.45rem;font-weight:500">
            <input type="checkbox" name="isimsiz" value="1" @checked(old('isimsiz'))> Kimliğimi gizli tut (İsimsiz bildir)
        </label>

        <button type="submit" class="btn">🚨 İSG Birimine Bildir</button>
    </form>
</div>
</body>
</html>
