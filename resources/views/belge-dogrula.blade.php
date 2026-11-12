<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Eğitim Belgesi Doğrulama</title>
<style>
    :root { --zemin: #eff6ff; --kart: #ffffff; --yazi: #1f2937; --soluk: #6b7280; --cizgi: #e5e7eb; }
    @media (prefers-color-scheme: dark) { :root { --zemin: #111827; --kart: #1f2937; --yazi: #f3f4f6; --soluk: #9ca3af; --cizgi: #374151; } }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;
        background: var(--zemin); color: var(--yazi); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .kart { width: 100%; max-width: 440px; background: var(--kart); border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgb(0 0 0 / .12); }
    .durum { text-align: center; padding: 18px; border-radius: 12px; color: #fff; font-size: 1.3rem; font-weight: 800; margin-bottom: 18px; }
    .ok { background: #15803d; } .bekle { background: #b45309; } .red { background: #dc2626; }
    .satir { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--cizgi); font-size: .95rem; }
    .satir span:first-child { color: var(--soluk); }
    .satir span:last-child { text-align: right; font-weight: 600; }
    .alt { margin-top: 14px; font-size: .78rem; color: var(--soluk); text-align: center; }
</style>
</head>
<body>
<div class="kart">
    @if (! $a)
        <div class="durum red">BELGE BULUNAMADI</div>
        <p style="text-align:center">Bu kodla eşleşen bir uzaktan eğitim belgesi kaydı yok.</p>
    @else
        @php
            // KVKK: ad soyad maskelenir (ilk harf + yıldız), TC kimlik no hiç gösterilmez.
            $maske = fn (?string $s) => collect(preg_split('/\s+/u', trim((string) $s)))->filter()
                ->map(fn ($k) => mb_substr($k, 0, 1).str_repeat('*', max(2, mb_strlen($k) - 1)))->implode(' ');
            $gecerli = $a->belgeAcikMi();
        @endphp
        <div class="durum {{ $gecerli ? 'ok' : 'bekle' }}">{{ $gecerli ? 'BELGE GEÇERLİ' : 'EĞİTİM TAMAMLANMAMIŞ' }}</div>
        <div class="satir"><span>Çalışan</span><span>{{ $maske($a->calisan?->ad_soyad) }}</span></div>
        <div class="satir"><span>İşyeri</span><span>{{ $a->calisan?->firma?->unvan ?? '—' }}</span></div>
        <div class="satir"><span>Eğitim</span><span>{{ $a->paket?->ad }}</span></div>
        <div class="satir"><span>Eğitim türü</span><span>{{ $a->turEtiketi() }}</span></div>
        <div class="satir"><span>Tamamlanma</span><span>{{ $a->tamamlandi_at?->format('d.m.Y') ?? '—' }}</span></div>
        <div class="satir"><span>Belge no</span><span>UE-{{ $a->id }}</span></div>
        <div class="alt">Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026) kapsamında üretilmiştir.</div>
    @endif
</div>
</body>
</html>
