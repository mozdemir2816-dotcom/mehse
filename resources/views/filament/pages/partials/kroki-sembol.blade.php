{{-- Çekirdek kroki sembolü — 40x40 kutu, ($x,$y) merkez. Basitleştirilmiş şematik
     piktogram (resmi ISO 7010 sanatı değil); editör ve PDF çıktısı bu partial'ı
     PAYLAŞIR, ikisi de birbirinden asla sapmaz. --}}
@php
    $renk = config('isg.kroki.semboller.'.$tip.'.renk', '#666');
    $makine = config('isg.kroki.semboller.'.$tip.'.sekil') === 'makine';
@endphp
@if ($makine)
{{-- Makine taban izi (editördeki 'makine' şekliyle aynı) --}}
<g transform="translate({{ $x }},{{ $y }})">
    <rect x="-33" y="-22" width="66" height="44" rx="4" fill="#e2e8f0" stroke="{{ $renk }}" stroke-width="2.5" />
    <text x="0" y="5" text-anchor="middle" font-size="11" font-weight="bold" fill="{{ $renk }}">{{ config('isg.kroki.semboller.'.$tip.'.ikon') }}</text>
</g>
@else
<g transform="translate({{ $x }},{{ $y }})">
    <rect x="-18" y="-18" width="36" height="36" rx="6" fill="{{ $renk }}" />

    @switch($tip)
        @case('cikis')
            <line x1="-8" y1="-9" x2="-8" y2="9" stroke="#fff" stroke-width="3" />
            <polygon points="-2,-8 -2,8 10,0" fill="#fff" />
            @break
        @case('toplanma')
            <circle cx="0" cy="-6" r="4" fill="#fff" />
            <circle cx="-8" cy="7" r="4" fill="#fff" />
            <circle cx="8" cy="7" r="4" fill="#fff" />
            @break
        @case('merdiven')
            <polyline points="-9,9 -3,9 -3,3 3,3 3,-3 9,-3" fill="none" stroke="#fff" stroke-width="3" />
            <polygon points="9,-9 9,1 1,-4" fill="#fff" />
            @break
        @case('sondurucu')
            <circle cx="0" cy="-10" r="3" fill="#fff" />
            <rect x="-6" y="-7" width="12" height="18" rx="3" fill="#fff" />
            <rect x="-8" y="10" width="16" height="3" fill="#fff" />
            @break
        @case('hidrant')
            <circle cx="0" cy="0" r="10" fill="none" stroke="#fff" stroke-width="3" />
            <circle cx="0" cy="0" r="4" fill="#fff" />
            @break
        @case('alarm')
            <polygon points="0,-10 9,8 -9,8" fill="#fff" />
            <rect x="-2" y="1" width="4" height="4" fill="{{ $renk }}" />
            @break
        @case('ilkyardim')
            <rect x="-9" y="-3" width="18" height="6" fill="#fff" />
            <rect x="-3" y="-9" width="6" height="18" fill="#fff" />
            @break
        @default
            <rect x="-14" y="-6" width="28" height="12" fill="#fff" />
    @endswitch
</g>
@endif
@if (($etiket ?? null))
    <text x="{{ $x }}" y="{{ $y + 28 }}" font-size="10" text-anchor="middle" fill="#333">{{ $etiket }}</text>
@endif
