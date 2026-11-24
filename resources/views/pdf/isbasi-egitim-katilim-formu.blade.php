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
    .kunye td:nth-child(odd) { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 11.5px; margin: 12px 0 4px; color: #0ea5e9; }
    ul.konular { margin: 0 0 8px; padding-left: 1.2rem; font-size: 10px; }
    ul.konular li { margin-bottom: 2px; }
    table.katilim { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9.5px; }
    table.katilim thead { display: table-header-group; }
    table.katilim tr { page-break-inside: avoid; }
    table.katilim th, table.katilim td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.katilim th { background: #f0f0f0; }
    .imza { margin-top: 18px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .imza td { width: 33.33%; text-align: center; border: 1px solid #999; padding: 6px 5px 34px; font-size: 9px; vertical-align: top; }
    .imza .rol { font-weight: bold; background: #f0f0f0; display: block; margin: -6px -5px 6px; padding: 3px; border-bottom: 1px solid #999; }
    .yasal { margin-top: 8px; font-size: 8.5px; color: #475569; text-align: center; }
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
            <td>Eğitim Tarihi / Saat</td><td>{{ $veri['egitim_tarihi'] ?? '—' }} · Saat: ....:.... – ....:....</td>
            <td>Süre</td><td>{{ $veri['sure_saat'] ?? '—' }} saat (Yön. m.7 asgari 2 saat)</td>
        </tr>
        <tr>
            <td>Eğitim Yeri</td><td>{{ $veri['egitim_yeri'] ?? '—' }}</td>
            <td>Eğitim Yöntemi</td><td>{{ $veri['egitim_yontemi'] ?? 'Uygulamalı ve Yüz Yüze' }}</td>
        </tr>
        <tr>
            <td>Eğitimi Veren</td><td>{{ $veri['egitimi_veren'] ?? '—' }}</td>
            <td>Belge Tarihi</td><td>{{ $veri['belge_tarihi'] ?? '—' }}</td>
        </tr>
    </table>

    @foreach (config('isg.isbasi_egitim.konu_kategorileri') as $kategori => $maddeler)
        <h2>{{ \App\Support\TurkceMetin::buyuk($kategori) }}</h2>
        <ul class="konular">
            @foreach ($maddeler as $madde)
                @php $isaretli = in_array($madde, $veri['konular'] ?? [], true); @endphp
                <li>{{ $isaretli ? '☑' : '☐' }} {{ $madde }}</li>
            @endforeach
        </ul>
    @endforeach

    @php
        $katilimcilar = array_values($veri['katilimcilar'] ?? []);
        $minSatir = max(10, count($katilimcilar));
    @endphp
    <table class="katilim">
        <thead>
            <tr><th style="width:5%">#</th><th>Ad Soyad</th><th style="width:18%">T.C. No</th><th style="width:20%">Görevi</th><th style="width:20%">İmza</th></tr>
        </thead>
        <tbody>
        @for ($i = 0; $i < $minSatir; $i++)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $katilimcilar[$i]['ad_soyad'] ?? '' }}</td>
                <td>{{ $katilimcilar[$i]['tc'] ?? '' }}</td>
                <td>{{ $katilimcilar[$i]['gorev'] ?? '' }}</td>
                <td></td>
            </tr>
        @endfor
        </tbody>
    </table>

    <table class="imza">
        <tr>
            <td><span class="rol">EĞİTİMİ VEREN</span>{{ $veri['egitimi_veren'] ?? 'Eğitici' }}</td>
            <td>
                <span class="rol">İGU / İŞYERİ HEKİMİ</span>
                @if ($veri['igu_imzasi'] ?? false) İGU: {{ $firma?->igu?->ad_soyad ?: 'İş Güvenliği Uzmanı' }} @endif
                @if (($veri['igu_imzasi'] ?? false) && ($veri['isyeri_hekimi_imzasi'] ?? false))<br>@endif
                @if ($veri['isyeri_hekimi_imzasi'] ?? false) İH: {{ $firma?->isyeriHekimi?->ad_soyad ?: 'İşyeri Hekimi' }} @endif
                @if (! ($veri['igu_imzasi'] ?? false) && ! ($veri['isyeri_hekimi_imzasi'] ?? false)) — @endif
            </td>
            <td><span class="rol">İŞVEREN / VEKİLİ (ONAY)</span>{{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}</td>
        </tr>
    </table>

    <p class="yasal">
        Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026 / 33212, m.7) gereğince işe başlama eğitimi fiilen çalışmaya başlamadan önce en az 2 saat uygulamalı ve yüz yüze verilir ve temel eğitim süresinden sayılmaz.
    </p>

</div>
</body>
</html>
