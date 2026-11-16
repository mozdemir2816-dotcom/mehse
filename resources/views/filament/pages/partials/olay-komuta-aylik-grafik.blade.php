{{-- Aylık olay (çubuk) + kayıtlı yaralanma (çizgi) — saf SVG. $aylik: OlayKomuta::aylik() --}}
@php
    $en = max(4, collect($aylik)->max('olay'));
    $g = 600; $y = 190; $sol = 26; $alan = $g - $sol - 6; $adim = $alan / 12; $kalin = $adim * .56;
    $nokta = [];
    foreach ($aylik as $i => $a) {
        $nokta[] = round($sol + $adim * $i + $adim / 2, 1).','.round($y - ($a['yaralanma'] / $en) * ($y - 14), 1);
    }
    $ozet = collect($aylik)->map(fn ($a) => $a['kisa'].': '.$a['olay'].' olay, '.$a['yaralanma'].' yaralanma')->implode('; ');
@endphp
<svg viewBox="0 0 {{ $g }} 218" width="100%" role="img" aria-label="Aylık olaylar ve yaralanmalar. {{ $ozet }}" style="display:block;max-height:260px">
    @foreach ([0, .5, 1] as $oran)
        @php $cy = $y - $oran * ($y - 14); @endphp
        <line x1="{{ $sol }}" x2="{{ $g - 6 }}" y1="{{ $cy }}" y2="{{ $cy }}" stroke="rgb(107 114 128 / .22)" stroke-width="1"/>
        <text x="{{ $sol - 5 }}" y="{{ $cy + 3.5 }}" text-anchor="end" font-size="10" fill="currentColor" opacity=".65">{{ round($en * $oran) }}</text>
    @endforeach
    @foreach ($aylik as $i => $a)
        @php
            $yuk = ($a['olay'] / $en) * ($y - 14);
            $bx = $sol + $adim * $i + ($adim - $kalin) / 2;
        @endphp
        <rect x="{{ round($bx, 1) }}" y="{{ round($y - $yuk, 1) }}" width="{{ round($kalin, 1) }}" height="{{ round($yuk, 1) }}" rx="3" fill="var(--primary-600, #4f46e5)" opacity=".85"><title>{{ $a['ay'] }}: {{ $a['olay'] }} olay</title></rect>
        @if ($a['olay'])
            <text x="{{ round($bx + $kalin / 2, 1) }}" y="{{ round($y - $yuk - 4, 1) }}" text-anchor="middle" font-size="10" font-weight="700" fill="currentColor">{{ $a['olay'] }}</text>
        @endif
        <text x="{{ round($bx + $kalin / 2, 1) }}" y="208" text-anchor="middle" font-size="10" fill="currentColor" opacity=".75">{{ $a['kisa'] }}</text>
    @endforeach
    <polyline points="{{ implode(' ', $nokta) }}" fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round"/>
    @foreach ($nokta as $n)
        @php [$px, $py] = explode(',', $n); @endphp
        <circle cx="{{ $px }}" cy="{{ $py }}" r="3" fill="#dc2626" stroke="#fff" stroke-width="1"/>
    @endforeach
</svg>
<div style="display:flex;gap:1rem;font-size:.75rem;margin-top:.3rem">
    <span><i style="display:inline-block;width:.7rem;height:.7rem;border-radius:2px;background:var(--primary-600,#4f46e5);vertical-align:-1px"></i> Olay</span>
    <span><i style="display:inline-block;width:.9rem;height:2px;background:#dc2626;vertical-align:3px"></i> Kayıtlı yaralanma</span>
</div>
