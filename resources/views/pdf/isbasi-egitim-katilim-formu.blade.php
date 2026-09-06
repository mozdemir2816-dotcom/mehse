<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 24%; }
    h2 { font-size: 11.5px; margin: 12px 0 4px; color: #0ea5e9; }
    ul.konular { margin: 0 0 8px; padding-left: 1.2rem; font-size: 10px; }
    ul.konular li { margin-bottom: 2px; }
    table.katilim { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9.5px; }
    table.katilim th, table.katilim td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.katilim th { background: #f0f0f0; }
    .imza { margin-top: 24px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞBAŞI / ORYANTASYON EĞİTİM KATILIM FORMU</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Eğitim Tarihi</td><td>{{ $veri['egitim_tarihi'] ?? '—' }}</td>
            <td>Süre</td><td>{{ $veri['sure_saat'] ?? '—' }} saat</td>
        </tr>
        <tr>
            <td>Eğitim Yeri</td><td>{{ $veri['egitim_yeri'] ?? '—' }}</td>
            <td>Eğitim Yöntemi</td><td>{{ $veri['egitim_yontemi'] ?? '—' }}</td>
        </tr>
        <tr>
            <td>Eğitimi Veren</td><td>{{ $veri['egitimi_veren'] ?? '—' }}</td>
            <td>Belge Tarihi</td><td>{{ $veri['belge_tarihi'] ?? '—' }}</td>
        </tr>
    </table>

    @foreach (config('isg.isbasi_egitim.konu_kategorileri') as $kategori => $maddeler)
        <h2>{{ mb_strtoupper($kategori, 'UTF-8') }}</h2>
        <ul class="konular">
            @foreach ($maddeler as $madde)
                @php $isaretli = in_array($madde, $veri['konular'] ?? [], true); @endphp
                <li>{{ $isaretli ? '☑' : '☐' }} {{ $madde }}</li>
            @endforeach
        </ul>
    @endforeach

    @php
        $katilimcilar = array_values($veri['katilimcilar'] ?? []);
        // En az 10 imza satırı olsun diye eksik kalan satırlar boş bırakılır —
        // firmanın çalışan sayısı 10'dan azsa elle tamamlanabilir (aynı desen:
        // egitim-katilim.blade.php).
        $minSatir = max(10, count($katilimcilar));
    @endphp
    <table class="katilim">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th style="width:18%">T.C. No</th><th style="width:20%">Görevi</th><th style="width:20%">İmza</th></tr>
        @for ($i = 0; $i < $minSatir; $i++)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $katilimcilar[$i]['ad_soyad'] ?? '' }}</td>
                <td>{{ $katilimcilar[$i]['tc'] ?? '' }}</td>
                <td>{{ $katilimcilar[$i]['gorev'] ?? '' }}</td>
                <td></td>
            </tr>
        @endfor
    </table>

    <table class="imza">
        <tr>
            <td>{{ $veri['egitimi_veren'] ?? 'Eğitici' }}<br>(İmza)</td>
            <td>
                @if ($veri['igu_imzasi'] ?? false) İş Güvenliği Uzmanı @endif
                @if (($veri['igu_imzasi'] ?? false) && ($veri['isyeri_hekimi_imzasi'] ?? false)) / @endif
                @if ($veri['isyeri_hekimi_imzasi'] ?? false) İşyeri Hekimi @endif
                @if (! ($veri['igu_imzasi'] ?? false) && ! ($veri['isyeri_hekimi_imzasi'] ?? false)) — @endif
                <br>(İmza – Kaşe)
            </td>
        </tr>
    </table>

</div>
</body>
</html>
