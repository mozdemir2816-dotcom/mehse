<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .sayfa { padding: 20px 26px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    table.plan { width: 100%; border-collapse: collapse; font-size: 8px; }
    table.plan th, table.plan td { border: 1px solid #999; padding: 3px 4px; text-align: center; }
    table.plan th { background: #f0f0f0; }
    table.plan td.faaliyet { text-align: left; font-weight: bold; width: 14%; }
    table.plan td.sorumlu { text-align: left; width: 8%; }
    table.plan td.aciklama { text-align: left; width: 20%; font-size: 7.5px; }
    .durum { width: 16px; height: 16px; display: inline-block; border-radius: 2px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>YILLIK ÇALIŞMA PLANI — {{ $plan->yil }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="plan">
        <tr>
            <th>Faaliyet</th><th>Sorumlu</th><th>Açıklama</th>
            @foreach ($aylar as $ay)
                <th>{{ $ay }}</th>
            @endforeach
        </tr>
        @forelse (($plan->faaliyetler ?? []) as $f)
            <tr>
                <td class="faaliyet">{{ $f['faaliyet'] }}</td>
                <td class="sorumlu">{{ $f['sorumlu'] ?? '—' }}</td>
                <td class="aciklama">{{ $f['aciklama'] ?? '' }}</td>
                @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $durum)
                    @php
                        $renk = match ($durum) { 'tamamlandi' => '#10b981', 'planlandi' => '#f59e0b', default => '#374151' };
                    @endphp
                    <td><span class="durum" style="background:{{ $renk }}"></span></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="15" style="color:#888">Faaliyet eklenmedi.</td></tr>
        @endforelse
    </table>

    <p style="font-size:8px;color:#666;margin-top:10px">
        <span class="durum" style="background:#374151"></span> Boş &nbsp;
        <span class="durum" style="background:#f59e0b"></span> Planlandı &nbsp;
        <span class="durum" style="background:#10b981"></span> Tamamlandı
    </p>

</div>
</body>
</html>
