<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18px 22px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; }
    h1 { font-size: 15px; margin: 0 0 3px; }
    .kunye { font-size: 9.5px; color: #555; margin-bottom: 6px; }
    .lejant { margin-top: 8px; }
    .lejant span { display: inline-block; margin-right: 14px; font-size: 10px; }
    .renk { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }
</style>
</head>
<body>
@php
    $gorsel = $kroki->gorsel_yolu ? storage_path('app/public/'.$kroki->gorsel_yolu) : null;
    $gorsel = $gorsel && is_file($gorsel) ? $gorsel : null;
    $surum2 = ($kroki->antet['surum'] ?? 1) >= 2;
    [$w, $h] = $surum2 ? [config('isg.kroki.genislik'), config('isg.kroki.yukseklik')] : [1000, 700];
@endphp

@if ($gorsel)
    {{-- Editörün kaydettiği görüntü: antet + lejant görüntünün içindedir --}}
    <img src="{{ $gorsel }}" style="width:100%;height:auto;border:1px solid #ccc">
    <div class="kunye" style="margin-top:4px">
        {{ $firma?->unvan }} · Hazırlanma: {{ $kroki->hazirlanma_tarihi?->format('d.m.Y') ?? '—' }}
        @if ($kroki->antet['revizyon'] ?? null) · Rev. {{ $kroki->antet['revizyon'] }} @endif
    </div>
@else
    <h1>Acil Durum / Tahliye Krokisi — {{ $firma?->unvan }}</h1>
    <div class="kunye">
        Hazırlanma Tarihi: {{ $kroki->hazirlanma_tarihi?->format('d.m.Y') ?? '—' }}
        &nbsp;·&nbsp; Adres: {{ $firma?->adres ?: '—' }}
    </div>

    <svg viewBox="0 0 {{ $w }} {{ $h }}" width="760" height="{{ round(760 * $h / $w) }}">
        @if ($kroki->arka_plan_gorseli)
            <image xlink:href="{{ public_path('storage/'.$kroki->arka_plan_gorseli) }}" x="0" y="0" width="{{ $w }}" height="{{ $h }}" opacity="0.5" />
        @endif

        <rect x="0" y="0" width="{{ $w }}" height="{{ $h }}" fill="none" stroke="#ccc" stroke-width="1" />

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
@endif
</body>
</html>
