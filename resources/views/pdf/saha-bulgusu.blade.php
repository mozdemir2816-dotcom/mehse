<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #7c3aed; border-bottom: 1px solid #7c3aed; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 8px; }
    td, th { border: 1px solid #999; padding: 5px 7px; vertical-align: top; text-align: left; }
    td.k { background: #f3f4f6; font-weight: bold; width: 22%; }
    .rozet { display: inline-block; padding: 2px 8px; border-radius: 8px; color: #fff; font-weight: bold; }
    .foto { width: 48%; display: inline-block; margin: 0 1% 8px 0; vertical-align: top; }
    .foto img { width: 100%; max-height: 300px; }
    .imza td { height: 52px; border: none; width: 50%; text-align: center; vertical-align: bottom; }
    .imza span { display: block; border-top: 1px solid #111; margin: 0 24px; padding-top: 3px; }
</style>
</head>
<body>
@php
    $skor = $b->skor();
    $renk = match (true) { $skor >= 16 => '#b91c1c', $skor >= 10 => '#d97706', $skor >= 5 => '#ca8a04', default => '#16a34a' };
@endphp
<div class="sayfa">
    <div class="baslik">
        <h1>SAHA TESPİT TUTANAĞI</h1>
        <div>{{ $firma?->unvan }}</div>
        <div style="font-size:9.5px;color:#555;margin-top:2px">Bulgu No: {{ $b->bulgu_no }} · {{ $b->created_at?->format('d.m.Y H:i') }}</div>
    </div>

    <h2>1. YER VE TEHLİKE</h2>
    <table>
        <tr><td class="k">Bölüm / Saha Alanı</td><td>{{ $b->bolum ?: '—' }}</td><td class="k">Gözlem Konumu</td><td>{{ $b->gozlem_konumu ?: '—' }}</td></tr>
        <tr><td class="k">Tehlike Kategorisi</td><td>{{ $b->kategori ?: '—' }}</td><td class="k">Tehlike</td><td>{{ $b->tehlike ?: '—' }}</td></tr>
        @if ($b->konumLinki())
            <tr><td class="k">GPS</td><td colspan="3">{{ $b->enlem }}, {{ $b->boylam }}</td></tr>
        @endif
    </table>

    <h2>2. UYGUNSUZLUK</h2>
    <p>{{ $b->uygunsuzluk }}</p>
    @if (filled($b->mevcut_onlemler))
        <p><strong>Mevcut önlemler:</strong> {{ $b->mevcut_onlemler }}</p>
    @endif

    <h2>3. RİSK (5×5)</h2>
    <table>
        <tr>
            <td class="k">Olasılık</td><td>{{ $b->olasilik }} — {{ config('isg.saha_bulgu.olasilik.'.$b->olasilik) }}</td>
            <td class="k">Şiddet</td><td>{{ $b->siddet }} — {{ config('isg.saha_bulgu.siddet.'.$b->siddet) }}</td>
        </tr>
        <tr><td class="k">Risk Skoru</td><td colspan="3"><span class="rozet" style="background:{{ $renk }}">{{ $skor }} — {{ $b->seviyeEtiketi() }}</span></td></tr>
    </table>

    <h2>4. DÜZELTİCİ FAALİYET</h2>
    <table>
        <tr><td class="k">Aksiyon</td><td colspan="3">{{ $b->aksiyon ?: '—' }}</td></tr>
        <tr><td class="k">Sorumlu</td><td>{{ $b->sorumlu ?: '—' }}</td><td class="k">Termin</td><td>{{ $b->termin?->format('d.m.Y') ?? '—' }}</td></tr>
        <tr>
            <td class="k">Durum</td><td>{{ config('isg.saha_bulgu.durumlar.'.$b->durum) }}</td>
            <td class="k">Kapanış</td><td>{{ $b->kapanis_tarihi?->format('d.m.Y') ?? '—' }}{{ filled($b->kapanis_notu) ? ' — '.$b->kapanis_notu : '' }}</td>
        </tr>
    </table>

    @if (filled($b->fotograflar))
        <h2>5. FOTOĞRAF KANITI</h2>
        @foreach ($b->fotograflar as $foto)
            @if (is_file(storage_path('app/public/'.$foto)))
                <div class="foto"><img src="{{ storage_path('app/public/'.$foto) }}"></div>
            @endif
        @endforeach
    @endif

    <table class="imza">
        <tr>
            <td><span>&nbsp;<br>Tespit Eden</span></td>
            <td><span>{{ $b->sorumlu ?: 'Sorumlu' }}<br>Bölüm Sorumlusu</span></td>
        </tr>
    </table>
</div>
</body>
</html>
