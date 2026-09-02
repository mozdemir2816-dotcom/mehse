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
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 5px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .durum-tamamlandi { color: #15803d; font-weight: bold; }
    .durum-planlandi { color: #b45309; font-weight: bold; }
    .durum-bos { color: #9ca3af; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>{{ $program->yil }} YILI SAHA ZİYARET PROGRAMI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="liste">
        <tr>
            <th style="width:10%">Ay</th>
            <th style="width:14%">Planlanan Tarih</th>
            <th style="width:30%">Amaç / Kapsam</th>
            <th style="width:10%">Süre (saat)</th>
            <th style="width:12%">Durum</th>
            <th>Notlar</th>
        </tr>
        @foreach (\App\Models\ZiyaretProgrami::AYLAR as $i => $ayAdi)
            @php $z = $program->ziyaretler[$i] ?? []; @endphp
            <tr>
                <td>{{ $ayAdi }}</td>
                <td>{{ ! empty($z['tarih']) ? \Illuminate\Support\Carbon::parse($z['tarih'])->format('d.m.Y') : '—' }}</td>
                <td>{{ $z['amac'] ?? '—' }}</td>
                <td>{{ $z['sure_saat'] ?? '—' }}</td>
                <td class="durum-{{ $z['durum'] ?? 'bos' }}">
                    {{ match ($z['durum'] ?? 'bos') { 'tamamlandi' => 'Tamamlandı', 'planlandi' => 'Planlandı', default => 'Boş' } }}
                </td>
                <td>{{ $z['notlar'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

</div>
</body>
</html>
