<!doctype html>
<html lang="tr"><head><meta charset="utf-8">@include('pdf.ekipman._stil')
<style>
    @page { margin: 9mm 9mm 10mm; }
    table.matris { margin-top: 5px; table-layout: fixed; font-size: 7.2px; }
    table.matris thead { display: table-header-group; }
    table.matris th { background: #0A345A; color: #fff; border: 1px solid #0A345A; padding: 3px 0; text-align: center; font-size: 6.8px; }
    table.matris td { border: 1px solid #94a3b8; padding: 2.4px 3px; height: 13px; }
    table.matris td.g { text-align: center; padding: 0; }
    table.matris td.no { text-align: center; }
    table.matris tr:nth-child(even) td { background: #f8fafc; }
    table.matris td.hafta, table.matris th.hafta { border-left: 1.6px solid #0A345A; }
    table.matris tr.imza td { height: 22px; background: #fff; font-weight: bold; }
</style>
</head>
<body>
@include('pdf.ekipman._antet', ['baslik' => 'AYLIK OPERATÖR GÜNLÜK KONTROL ÇİZELGESİ'])
<table class="kunye">
    <tr><td class="e">Ay / yıl</td><td>{{ $ayAdi }}</td><td class="e">Form</td><td>{{ $form['ad'] }}</td>
        <td class="e">İşaretleme</td><td>✓ Uygun &nbsp; X Uygun değil &nbsp; – Uygulanamaz / çalışılmadı</td></tr>
</table>
<table class="matris">
    <thead>
        <tr>
            <th style="width:2.6%">No</th>
            <th style="width:29%">Kontrol maddesi</th>
            @for ($g = 1; $g <= 31; $g++)<th @class(['hafta' => $g % 7 === 1 && $g > 1])>{{ $g }}</th>@endfor
        </tr>
    </thead>
    <tbody>
        @foreach ($form['sorular'] as $i => $soru)
            <tr>
                <td class="no">{{ $i + 1 }}</td>
                <td>{{ \Illuminate\Support\Str::limit($soru, 95) }}</td>
                @for ($g = 1; $g <= 31; $g++)<td class="g @if ($g % 7 === 1 && $g > 1) hafta @endif" @if ($g > $gunSayisi) style="background:#cbd5e1" @endif></td>@endfor
            </tr>
        @endforeach
        <tr class="imza">
            <td colspan="2">Operatör paraf (günlük)</td>
            @for ($g = 1; $g <= 31; $g++)<td class="g @if ($g % 7 === 1 && $g > 1) hafta @endif" @if ($g > $gunSayisi) style="background:#cbd5e1" @endif></td>@endfor
        </tr>
    </tbody>
</table>
<div class="uyari">UYGUNSUZLUK TESPİT EDİLİRSE MAKİNEYİ KULLANMAYIN, BAKIM SORUMLUSUNA VE İSG BİRİMİNE HEMEN BİLDİRİN. 
    <br><span style="font-weight:normal">Uygunsuzluk telefondan da bildirilebilir: sağ üstteki QR kodu okutun.</span></div>
<table class="imza">
    <tr><th>Ay sonu — Operatör</th><th>Ay sonu — Bakım sorumlusu{{ $e->bakim_sorumlusu ? ': '.$e->bakim_sorumlusu : '' }}</th><th>İş Güvenliği Uzmanı — kontrol</th></tr>
    <tr style="height:40px"><td>Adı soyadı / İmza:</td><td>Adı soyadı / İmza:</td><td>{{ $tebligEden->ad }}<br>İmza:</td></tr>
</table>
</body></html>
