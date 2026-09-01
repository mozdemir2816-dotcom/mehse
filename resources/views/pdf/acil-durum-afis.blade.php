<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; }
    .cerceve { border: 8px solid #b91c1c; margin: 18px; padding: 24px; min-height: 92%; }
    .ust { border: 2px solid #b91c1c; padding: 10px 14px; display: table; width: 100%; }
    .ust .baslik { display: table-cell; font-size: 16px; font-weight: bold; color: #b91c1c; }
    .ust .firma { display: table-cell; text-align: right; font-size: 10px; vertical-align: middle; width: 45%; }
    h1 { text-align: center; font-size: 22px; letter-spacing: 1px; margin: 28px 0; }
    .adim { border: 2px solid #b91c1c; border-radius: 6px; margin: 12px 0; padding: 14px 16px; font-size: 14px; }
    .adim .no { display: inline-block; width: 34px; height: 34px; line-height: 34px; text-align: center;
        background: #b91c1c; color: #fff; border-radius: 50%; font-weight: bold; margin-right: 12px; font-size: 15px; }
    .alt { text-align: center; font-size: 10px; color: #666; margin-top: 24px; }
</style>
</head>
<body>
<div class="cerceve">
    <div class="ust">
        <span class="baslik">⚠ {{ $afis['baslik'] }}</span>
        <span class="firma">{{ $firma?->unvan }}</span>
    </div>

    <h1>{{ $afis['ad'] }}</h1>

    @foreach ($afis['adimlar'] as $i => $adim)
        <div class="adim"><span class="no">{{ $i + 1 }}</span>{{ $adim }}</div>
    @endforeach

    <div class="alt">
        Acil Servis: 112 &nbsp;|&nbsp; İtfaiye: 110 &nbsp;|&nbsp; UZEM: 114<br>
        Bu talimat işyerine görünür şekilde asılmalıdır. — 6331 Sayılı İSG Kanunu
    </div>
</div>
</body>
</html>
