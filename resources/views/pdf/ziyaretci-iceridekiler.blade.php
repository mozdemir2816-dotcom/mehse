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
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { border: 1px solid #999; padding: 6px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    td.isaret { width: 60px; }
    .ozet { font-size: 11px; margin-bottom: 8px; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="baslik">
        <h1>İÇERİDEKİ ZİYARETÇİLER — ACİL DURUM SAYIM LİSTESİ</h1>
        <div>{{ $firma->unvan }}</div>
        <div style="font-size:9.5px;color:#555;margin-top:2px">Liste zamanı: {{ now()->format('d.m.Y H:i') }}</div>
    </div>

    <p class="ozet"><strong>İçeride kayıtlı ziyaretçi:</strong> {{ $liste->count() }} kişi. Toplanma alanında sayım yapılırken "Sayıldı" sütununu işaretleyin.</p>

    <table>
        <tr><th style="width:4%">#</th><th>Ad Soyad</th><th>Kurum</th><th>Ziyaret Edilen</th><th>Telefon</th><th>Giriş</th><th>Kart No</th><th>Sayıldı</th></tr>
        @forelse ($liste as $i => $z)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $z->ad_soyad }}</td>
                <td>{{ $z->kurum ?: '—' }}</td>
                <td>{{ $z->ziyaret_edilen ?: '—' }}</td>
                <td>{{ $z->telefon ?: '—' }}</td>
                <td>{{ $z->giris_zamani?->format('d.m.Y H:i') }}</td>
                <td>{{ $z->kart_no }}</td>
                <td class="isaret"></td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;color:#888">İçeride kayıtlı ziyaretçi yok.</td></tr>
        @endforelse
    </table>
</div>
</body>
</html>
