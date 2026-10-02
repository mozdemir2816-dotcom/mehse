<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Ziyaretçi Kartı Doğrulama</title>
<style>
    :root { --zemin: #f5f3ff; --kart: #ffffff; --yazi: #1f2937; --soluk: #6b7280; --cizgi: #e5e7eb; }
    @media (prefers-color-scheme: dark) { :root { --zemin: #111827; --kart: #1f2937; --yazi: #f3f4f6; --soluk: #9ca3af; --cizgi: #374151; } }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;
        background: var(--zemin); color: var(--yazi); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .kart { width: 100%; max-width: 420px; background: var(--kart); border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgb(0 0 0 / .12); }
    .durum { text-align: center; padding: 18px; border-radius: 12px; color: #fff; font-size: 1.4rem; font-weight: 800; margin-bottom: 18px; }
    .ok { background: #16a34a; } .bekle { background: #d97706; } .red { background: #dc2626; }
    .satir { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--cizgi); font-size: .95rem; }
    .satir span:first-child { color: var(--soluk); }
    .satir span:last-child { text-align: right; font-weight: 600; }
    .alt { margin-top: 14px; font-size: .78rem; color: var(--soluk); text-align: center; }
</style>
</head>
<body>
<div class="kart">
    @if (! $z)
        <div class="durum red">GEÇERSİZ KART</div>
        <p style="text-align:center">Bu QR koduna ait ziyaretçi kartı bulunamadı.</p>
    @else
        @php
            $d = $z->durum();
            $sinif = match ($d) { 'gecerli', 'icerde' => 'ok', 'planli' => 'bekle', default => 'red' };
            $baslik = match ($d) {
                'gecerli' => 'GEÇERLİ', 'icerde' => 'GEÇERLİ — İÇERİDE', 'planli' => 'HENÜZ GEÇERLİ DEĞİL',
                'cikti' => 'ÇIKIŞ YAPILMIŞ', 'iptal' => 'İPTAL EDİLMİŞ', default => 'SÜRESİ DOLMUŞ',
            };
        @endphp
        <div class="durum {{ $sinif }}">{{ $baslik }}</div>
        <div class="satir"><span>Kart no</span><span>{{ $z->kart_no }}</span></div>
        <div class="satir"><span>Ziyaretçi</span><span>{{ $z->ad_soyad }}</span></div>
        <div class="satir"><span>Kurum</span><span>{{ $z->kurum ?: '—' }}</span></div>
        <div class="satir"><span>İşyeri</span><span>{{ $z->firma?->unvan }}</span></div>
        <div class="satir"><span>Ziyaret edilen</span><span>{{ $z->ziyaret_edilen ?: '—' }}</span></div>
        <div class="satir"><span>Geçerlilik</span><span>{{ $z->gecerlilik_baslangic->format('d.m.Y H:i') }} – {{ $z->gecerlilik_bitis->format('d.m.Y H:i') }}</span></div>
        <div class="satir"><span>İSG bilgilendirmesi</span><span>{{ $z->isg_bilgilendirme ? 'Yapıldı' : 'Yapılmadı' }}</span></div>
        <div class="alt">Doğrulama zamanı: {{ now()->format('d.m.Y H:i') }}</div>
    @endif
</div>
</body>
</html>
