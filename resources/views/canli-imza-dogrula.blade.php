<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İmza Doğrulama | Mehse İSG</title>
    <style>
        body { margin:0; font-family:system-ui,sans-serif; background:#f1f5f9; color:#0f172a; padding:1.5rem; }
        .kart { max-width:540px; margin:2rem auto; background:#fff; border:1px solid #cbd5e1; border-radius:16px; padding:1.5rem; }
        .satir { display:flex; justify-content:space-between; gap:1rem; padding:.55rem 0; border-bottom:1px solid #e2e8f0; font-size:.9rem; }
        .etiket { color:#64748b; }
    </style>
</head>
<body>
<div class="kart">
    @if ($imza && $imza->imzalandiMi())
        <div style="color:#15803d;font-weight:800;font-size:1.1rem;margin-bottom:.75rem">✅ Geçerli Elektronik İmza Kaydı</div>
        <div class="satir"><span class="etiket">İşyeri</span><strong>{{ $imza->firma?->unvan }}</strong></div>
        <div class="satir"><span class="etiket">Belge Türü</span><strong>{{ $imza->belgeTuruEtiketi() }}</strong></div>
        <div class="satir"><span class="etiket">Belge Başlığı</span><strong>{{ $imza->belge_baslik }}</strong></div>
        <div class="satir"><span class="etiket">İmzalayan</span><strong>{{ $imza->calisan_adi }}</strong></div>
        <div class="satir"><span class="etiket">T.C. (Maskeli)</span><strong>{{ $imza->maskeliTc() }}</strong></div>
        <div class="satir"><span class="etiket">İmza Zamanı</span><strong>{{ $imza->imzalandi_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</strong></div>
        <div class="satir"><span class="etiket">Doğrulama Kodu</span><strong>{{ $imza->dogrulama_kodu }}</strong></div>
    @else
        <div style="color:#dc2626;font-weight:800;font-size:1.1rem">✕ İmza Kaydı Bulunamadı</div>
        <p style="font-size:.9rem;color:#475569">Bu doğrulama koduna ait onaylanmış bir imza kaydı mevcut değil.</p>
    @endif
</div>
</body>
</html>
