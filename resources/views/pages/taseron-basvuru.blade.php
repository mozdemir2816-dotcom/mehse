<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Taşeron / Alt İşveren İSG Başvuru Portalı | Mehse İSG</title>
    <style>
        body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:#f8fafc; color:#0f172a; }
        .ust { background:#0f172a; color:#fff; padding:1rem 1.25rem; }
        .kap { max-width:740px; margin:0 auto; padding:1.5rem 1.1rem 4rem; }
        .kart { background:#fff; border:1px solid #cbd5e1; border-radius:14px; padding:1.35rem; margin-bottom:1rem; }
        label { display:block; font-size:.82rem; font-weight:700; margin:.65rem 0 .25rem; }
        input, select { width:100%; padding:.58rem .75rem; border:1px solid #cbd5e1; border-radius:9px; font-size:.92rem; box-sizing:border-box; }
        .izgara { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.8rem; }
        .btn { padding:.7rem 1.3rem; border:none; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; cursor:pointer; font-size:.95rem; }
        .uyari { padding:.8rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:.88rem; }
    </style>
</head>
<body>
<header class="ust">
    <div style="max-width:740px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap">
        <strong>MEHSE İSG · Taşeron / Alt İşveren Ön Başvuru Portalı</strong>
        <span style="font-size:.8rem;color:#94a3b8">{{ $basvuru->firma?->unvan }}</span>
    </div>
</header>
<main class="kap">
    @if (session('tamam'))
        <div class="uyari" style="background:#dcfce7;border:1px solid #86efac;color:#166534">✓ {{ session('tamam') }}</div>
    @endif
    @if (session('bilgi'))
        <div class="uyari" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af">{{ session('bilgi') }}</div>
    @endif
    @if ($basvuru->durum === 'reddedildi' && $basvuru->ret_nedeni)
        <div class="uyari" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b">
            <strong>Eksik / Revizyon Talebi:</strong> {{ $basvuru->ret_nedeni }} — Aşağıdan güncel evraklarınızı tekrar yükleyebilirsiniz.
        </div>
    @endif
    @if ($basvuru->durum === 'onaylandi')
        <div class="kart" style="text-align:center;border-color:#86efac;background:#f0fdf4">
            <h2 style="margin:0 0 .4rem;color:#166534">✓ Başvurunuz Onaylandı</h2>
            <p style="margin:0;color:#15803d;font-size:.9rem"><strong>{{ $basvuru->unvan }}</strong> kaydınız <strong>{{ $basvuru->firma?->unvan }}</strong> taşeron siciline 6 aylık onay süresiyle aktarılmıştır.</p>
        </div>
    @elseif ($basvuru->linkSuresiDolduMu())
        <div class="kart" style="text-align:center;border-color:#fca5a5;background:#fef2f2">
            <h2 style="margin:0 0 .4rem;color:#991b1b">⏱ Başvuru Linkinin Süresi Doldu</h2>
            <p style="margin:0;color:#b91c1c;font-size:.9rem">
                Bu başvuru linki {{ $basvuru->gecerlilikBitis()->format('d.m.Y') }} tarihinde (6 ay) sona ermiştir.
                Yeni dönem onayı için İSG uzmanınızdan güncel başvuru linki talep ediniz.
            </p>
        </div>
    @else
        <div class="kart">
            <h1 style="margin:0 0 .35rem;font-size:1.3rem">{{ $basvuru->firma?->unvan }} — Alt İşveren İSG Evrak Yükleme</h1>
            <p style="margin:0 0 1rem;color:#475569;font-size:.87rem">Saha girişi öncesinde firma bilgilerinizi ve zorunlu İSG belgelerinizi (SGK bildirgesi, İSG sözleşmesi, sağlık/eğitim belgeleri) bu ekrandan iletebilirsiniz.</p>

            @if ($errors->any())
                <div class="uyari" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b">
                    @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('taseron.basvuru.kaydet', $basvuru->token) }}" enctype="multipart/form-data">
                @csrf
                <label>Taşeron / Alt İşveren Ticari Unvanı *</label>
                <input type="text" name="unvan" required value="{{ old('unvan', $basvuru->unvan) }}" placeholder="Örn: Yılmaz Elektrik Taahhüt Ltd. Şti.">

                <div class="izgara">
                    <div>
                        <label>Faaliyet / Yapılacak İş *</label>
                        <input type="text" name="faaliyet" required value="{{ old('faaliyet', $basvuru->faaliyet) }}" placeholder="Örn: Elektrik tesisatı, kaynak, cephe">
                    </div>
                    <div>
                        <label>Vergi No</label>
                        <input type="text" name="vergi_no" value="{{ old('vergi_no', $basvuru->vergi_no) }}">
                    </div>
                </div>

                <div class="izgara">
                    <div>
                        <label>Yetkili Ad Soyad *</label>
                        <input type="text" name="yetkili_ad" required value="{{ old('yetkili_ad', $basvuru->yetkili_ad) }}">
                    </div>
                    <div>
                        <label>Telefon *</label>
                        <input type="text" name="telefon" required value="{{ old('telefon', $basvuru->telefon) }}" placeholder="05xx xxx xx xx">
                    </div>
                    <div>
                        <label>E-posta</label>
                        <input type="email" name="eposta" value="{{ old('eposta', $basvuru->eposta) }}">
                    </div>
                </div>

                <div class="izgara" style="margin-top:.5rem">
                    <div>
                        <label>Yüklenen Belge Türü</label>
                        <select name="belge_turu">
                            @foreach ($belgeTurleri as $k => $t)
                                <option value="{{ $k }}">{{ $t['ad'] ?? $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>İSG Evrakları (PDF / Görsel, çoklu seçim)</label>
                        <input type="file" name="belgeler[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                @if (! empty($basvuru->yuklenen_belgeler))
                    <div style="margin-top:.9rem;padding:.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:.82rem">
                        <strong>Daha Önce Yüklenen Evraklar ({{ count($basvuru->yuklenen_belgeler) }}):</strong>
                        <ul style="margin:.4rem 0 0;padding-left:1.2rem">
                            @foreach ($basvuru->yuklenen_belgeler as $blg)
                                <li>{{ $blg['ad'] ?? 'Belge' }} ({{ $belgeTurleri[$blg['tur'] ?? '']['ad'] ?? ($blg['tur'] ?? '') }})</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="margin-top:1.15rem">
                    <button type="submit" class="btn">Başvuruyu ve Evrakları Gönder</button>
                </div>
            </form>
        </div>
    @endif
</main>
</body>
</html>
