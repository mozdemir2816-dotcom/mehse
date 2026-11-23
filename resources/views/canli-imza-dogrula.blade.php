<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Dijital Onay Doğrulama | Mehse İSG</title>
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
        @php
            $maskeliAd = collect(preg_split('/\s+/u', trim((string) $imza->calisan_adi)) ?: [])
                ->filter()
                ->map(fn ($k) => mb_substr($k, 0, 2, 'UTF-8').str_repeat('*', max(1, mb_strlen($k, 'UTF-8') - 2)))
                ->implode(' ');
        @endphp
        <div style="color:#15803d;font-weight:800;font-size:1.1rem;margin-bottom:.75rem">✅ Geçerli Dijital Onay Kaydı</div>
        <div class="satir"><span class="etiket">İşyeri</span><strong>{{ $imza->firma?->unvan }}</strong></div>
        <div class="satir"><span class="etiket">Belge Türü</span><strong>{{ $imza->belgeTuruEtiketi() }}</strong></div>
        <div class="satir"><span class="etiket">Belge Başlığı</span><strong>{{ $imza->belge_baslik }}</strong></div>
        <div class="satir"><span class="etiket">İmzalayan (Maskeli)</span><strong>{{ $maskeliAd ?: '—' }}</strong></div>
        <div class="satir"><span class="etiket">T.C. (Maskeli)</span><strong>{{ $imza->maskeliTc() }}</strong></div>
        <div class="satir"><span class="etiket">Onay Zamanı</span><strong>{{ $imza->imzalandi_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</strong></div>
        <div class="satir"><span class="etiket">Doğrulama Kodu</span><strong>{{ $imza->dogrulama_kodu }}</strong></div>
        <p style="font-size:.74rem;color:#64748b;margin:.9rem 0 0;line-height:1.4">
            Not: Bu kayıt dijital teslim/basit elektronik onay niteliğindedir; 5070 sayılı Kanun kapsamında güvenli elektronik imza yerine geçmez.
        </p>
    @else
        <div style="color:#dc2626;font-weight:800;font-size:1.1rem">✕ İmza Kaydı Bulunamadı</div>
        <p style="font-size:.9rem;color:#475569">Bu doğrulama koduna ait onaylanmış bir imza kaydı mevcut değil.</p>
    @endif
</div>
</body>
</html>
