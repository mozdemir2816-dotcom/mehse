<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{--
    Talimat Teslim Tutanağı — kullanıcının "Talimat İçerikleri.xlsx" tutanağından
    birebir metin (10.10.2026). Üretici: App\Support\TalimatListesiUretici::pdf()
--}}
<style>
    @page { margin: 34px 46px 44px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; line-height: 1.5; }
    table.ust { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.ust td { border: 1px solid #222; vertical-align: middle; padding: 6px 8px; height: 70px; }
    table.ust .logo { width: 30%; text-align: center; }
    table.ust .logo img { max-width: 170px; max-height: 64px; }
    table.ust .ad { text-align: center; font-size: 14px; font-weight: bold; }
    table.ust .bilgi { width: 27%; font-size: 9.5px; line-height: 1.6; }
    table.liste { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.liste th { background: #d9d9d9; border: 1px solid #222; padding: 5px; font-size: 11px; }
    table.liste td { border: 1px solid #222; padding: 4px 6px; }
    table.liste td.no { width: 7%; text-align: center; }
    p { margin: 0 0 8px; text-align: justify; }
    ul { margin: 0 0 8px; padding-left: 26px; }
    ul li { margin-bottom: 3px; text-align: justify; }
    table.imza { width: 100%; margin-top: 28px; page-break-inside: avoid; }
    table.imza td { width: 50%; vertical-align: top; padding: 0 10px; line-height: 1.8; }
    table.imza img { max-height: 52px; max-width: 120px; margin-right: 6px; }
    .altbilgi { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 8px; color: #666; text-align: center; }
</style>
</head>
<body>

<div class="altbilgi">Talimat Teslim Tutanağı · {{ $firma->unvan }} · {{ $tarih }}</div>

<table class="ust">
    <tr>
        <td class="logo">@if ($logo)<img src="{{ $logo }}">@else<strong>{{ $firma->unvan }}</strong>@endif</td>
        <td class="ad">TALİMAT TESLİM TUTANAĞI</td>
        <td class="bilgi">
            <strong>İşyeri:</strong> {{ $firma->unvan }}<br>
            <strong>Tarih:</strong> {{ $tarih }}<br>
            <strong>Talimat sayısı:</strong> {{ $talimatlar->count() }}
        </td>
    </tr>
</table>

<table class="liste">
    <tr><th colspan="2">TALİMATLAR</th></tr>
    @forelse ($talimatlar as $i => $t)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $t->baslik }}</td>
        </tr>
    @empty
        <tr><td colspan="2" style="color:#888">Bu firmaya henüz talimat tanımlanmadı.</td></tr>
    @endforelse
</table>

<p>{{ \App\Support\TalimatListesiUretici::GIRIS }}</p>
<p>{{ \App\Support\TalimatListesiUretici::ISVEREN_OLARAK }}</p>
<ul>
    @foreach (\App\Support\TalimatListesiUretici::YUKUMLULUKLER as $y)
        <li>{{ $y }}</li>
    @endforeach
</ul>
<p>{{ \App\Support\TalimatListesiUretici::KAPANIS }}</p>

<table class="imza">
    <tr>
        <td>
            <strong>Teslim Eden</strong><br>
            Adı Soyadı: {{ $teslimEden->ad }}<br>
            Unvanı: {{ $teslimEden->unvan }}<br>
            İmza:
        </td>
        <td>
            <strong>Teslim Alan</strong><br>
            Adı Soyadı: {{ $firma->isveren_ad ?: '………………………………………' }}<br>
            Unvanı: İşveren / Yetkili<br>
            İmza:
        </td>
    </tr>
</table>

</body>
</html>
