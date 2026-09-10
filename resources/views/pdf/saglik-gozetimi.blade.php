{{-- Sağlık Gözetimi Takip Çizelgesi — 6331 s.K. Md.15 ve İşyeri Hekimi Yön.
     A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 10px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #777; padding: 3px 4px; vertical-align: top; }
    table.liste th { background: #eee; text-align: left; font-size: 7.5px; }
    .s-uygun { color: #15803d; font-weight: bold; }
    .s-uygun_degil { color: #b91c1c; font-weight: bold; }
    .s-sartli { color: #b45309; font-weight: bold; }
    .s-bekliyor { color: #6b7280; }
    .gecti { color: #b91c1c; font-weight: bold; }
    .yasal { margin-top: 10px; font-size: 7.5px; color: #555; }
    .imza { margin-top: 24px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 50%; }
</style>
</head>
<body>

<h1>SAĞLIK GÖZETİMİ TAKİP ÇİZELGESİ</h1>
<div class="alt">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 15 uyarınca çalışanların sağlık gözetimi</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">Tehlike Sınıfı</td><td>{{ $firma?->tehlikeSinifiEtiketi() ?? '—' }}</td>
        <td class="e">Liste Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    @if ($gozetim->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $gozetim->genel_not }}</td></tr>
    @endif
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:16px">#</th>
            <th>Çalışan</th>
            <th style="width:90px">Görev</th>
            <th>Sağlık Tetkiki</th>
            <th style="width:60px">Tetkik Tarihi</th>
            <th style="width:60px">Sonraki Tetkik</th>
            <th style="width:90px">Sonuç</th>
            <th style="width:70px">Rapor No</th>
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
        @php $bugun = \Illuminate\Support\Carbon::today(); @endphp
        @forelse ($gozetim->satirlar ?? [] as $i => $s)
            @php
                $sonraki = !empty($s['sonraki_tarih']) ? \Illuminate\Support\Carbon::parse($s['sonraki_tarih']) : null;
                $gecmis = $sonraki && $sonraki->lt($bugun);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $s['calisan_adi'] ?? '' }}</td>
                <td>{{ $s['gorev'] ?? '—' }}</td>
                <td>{{ $turler[$s['tetkik_turu']]['ad'] ?? $s['tetkik_turu'] }}</td>
                <td>{{ !empty($s['tarih']) ? \Illuminate\Support\Carbon::parse($s['tarih'])->format('d.m.Y') : '—' }}</td>
                <td class="{{ $gecmis ? 'gecti' : '' }}">{{ $sonraki ? $sonraki->format('d.m.Y') : '—' }}</td>
                <td class="s-{{ $s['sonuc'] ?? 'bekliyor' }}">{{ $sonuclar[$s['sonuc'] ?? 'bekliyor'] ?? '' }}</td>
                <td>{{ $s['rapor_no'] ?? '—' }}</td>
                <td>{{ $s['not'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center;color:#888">Kayıt girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    İşe giriş ve periyodik muayeneler işyeri hekimi tarafından EK-2 formuna uygun yapılır.
    Periyodik muayene aralığı: az tehlikeli en geç 5, tehlikeli 3, çok tehlikeli 1 yıl (hekim
    daha sık isteyebilir). Kırmızı "Sonraki Tetkik" tarihi geçmiş; tetkik yenilenmelidir.
</p>

<table class="imza">
    <tr>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
