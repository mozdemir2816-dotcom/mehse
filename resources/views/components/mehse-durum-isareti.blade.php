{{-- ISO 7010 dilinde durum işareti (Pano teması, 13.11.2026): renk + şekil — renk ayırt edemeyen de şekilden okur.
     gecikti: kırmızı yasak dairesi · yaklasiyor: sarı uyarı üçgeni · tamam: yeşil kare · zorunlu: mavi daire --}}
@props(['durum' => 'tamam', 'etiket' => null])
@php
    $etiket ??= ['gecikti' => 'Gecikti', 'yaklasiyor' => 'Yaklaşıyor', 'tamam' => 'Güncel', 'zorunlu' => 'Yapılmalı'][$durum] ?? $durum;
@endphp
<svg {{ $attributes->class('mehse-isaret') }} viewBox="0 0 16 16" role="img" aria-label="{{ $etiket }}">
    @switch($durum)
        @case('gecikti')
            <circle cx="8" cy="8" r="7" fill="#B3122E"/><path d="M3.4 12.6 12.6 3.4" stroke="#fff" stroke-width="2.2"/>
            @break
        @case('yaklasiyor')
            <path d="M8 1.3 15 14.4H1Z" fill="#F2B705" stroke="#22272B" stroke-width="1.4" stroke-linejoin="round"/><path d="M8 6v3.6" stroke="#22272B" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="11.9" r=".95" fill="#22272B"/>
            @break
        @case('zorunlu')
            <circle cx="8" cy="8" r="7" fill="#0057A8"/><path d="M8 4.2v4.6" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="8" cy="11.5" r="1.1" fill="#fff"/>
            @break
        @default
            <rect x="1.5" y="1.5" width="13" height="13" rx="1.6" fill="#00704A"/><path d="m4.6 8.2 2.3 2.3 4.6-4.8" stroke="#fff" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    @endswitch
</svg>
