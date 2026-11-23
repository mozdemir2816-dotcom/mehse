<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $oturum->baslik }} — Canlı Eğitim Sınavı | Mehse İSG</title>
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f1f5f9; color:#0f172a; padding:1rem; }
        .kart { max-width:680px; margin:0 auto; background:#fff; border:1px solid #cbd5e1; border-radius:16px; padding:1.4rem; }
        .soru { border:1px solid #e2e8f0; border-radius:12px; padding:.9rem; margin-top:.85rem; }
        label.sec { display:flex; gap:.5rem; align-items:flex-start; padding:.35rem 0; cursor:pointer; font-size:.9rem; }
        input[type=text] { width:100%; padding:.6rem .8rem; border:1px solid #cbd5e1; border-radius:10px; font-size:.95rem; box-sizing:border-box; }
        .btn { display:inline-block; padding:.75rem 1.25rem; border:none; border-radius:12px; background:#2563eb; color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; text-decoration:none; }
    </style>
</head>
<body>
<div class="kart">
    <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:.74rem;font-weight:800;padding:.2rem .6rem;border-radius:999px">
        🎥 {{ $oturum->kategoriEtiketi() }} · {{ $oturum->sure_saat }} Saat
    </span>
    <h1 style="margin:.45rem 0 .2rem;font-size:1.35rem">{{ $oturum->baslik }}</h1>
    <div style="font-size:.86rem;color:#475569;margin-bottom:1rem">
        {{ $oturum->firma?->unvan }} · {{ $oturum->oturum_tarihi?->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}
        @if ($oturum->toplanti_url && preg_match('#^https?://#i', $oturum->toplanti_url))
            · <a href="{{ $oturum->toplanti_url }}" target="_blank" rel="noopener noreferrer" style="color:#2563eb;font-weight:700">Canlı Oturuma Katıl ↗</a>
        @endif
    </div>

    @if ($sonuc)
        <div style="padding:1rem;border-radius:12px;margin-bottom:1rem;background:{{ $sonuc['gecti'] ? '#dcfce7' : '#fee2e2' }};color:{{ $sonuc['gecti'] ? '#166534' : '#991b1b' }}">
            <div style="font-weight:800;font-size:1.05rem">
                {{ $sonuc['gecti'] ? '✅ Tebrikler! Sınavı Geçtiniz' : '✗ Geçme Notuna Ulaşılamadı' }} — Puan: {{ $sonuc['puan'] }} / 100
            </div>
            <div style="font-size:.86rem;margin-top:.25rem">
                {{ $sonuc['ad_soyad'] }} · {{ $sonuc['dogru'] }}/{{ $sonuc['toplam'] }} doğru (Geçme barajı: {{ $oturum->gecme_notu }})
            </div>
            @if ($sonuc['gecti'])
                <div style="margin-top:.75rem">
                    <a href="{{ route('canli-egitim.sertifika', ['token' => $oturum->katilim_token, 'kod' => $sonuc['kod']]) }}" class="btn" style="background:#15803d">
                        🎓 QR Doğrulamalı Sertifikayı İndir (PDF)
                    </a>
                </div>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('canli-egitim.gonder', ['token' => $oturum->katilim_token]) }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem">
            <div>
                <label style="display:block;font-size:.82rem;font-weight:700;margin-bottom:.25rem">Ad Soyad *</label>
                <input type="text" name="ad_soyad" required placeholder="Adınız Soyadınız">
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:700;margin-bottom:.25rem">T.C. Kimlik No (Opsiyonel)</label>
                <input type="text" name="tc" maxlength="11" inputmode="numeric" placeholder="11 haneli T.C. No">
            </div>
        </div>

        @foreach (($oturum->sorular ?? []) as $i => $s)
            <div class="soru">
                <div style="font-weight:700;font-size:.92rem;margin-bottom:.4rem">{{ $i + 1 }}. {{ $s['soru'] }}</div>
                @foreach (($s['secenekler'] ?? []) as $j => $sec)
                    <label class="sec">
                        <input type="radio" name="cevaplar[{{ $i }}]" value="{{ $j }}" required>
                        <span>{{ $sec }}</span>
                    </label>
                @endforeach
            </div>
        @endforeach

        <button type="submit" class="btn" style="width:100%;margin-top:1rem">📝 Sınavı Tamamla ve Sertifika Al</button>
    </form>
</div>
</body>
</html>
