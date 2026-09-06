<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { font-family: DejaVu Sans, sans-serif; }
    html, body { margin: 0; padding: 0; }
    .serit { box-sizing: border-box; width: 100%; overflow: hidden; white-space: nowrap;
        border: {{ $cerceveKalinlik }}px solid #b91c1c; border-radius: {{ $cerceveKalinlik * 1.5 }}px;
        background: #fff5f5; padding: {{ $puntoDetay * 0.6 }}px {{ $puntoUnvan * 0.7 }}px; }
    .unvan { font-size: {{ $puntoUnvan }}px; font-weight: bold; color: #991b1b; }
    .detay { font-size: {{ $puntoDetay }}px; color: #7f1d1d; }
</style>
</head>
<body>
    <div class="serit">
        <span class="unvan">{{ $firma->unvan }}</span>
        <span class="detay">&nbsp;&nbsp;·&nbsp;&nbsp;Tehlike Sınıfı: {{ $firma->tehlikeSinifiEtiketi() }} &nbsp;·&nbsp; {{ now()->format('d.m.Y') }}</span>
    </div>
</body>
</html>
