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
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #c2410c; border-bottom: 1px solid #c2410c; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 8px; }
    td, th { border: 1px solid #999; padding: 5px 7px; vertical-align: top; text-align: left; }
    td.k { background: #f3f4f6; font-weight: bold; width: 22%; }
    .kutu { display: inline-block; width: 9px; height: 9px; border: 1px solid #111; margin-right: 5px; text-align: center; line-height: 9px; font-size: 8px; }
    .liste td { border: none; padding: 2px 4px; width: 50%; }
    .imza td { height: 52px; }
    .not { font-size: 8.5px; color: #555; margin-top: 10px; }
</style>
</head>
<body>
@php
    $isaret = fn (string $alan, string $k) => in_array($k, $p->{$alan} ?? [], true) ? '✓' : '';
    $tarih = fn ($d) => $d?->format('d.m.Y') ?? '—';
@endphp
<div class="sayfa">
    <div class="baslik">
        <h1>PATLAMADAN KORUNMA DOKÜMANI — KÜNYE VE ÖZET FORMU</h1>
        <div>{{ $firma?->unvan }}</div>
        <div style="font-size:9.5px;color:#555;margin-top:2px">Doküman No: {{ $p->dokuman_no }} · Revizyon: {{ $p->revizyon_no ?: '—' }} · Durum: {{ $p->durumEtiketi() }}</div>
    </div>

    <h2>1. DOKÜMAN KÜNYESİ</h2>
    <table>
        <tr><td class="k">İşyeri</td><td>{{ $firma?->unvan }}</td><td class="k">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td class="k">Bölüm / Tehlikeli Alan</td><td>{{ $p->bolum }}</td><td class="k">Proses / Faaliyet</td><td>{{ $p->proses ?: '—' }}</td></tr>
        <tr><td class="k">Ortam Türü</td><td>{{ $p->ortamEtiketi() }}</td><td class="k">Doküman Tarihi</td><td>{{ $tarih($p->dokuman_tarihi) }}</td></tr>
        <tr><td class="k">Revizyon No</td><td>{{ $p->revizyon_no ?: '—' }}</td><td class="k">Sonraki Gözden Geçirme</td><td>{{ $tarih($p->sonraki_gozden_gecirme) }}</td></tr>
    </table>

    <h2>2. TEHLİKELİ MADDELER / KARIŞIMLAR</h2>
    <p>{{ $p->tehlikeli_maddeler ?: '—' }}</p>

    <h2>3. TEHLİKELİ BÖLGE SINIFLARI</h2>
    <table class="liste">
        @foreach (collect(config('isg.pkd.zonelar'))->chunk(3) as $satir)
            <tr>@foreach ($satir as $k => $ad)<td><span class="kutu">{{ $isaret('zonelar', $k) }}</span>{{ $ad }}</td>@endforeach</tr>
        @endforeach
    </table>

    <h2>4. MUHTEMEL TUTUŞTURUCU KAYNAKLAR</h2>
    <table class="liste">
        @foreach (collect(config('isg.pkd.tutusturucular'))->chunk(2) as $satir)
            <tr>@foreach ($satir as $k => $ad)<td><span class="kutu">{{ $isaret('tutusturucular', $k) }}</span>{{ $ad }}</td>@endforeach</tr>
        @endforeach
    </table>

    <h2>5. KONTROL VE KORUNMA ÖNLEMLERİ</h2>
    <table class="liste">
        @foreach (collect(config('isg.pkd.onlemler'))->chunk(2) as $satir)
            <tr>@foreach ($satir as $k => $ad)<td><span class="kutu">{{ $isaret('onlemler', $k) }}</span>{{ $ad }}</td>@endforeach</tr>
        @endforeach
    </table>

    @if (filled($p->notlar))
        <h2>6. NOTLAR VE AKSİYONLAR</h2>
        <p>{{ $p->notlar }}</p>
    @endif

    <h2>{{ filled($p->notlar) ? '7' : '6' }}. SORUMLULUK VE ONAY</h2>
    <table class="imza">
        <tr><th>Görev</th><th>Ad Soyad</th><th>İmza</th></tr>
        <tr><td>Sorumlu</td><td>{{ $p->sorumlu }}</td><td></td></tr>
        <tr><td>Hazırlayan</td><td>{{ $p->hazirlayan }}</td><td></td></tr>
        <tr><td>Onaylayan (İşveren)</td><td>{{ $p->onaylayan }}</td><td></td></tr>
    </table>

    <p class="not">
        Bu form PKD'nin künyesini ve özetini gösterir; patlamadan korunma dokümanının teknik içeriğinin
        (bölge sınıflandırma çizimleri, ekipman uygunluğu, hesaplamalar) yerine geçmez.
    </p>
</div>
</body>
</html>
