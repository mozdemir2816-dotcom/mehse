<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Ziyaretçi İSG Brifing &amp; Kart Doğrulama</title>
<style>
    :root { --zemin: #f5f3ff; --kart: #ffffff; --yazi: #1f2937; --soluk: #6b7280; --cizgi: #e5e7eb; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;
        background: var(--zemin); color: var(--yazi); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .kart { width: 100%; max-width: 520px; background: var(--kart); border-radius: 16px; padding: 22px; box-shadow: 0 10px 30px rgb(0 0 0 / .12); }
    .durum { text-align: center; padding: 16px; border-radius: 12px; color: #fff; font-size: 1.25rem; font-weight: 800; margin-bottom: 16px; }
    .ok { background: #15803d; } .bekle { background: #b45309; } .red { background: #dc2626; }
    .satir { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--cizgi); font-size: .9rem; }
    .satir span:first-child { color: var(--soluk); }
    .satir span:last-child { text-align: right; font-weight: 600; }
    .brifing { margin-top: 16px; padding: 14px; border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 12px; font-size: .86rem; }
    .soru { margin-top: 12px; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
    .soru label { display: block; margin-top: 6px; cursor: pointer; font-size: .84rem; }
    .btn { width: 100%; margin-top: 14px; padding: 11px; border: none; border-radius: 10px; background: #2563eb; color: #fff; font-weight: 700; font-size: .95rem; cursor: pointer; }
    .uyari { padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: .84rem; }
    .alt { margin-top: 14px; font-size: .76rem; color: var(--soluk); text-align: center; }
</style>
</head>
<body>
<div class="kart">
    @if (! $z)
        <div class="durum red">GEÇERSİZ KART</div>
        <p style="text-align:center">Bu QR koduna ait ziyaretçi kartı bulunamadı.</p>
    @else
        @php
            $egitimOk = $z->egitimTamamlandiMi();
            $d = $z->durum();
            $sinif = ! $egitimOk ? 'bekle' : match ($d) { 'gecerli', 'icerde' => 'ok', 'planli' => 'bekle', default => 'red' };
            $baslik = ! $egitimOk
                ? 'İSG BRİFİNG & TESTİ BEKLENİYOR'
                : match ($d) {
                    'gecerli' => 'GEÇERLİ — GİRİŞ ONAYLI', 'icerde' => 'GEÇERLİ — İÇERİDE', 'planli' => 'HENÜZ GEÇERLİ DEĞİL',
                    'cikti' => 'ÇIKIŞ YAPILMIŞ', 'iptal' => 'İPTAL EDİLMİŞ', default => 'SÜRESİ DOLMUŞ',
                };
        @endphp

        @if (session('test_ok'))
            <div class="uyari" style="background:#dcfce7;border:1px solid #86efac;color:#166534">✓ {{ session('test_ok') }}</div>
        @endif
        @if (session('test_hata'))
            <div class="uyari" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b">⚠ {{ session('test_hata') }}</div>
        @endif

        <div class="durum {{ $sinif }}">{{ $baslik }}</div>
        <div class="satir"><span>Kart no</span><span>{{ $z->kart_no }}</span></div>
        <div class="satir"><span>Ziyaretçi</span><span>{{ $z->ad_soyad }}</span></div>
        <div class="satir"><span>Kurum</span><span>{{ $z->kurum ?: '—' }}</span></div>
        <div class="satir"><span>İşyeri</span><span>{{ $z->firma?->unvan }}</span></div>
        <div class="satir"><span>Ziyaret edilen</span><span>{{ $z->ziyaret_edilen ?: '—' }}</span></div>
        <div class="satir"><span>Geçerlilik</span><span>{{ $z->gecerlilik_baslangic->format('d.m.Y H:i') }} – {{ $z->gecerlilik_bitis->format('d.m.Y H:i') }}</span></div>
        <div class="satir">
            <span>İSG Brifing &amp; Testi</span>
            <span style="color:{{ $egitimOk ? '#15803d' : '#b45309' }}">
                {{ $egitimOk ? 'Tamamlandı'.($z->test_puani ? ' (%'.$z->test_puani.')' : '') : 'Tamamlanmadı' }}
            </span>
        </div>

        @if (! $egitimOk && ! $z->iptal && $z->cikis_zamani === null)
            <div class="brifing">
                <strong>🛡️ Giriş Öncesi Zorunlu İSG Bilgilendirmesi &amp; Bilgi Testi</strong>
                <p style="margin:.4rem 0">Tesis giriş onayının açılması için aşağıdaki kuralları okuyup kısa bilgi testini tamamlayın:</p>
                <ul style="margin:.35rem 0;padding-left:1.15rem;line-height:1.45">
                    <li>Tesis boyunca refakatçinizden ayrılmayın ve yaya yollarını kullanın.</li>
                    <li>Belirtilen KKD'leri (baret, yelek, gözlük) eksiksiz takın.</li>
                    <li>Acil alarmda asansör kullanmadan en yakın çıkıştan toplanma alanına geçin.</li>
                    <li>İzinsiz fotoğraf/video çekimi ve makinelere temas yasaktır.</li>
                </ul>
                @if ($z->brifing_video_url && preg_match('#^https?://#i', $z->brifing_video_url))
                    <div style="margin:.6rem 0">
                        <a href="{{ $z->brifing_video_url }}" target="_blank" rel="noopener noreferrer" style="color:#1d4ed8;font-weight:700;text-decoration:underline">▶ Tesis Güvenlik Brifing Videosunu İzle</a>
                    </div>
                @endif

                <form method="POST" action="{{ route('ziyaretci.test', $z->token) }}">
                    @csrf
                    @foreach (\App\Models\Ziyaretci::BRIFING_SORULARI as $i => $s)
                        <div class="soru">
                            <strong>{{ $i + 1 }}. {{ $s['soru'] }}</strong>
                            @foreach ($s['secenekler'] as $harf => $metin)
                                <label>
                                    <input type="radio" name="cevaplar[{{ $i }}]" value="{{ $harf }}" required>
                                    {{ $metin }}
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                    <label style="display:flex;gap:8px;align-items:flex-start;margin-top:12px;font-size:.82rem;cursor:pointer">
                        <input type="checkbox" name="kvkk_onay" value="1" required style="margin-top:2px">
                        <span>İşyeri İSG kurallarını okudum, anladım ve KVKK aydınlatma metnini onaylıyorum.</span>
                    </label>
                    <button type="submit" class="btn">Testi Tamamla ve Giriş Onayını Aç</button>
                </form>
            </div>
        @endif

        <div class="alt">Doğrulama zamanı: {{ now()->format('d.m.Y H:i') }}</div>
    @endif
</div>
</body>
</html>
