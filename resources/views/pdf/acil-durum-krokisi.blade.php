<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { font-size: 10px; color: #555; margin-bottom: 10px; }
    .lejant { margin-top: 10px; }
    .lejant span { display: inline-block; margin-right: 14px; font-size: 10px; }
    .renk { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
</style>
</head>
<body>
    <h1>Acil Durum / Tahliye Krokisi — {{ $firma?->unvan }}</h1>
    <div class="kunye">
        Hazırlanma Tarihi: {{ $kroki->hazirlanma_tarihi?->format('d.m.Y') ?? '—' }}
        &nbsp;·&nbsp; Adres: {{ $firma?->adres ?: '—' }}
    </div>

    <svg viewBox="0 0 1000 700" width="720" height="504">
        @if ($kroki->arka_plan_gorseli)
            <image xlink:href="{{ public_path('storage/'.$kroki->arka_plan_gorseli) }}" x="0" y="0" width="1000" height="700" opacity="0.5" />
        @endif

        <rect x="0" y="0" width="1000" height="700" fill="none" stroke="#ccc" stroke-width="1" />

        @foreach ($kroki->duvarlar ?? [] as $d)
            <line x1="{{ $d['x1'] }}" y1="{{ $d['y1'] }}" x2="{{ $d['x2'] }}" y2="{{ $d['y2'] }}" stroke="#333" stroke-width="4" />
        @endforeach

        @foreach ($kroki->semboller ?? [] as $s)
            @include('filament.pages.partials.kroki-sembol', ['tip' => $s['tip'], 'x' => $s['x'], 'y' => $s['y'], 'etiket' => $s['etiket'] ?? null])
        @endforeach
    </svg>

    <div class="lejant">
        @foreach ($kroki->lejant() as $l)
            <span><span class="renk" style="background:{{ $l['renk'] }}"></span>{{ $l['ad'] }} ({{ $l['adet'] }})</span>
        @endforeach
    </div>
</body>
</html>
