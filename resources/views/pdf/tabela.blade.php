<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12mm; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .ust { border-bottom: 1px solid #999; padding-bottom: 4px; margin-bottom: 8mm; font-size: 9px; color: #444; }
    .ust strong { color: #111; font-size: 11px; }
    .levha { text-align: center; }
    .levha img { width: {{ $genislikMm }}mm; height: {{ $yukseklikMm }}mm; }
    .alt { margin-top: 6mm; font-size: 8.5px; color: #555; text-align: center; }
</style>
</head>
<body>
    <div class="ust">
        <strong>{{ $baslik }}</strong>
        @if ($firma) — {{ $firma }} @endif
    </div>

    <div class="levha"><img src="{{ $svgUri }}" alt="Levha"></div>

    <div class="alt">
        Ölçü: {{ $genislikMm }} × {{ $yukseklikMm }} mm ({{ $olcekMetni }}) · Tasarım boyutu {{ $pikselGenislik }} × {{ $pikselYukseklik }} px · {{ now()->format('d.m.Y') }}
    </div>
</body>
</html>
