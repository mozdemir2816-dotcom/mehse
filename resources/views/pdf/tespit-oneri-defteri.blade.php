<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 20%; }
    .madde { border: 1px solid #999; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; page-break-inside: avoid; }
    .oncelik { display: inline-block; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 8.5px; margin-bottom: 4px; }
    .tespit { font-weight: bold; font-size: 10.5px; margin-bottom: 3px; }
    .oneri { font-size: 10px; margin-bottom: 3px; }
    .dayanak { font-size: 8.5px; color: #666; }
    .imza { margin-top: 40px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İSG TESPİT VE ÖNERİ DEFTERİ</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr><td>Tarih</td><td>{{ now()->format('d.m.Y') }}</td></tr>
        @if ($defter->genel_not)
            <tr><td>Genel Not</td><td>{{ $defter->genel_not }}</td></tr>
        @endif
    </table>

    @forelse (($defter->maddeler ?? []) as $i => $m)
        @php
            $renk = match ($m['oncelik'] ?? 'orta') { 'yuksek' => '#ef4444', 'dusuk' => '#6b7280', default => '#f59e0b' };
            $etiket = match ($m['oncelik'] ?? 'orta') { 'yuksek' => 'Yüksek Öncelik', 'dusuk' => 'Düşük Öncelik', default => 'Orta Öncelik' };
        @endphp
        <div class="madde">
            <span class="oncelik" style="background:{{ $renk }}">{{ $etiket }}</span>
            <div class="tespit">{{ $i + 1 }}. {{ $m['tespit'] ?? '' }}</div>
            <div class="oneri">Öneri: {{ $m['oneri'] ?? '—' }}</div>
            @if ($m['dayanak'] ?? null)
                <div class="dayanak">Dayanak: {{ $m['dayanak'] }}</div>
            @endif
        </div>
    @empty
        <p style="color:#888">Madde eklenmedi.</p>
    @endforelse

    <table class="imza">
        <tr>
            <td>İş Güvenliği Uzmanı<br>(İmza – Kaşe)</td>
            <td>İşveren / İşveren Vekili<br>(İmza)</td>
        </tr>
    </table>

</div>
</body>
</html>
